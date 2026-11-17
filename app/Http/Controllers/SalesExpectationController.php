<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Models\SalesExpectation;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SalesExpectationController extends Controller
{
    /**
     * GET /modulos/vendas/expectativas
     */
    public function edit(PeriodRequest $request): View|RedirectResponse
    {
        // Only 3 months allowed: previous, current, next
        $today = today();
        $allowed = [
            $today->copy()->subMonthNoOverflow()->startOfMonth(),
            $today->copy()->startOfMonth(),
            $today->copy()->addMonthNoOverflow()->startOfMonth(),
        ];

        $monthStart = $request->monthStart();
        $isAllowed = collect($allowed)->contains(
            fn (Carbon $m) => $m->year === $monthStart->year && $m->month === $monthStart->month
        );

        if (! $isAllowed) {
            return redirect()
                ->route('sales.expectations.edit', ['ano' => $today->year, 'mes' => $today->month])
                ->with('warning', 'O período selecionado não está disponível. Exibindo o mês atual.');
        }

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
    public function update(PeriodRequest $request): RedirectResponse
    {
        $today = today();
        $allowed = [
            $today->copy()->subMonthNoOverflow()->startOfMonth(),
            $today->copy()->startOfMonth(),
            $today->copy()->addMonthNoOverflow()->startOfMonth(),
        ];

        $monthStart = $request->monthStart();
        $isAllowed = collect($allowed)->contains(
            fn (Carbon $m) => $m->year === $monthStart->year && $m->month === $monthStart->month
        );

        if (! $isAllowed) {
            return redirect()
                ->route('sales.expectations.edit', ['ano' => $today->year, 'mes' => $today->month])
                ->with('error', 'Período inválido.');
        }

        $monthEnd = $monthStart->copy()->endOfMonth();
        $rawValues = $request->input('expectations', []);

        // Validate each key/value pair
        $rows = [];
        foreach ($rawValues as $dateStr => $rawAmount) {
            // Date must be within the displayed month
            $date = Carbon::createFromFormat('Y-m-d', $dateStr);
            if (! $date || $date->toDateString() !== $dateStr) {
                continue;
            }
            if ($date->lt($monthStart) || $date->gt($monthEnd)) {
                continue;
            }

            $decimal = Money::parseToDecimal($rawAmount);
            $amount = ($decimal === null || $decimal === '') ? '0.00' : $decimal;

            $rows[] = [
                'expectation_date' => $dateStr,
                'amount' => $amount,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if (! empty($rows)) {
            SalesExpectation::upsert($rows, ['expectation_date'], ['amount', 'updated_at']);
        }

        return redirect()
            ->route('sales.expectations.edit', ['ano' => $monthStart->year, 'mes' => $monthStart->month])
            ->with('success', 'Expectativas salvas com sucesso.');
    }
}
