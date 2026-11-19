<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PeriodPills
{
    /**
     * @param  Collection<int, array{year: int, month: int}>  $recordPeriods
     * @return array{years: Collection<int, array{year: int, month: int}>, months: Collection<int, array{month: int, label: string}>}
     */
    public static function build(Collection $recordPeriods, Carbon $selected, Carbon $today): array
    {
        $monthsByYear = $recordPeriods
            ->push(['year' => $today->year, 'month' => $today->month])
            ->unique(fn (array $period) => $period['year'].'-'.$period['month'])
            ->groupBy('year')
            ->map(fn (Collection $periods) => $periods->pluck('month')->sort()->values())
            ->sortKeys();

        $years = $monthsByYear->map(function (Collection $months, int $year) use ($selected, $today) {
            return [
                'year' => $year,
                'month' => match (true) {
                    $year === $selected->year => $selected->month,
                    $year === $today->year => $today->month,
                    $months->contains($selected->month) => $selected->month,
                    default => $months->last(),
                },
            ];
        })->values();

        $months = $monthsByYear->get($selected->year, collect())->map(fn (int $month) => [
            'month' => $month,
            'label' => Str::ucfirst(Carbon::create(2000, $month, 1)->translatedFormat('F')),
        ]);

        return [
            'years' => $years,
            'months' => $months,
        ];
    }
}
