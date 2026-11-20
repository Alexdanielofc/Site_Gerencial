<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\PeriodRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Support\PeriodPills;
use App\Support\SubmissionGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        // Month indicators (active only)
        $monthCount = Invoice::countBetween($monthStart, $monthEnd);
        $monthTotalCents = Invoice::totalCentsBetween($monthStart, $monthEnd);
        $todayCount = Invoice::countOn($today);
        $todayTotalCents = Invoice::centsOn($today);

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
        $fromCreate = $request->input('origem') === 'create';

        if (! SubmissionGuard::firstTime($request)) {
            if ($fromCreate) {
                return redirect()->route('invoices.advanced')
                    ->with('warning', 'Este registro já foi enviado.');
            }

            return redirect()->route('invoices.index')
                ->with('warning', 'Este registro já foi enviado.');
        }

        $invoice = Invoice::create($request->validated());

        if ($fromCreate) {
            return redirect()->route('invoices.advanced', [
                'ano' => $invoice->issue_date->year,
                'mes' => $invoice->issue_date->month,
            ])->with('success', 'Nota fiscal registrada com sucesso.');
        }

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
        ['years' => $years, 'months' => $months] = PeriodPills::build(
            Invoice::periodsWithRecords(),
            $monthStart,
            $today
        );

        $statusFilter = (string) $request->input('situacao', 'todas');
        if (! in_array($statusFilter, UpdateInvoiceRequest::ALLOWED_STATUSES, true)) {
            $statusFilter = 'todas';
        }
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

        match ($statusFilter) {
            'ativas' => $query->active(),
            'canceladas' => $query->canceled(),
            default => null,
        };

        // Indicators for the period (unfiltered by search term, active only)
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
            'statusFilter',
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
        $context = UpdateInvoiceRequest::sanitizeContext($request);
        $origin = $context['origem'];
        $contextParams = UpdateInvoiceRequest::advancedRouteParams($request);
        $backHref = $origin === 'index'
            ? route('invoices.index')
            : route('invoices.advanced', $contextParams);

        return view('modules.invoices.edit', compact('invoice', 'today', 'origin', 'context', 'backHref'));
    }

    /**
     * PUT /modulos/notas-fiscais/{invoice}
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->isCanceled()) {
            return $this->redirectToContext($request)
                ->with('warning', 'Nota fiscal cancelada não pode ser editada. Reative-a antes de editar.');
        }

        $invoice->update($request->validated());

        return $this->redirectToContext($request)
            ->with('success', 'Nota fiscal atualizada com sucesso.');
    }

    /**
     * POST /modulos/notas-fiscais/{invoice}/cancelar
     */
    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->isCanceled()) {
            return $this->redirectToContext($request)
                ->with('warning', 'Esta nota fiscal já está cancelada.');
        }

        $invoice->update([
            'canceled_at' => now(),
            'cancel_reason' => $request->validated('cancel_reason'),
        ]);

        return $this->redirectToContext($request)
            ->with('success', 'Nota fiscal cancelada com sucesso.');
    }

    /**
     * POST /modulos/notas-fiscais/{invoice}/reativar
     */
    public function reactivate(Request $request, Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isCanceled()) {
            return $this->redirectToContext($request)
                ->with('warning', 'Esta nota fiscal já está ativa.');
        }

        $invoice->update([
            'canceled_at' => null,
            'cancel_reason' => null,
        ]);

        return $this->redirectToContext($request)
            ->with('success', 'Nota fiscal reativada com sucesso.');
    }

    private function redirectToContext(Request $request): RedirectResponse
    {
        $context = UpdateInvoiceRequest::sanitizeContext($request);

        if ($context['origem'] === 'index') {
            return redirect()->route('invoices.index');
        }

        return redirect()->route('invoices.advanced', UpdateInvoiceRequest::advancedRouteParams($request));
    }
}
