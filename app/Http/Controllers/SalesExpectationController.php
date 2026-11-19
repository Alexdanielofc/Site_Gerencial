<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Http\Requests\UpdateSalesExpectationsRequest;
use App\Models\SalesExpectation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesExpectationController extends Controller
{
    /**
     * GET /modulos/vendas/expectativas
     */
    public function edit(PeriodRequest $request): View|RedirectResponse
    {
        $today = today();
        $monthStart = $request->monthStart();

        if (! UpdateSalesExpectationsRequest::isMonthAllowed($monthStart, $today)) {
            return redirect()
                ->route('sales.expectations.edit', ['ano' => $today->year, 'mes' => $today->month])
                ->with('warning', 'O período selecionado não está disponível. Exibindo o mês atual.');
        }

        $allowed = UpdateSalesExpectationsRequest::allowedMonths($today);

        // Build all days of the selected month
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysCount = $monthStart->daysInMonth;

        // Load saved expectations for the month indexed by date string
        $saved = SalesExpectation::query()
            ->whereBetween('expectation_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn ($row) => $row->expectation_date->toDateString());

        $days = [];
        for ($d = 1; $d <= $daysCount; $d++) {
            $date = Carbon::create($monthStart->year, $monthStart->month, $d);
            $key = $date->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $date->format('d/m/Y'),
                'weekday' => Str::ucfirst($date->translatedFormat('l')),
                'amount' => $saved->has($key)
                    ? number_format($saved[$key]->amount, 2, ',', '.')
                    : '',
            ];
        }

        // Pills: previous, current, next
        $pills = collect($allowed)->map(fn (Carbon $m) => [
            'year' => $m->year,
            'month' => $m->month,
            'label' => Str::ucfirst($m->translatedFormat('F/Y')),
            'selected' => $m->year === $monthStart->year && $m->month === $monthStart->month,
        ]);

        return view('modules.sales.expectations', compact('monthStart', 'days', 'pills'));
    }

    /**
     * PUT /modulos/vendas/expectativas
     */
    public function update(UpdateSalesExpectationsRequest $request): RedirectResponse
    {
        $today = today();
        $monthStart = $request->monthStart();

        if (! UpdateSalesExpectationsRequest::isMonthAllowed($monthStart, $today)) {
            return redirect()
                ->route('sales.expectations.edit', ['ano' => $today->year, 'mes' => $today->month])
                ->with('warning', 'O período selecionado não está disponível. Exibindo o mês atual.');
        }

        $validatedExpectations = $request->validated('expectations');
        $now = now();
        $rows = [];

        foreach ($validatedExpectations as $dateStr => $amount) {
            $rows[] = [
                'expectation_date' => $dateStr,
                'amount' => $amount,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows): void {
            if (! empty($rows)) {
                SalesExpectation::upsert($rows, ['expectation_date'], ['amount', 'updated_at']);
            }
        });

        return redirect()
            ->route('sales.expectations.edit', ['ano' => $monthStart->year, 'mes' => $monthStart->month])
            ->with('success', 'Expectativas salvas com sucesso.');
    }
}
