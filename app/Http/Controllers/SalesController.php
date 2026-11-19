<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\DailySale;
use App\Models\SalesExpectation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SalesController extends Controller
{
    /**
     * GET /modulos/vendas
     */
    public function index(): View
    {
        $today = today();
        $monthStart = Carbon::create($today->year, $today->month, 1);

        $todaySale = DailySale::query()
            ->where('sale_date', $today->toDateString())
            ->first();

        $todayExpectation = SalesExpectation::query()
            ->where('expectation_date', $today->toDateString())
            ->first();

        $indicators = DailySale::indicators($monthStart, $today);

        return view('modules.sales.index', compact('today', 'todaySale', 'todayExpectation', 'indicators'));
    }

    /**
     * POST /modulos/vendas
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $date = today()->toDateString();
        $exists = DailySale::query()->where('sale_date', $date)->exists();

        if ($exists) {
            return redirect()->route('sales.index')
                ->with('success', 'Registro de hoje já adicionado.');
        }

        DailySale::create([
            'sale_date' => $date,
            'amount' => $request->validated('amount'),
        ]);

        return redirect()->route('sales.index')
            ->with('success', 'Venda registrada com sucesso.');
    }

    /**
     * GET /modulos/vendas/dia/{date}/editar
     */
    public function edit(string $date, PeriodRequest $request): View
    {
        $carbon = Carbon::createFromFormat('!Y-m-d', $date);

        abort_if(! $carbon || $carbon->format('Y-m-d') !== $date, 404);
        abort_if($carbon->isFuture(), 404);

        $sale = DailySale::query()
            ->where('sale_date', $date)
            ->first();

        $expectation = SalesExpectation::query()
            ->where('expectation_date', $date)
            ->first();

        $dayOfWeek = Str::ucfirst($carbon->translatedFormat('l'));

        $origin = $request->input('origem', 'index');
        $ano = $request->integer('ano') ?: null;
        $mes = $request->integer('mes') ?: null;

        return view('modules.sales.edit', compact('carbon', 'sale', 'expectation', 'dayOfWeek', 'origin', 'ano', 'mes'));
    }

    /**
     * GET /modulos/vendas/avancado
     */
    public function advanced(PeriodRequest $request): View
    {
        $monthStart = $request->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $today = today();

        // Build periods list (sales + expectations + current month)
        $monthsByYear = DailySale::periodsWithRecords()
            ->push(['year' => $today->year, 'month' => $today->month])
            ->unique(fn (array $p) => $p['year'].'-'.$p['month'])
            ->groupBy('year')
            ->map(fn ($periods) => $periods->pluck('month')->sort()->values())
            ->sortKeys();

        $years = $monthsByYear->map(function ($months, int $year) use ($monthStart) {
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

        // Indicators for the selected period
        $indicators = DailySale::indicators($monthStart, $today);

        // Load sales for this month in a single query
        $salesByDate = DailySale::query()
            ->whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn ($row) => $row->sale_date->toDateString());

        // Build all days of the month
        $days = [];
        $daysCount = $monthStart->daysInMonth;
        for ($d = 1; $d <= $daysCount; $d++) {
            $date = Carbon::create($monthStart->year, $monthStart->month, $d);
            $key = $date->toDateString();
            $sale = $salesByDate->get($key);
            $isFuture = $date->isAfter($today);

            $days[] = [
                'date' => $key,
                'label' => $date->format('d/m/Y'),
                'weekday' => Str::ucfirst($date->translatedFormat('l')),
                'amount' => $sale ? (int) round($sale->amount * 100) : 0,
                'has_record' => (bool) $sale,
                'is_future' => $isFuture,
            ];
        }

        return view('modules.sales.advanced', compact('monthStart', 'years', 'months', 'indicators', 'days'));
    }

    /**
     * PUT /modulos/vendas/dia/{date}
     */
    public function update(UpdateSaleRequest $request, string $date): RedirectResponse
    {
        $carbon = Carbon::createFromFormat('!Y-m-d', $date);

        abort_if(! $carbon || $carbon->format('Y-m-d') !== $date, 404);
        abort_if($carbon->isFuture(), 404);

        DailySale::updateOrCreate(
            ['sale_date' => $date],
            ['amount' => $request->validated('amount')]
        );

        $origin = $request->input('origem', 'index');
        $ano = $request->integer('ano') ?: null;
        $mes = $request->integer('mes') ?: null;

        if ($origin === 'advanced') {
            return redirect()->route('sales.advanced', array_filter(compact('ano', 'mes')))
                ->with('success', 'Venda atualizada com sucesso.');
        }

        return redirect()->route('sales.index')
            ->with('success', 'Venda atualizada com sucesso.');
    }
}
