<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Models\DailySale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(PeriodRequest $request): View
    {
        $monthStart = $request->monthStart();

        $monthsByYear = DailySale::periodsWithRecords()
            ->push(['year' => now()->year, 'month' => now()->month])
            ->unique(fn (array $period) => $period['year'].'-'.$period['month'])
            ->groupBy('year')
            ->map(fn (Collection $periods) => $periods->pluck('month')->sort()->values())
            ->sortKeys();

        $years = $monthsByYear->map(function (Collection $months, int $year) use ($monthStart) {
            return [
                'year' => $year,
                'month' => match (true) {
                    $year === $monthStart->year => $monthStart->month,
                    $year === now()->year => now()->month,
                    $months->contains($monthStart->month) => $monthStart->month,
                    default => $months->last(),
                },
            ];
        })->values();

        $months = $monthsByYear->get($monthStart->year, collect())->map(fn (int $month) => [
            'month' => $month,
            'label' => Str::ucfirst(Carbon::create(2000, $month, 1)->translatedFormat('F')),
        ]);

        return view('index', compact('monthStart', 'years', 'months'));
    }
}
