<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * GET /modulos/notas-fiscais
     */
    public function index(): View
    {
        $today = today();
        $monthStart = Carbon::create($today->year, $today->month, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Today's invoices
        $todayInvoices = Invoice::query()
            ->where('issue_date', $today->toDateString())
            ->orderByDesc('id')
            ->get();

        // Month indicators
        $monthCount = Invoice::countBetween($monthStart, $monthEnd);
        $monthTotalCents = Invoice::totalCentsBetween($monthStart, $monthEnd);
        $todayCount = $todayInvoices->count();
        $todayTotalCents = (int) round($todayInvoices->sum('amount') * 100);

        return view('modules.invoices.index', compact(
            'today',
            'todayInvoices',
            'monthCount',
            'monthTotalCents',
            'todayCount',
            'todayTotalCents'
        ));
    }

    /**
     * POST /modulos/notas-fiscais
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        Invoice::create($request->validated());

        return redirect()->route('invoices.index')
            ->with('success', 'Nota fiscal registrada com sucesso.');
    }

    /**
     * GET /modulos/notas-fiscais/avancado
     */
    public function advanced(PeriodRequest $request): View
    {
        $monthStart = $request->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $today = today();

        // Build periods list (invoices + current month)
        $monthsByYear = Invoice::periodsWithRecords()
            ->push(['year' => $today->year, 'month' => $today->month])
            ->unique(fn (array $p) => $p['year'].'-'.$p['month'])
            ->groupBy('year')
            ->map(fn (Collection $periods) => $periods->pluck('month')->sort()->values())
            ->sortKeys();

        $years = $monthsByYear->map(function (Collection $months, int $year) use ($monthStart) {
            return [
                'year' => $year,
                'month' => match (true) {
                    $year === $monthStart->year => $monthStart->month,
                    $year === today()->year => today()->month,
                    $months->contains($monthStart->month) => $monthStart->month,
                    default => $months->last(),
                },
            ];
        })->values();

        $months = $monthsByYear->get($monthStart->year, collect())->map(fn (int $month) => [
            'month' => $month,
            'label' => Str::ucfirst(Carbon::create(2000, $month, 1)->translatedFormat('F')),
        ]);

        $search = trim((string) $request->input('busca', ''));

        // Query invoices for the period
        $query = Invoice::query()
            ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        // Indicators for the period (unfiltered by search term, as specified in rules)
        $periodCount = Invoice::countBetween($monthStart, $monthEnd);
        $periodTotalCents = Invoice::totalCentsBetween($monthStart, $monthEnd);
        $averageTicket = $periodCount > 0 ? (int) round($periodTotalCents / $periodCount) : null;

        $invoices = $query->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('modules.invoices.advanced', compact(
            'monthStart',
            'years',
            'months',
            'invoices',
            'search',
            'periodCount',
            'periodTotalCents',
            'averageTicket'
        ));
    }

    /**
     * GET /modulos/notas-fiscais/nova
     */
    public function create(): View
    {
        $today = today();

        return view('modules.invoices.create', compact('today'));
    }

    /**
     * GET /modulos/notas-fiscais/{invoice}/editar
     */
    public function edit(Invoice $invoice, Request $request): View
    {
        $today = today();
        $origin = $request->input('origem', 'advanced');

        return view('modules.invoices.edit', compact('invoice', 'today', 'origin'));
    }

    /**
     * PUT /modulos/notas-fiscais/{invoice}
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($request->validated());

        $origin = $request->input('origem', 'advanced');

        if ($origin === 'index') {
            return redirect()->route('invoices.index')
                ->with('success', 'Nota fiscal atualizada com sucesso.');
        }

        return redirect()->route('invoices.advanced')
            ->with('success', 'Nota fiscal atualizada com sucesso.');
    }
}
