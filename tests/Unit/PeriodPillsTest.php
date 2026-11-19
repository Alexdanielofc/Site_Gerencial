<?php

use App\Support\PeriodPills;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('unions record periods with the current month without duplicates and sorts them', function () {
    $today = Carbon::create(2026, 6, 15);
    $selected = Carbon::create(2026, 6, 1);

    $records = collect([
        ['year' => 2026, 'month' => 4],
        ['year' => 2026, 'month' => 6],
        ['year' => 2026, 'month' => 1],
    ]);

    $result = PeriodPills::build($records, $selected, $today);

    expect($result['years']->all())->toBe([
        ['year' => 2026, 'month' => 6],
    ]);
    expect($result['months']->pluck('month')->all())->toBe([1, 4, 6]);
});

it('keeps the selected month for the selected year and uses current month for today year', function () {
    $today = Carbon::create(2026, 6, 15);
    $selected = Carbon::create(2025, 3, 1);

    $records = collect([
        ['year' => 2025, 'month' => 2],
        ['year' => 2025, 'month' => 3],
        ['year' => 2026, 'month' => 1],
    ]);

    $result = PeriodPills::build($records, $selected, $today);

    expect($result['years']->all())->toBe([
        ['year' => 2025, 'month' => 3],
        ['year' => 2026, 'month' => 6],
    ]);
});

it('keeps the selected month in another year if that month exists in it, otherwise uses the latest month', function () {
    $today = Carbon::create(2026, 6, 15);
    $selected = Carbon::create(2026, 4, 1);

    $records = collect([
        ['year' => 2024, 'month' => 4],
        ['year' => 2024, 'month' => 10],
        ['year' => 2025, 'month' => 2],
        ['year' => 2025, 'month' => 8],
        ['year' => 2026, 'month' => 4],
    ]);

    $result = PeriodPills::build($records, $selected, $today);

    expect($result['years']->all())->toBe([
        ['year' => 2024, 'month' => 4],
        ['year' => 2025, 'month' => 8],
        ['year' => 2026, 'month' => 4],
    ]);
});

it('formats month labels in full Portuguese with uppercase initial', function () {
    $today = Carbon::create(2026, 3, 10);
    $selected = Carbon::create(2026, 3, 1);

    $records = collect([
        ['year' => 2026, 'month' => 1],
        ['year' => 2026, 'month' => 3],
    ]);

    $result = PeriodPills::build($records, $selected, $today);

    expect($result['months']->all())->toBe([
        ['month' => 1, 'label' => 'Janeiro'],
        ['month' => 3, 'label' => 'Março'],
    ]);
});
