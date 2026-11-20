<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailySale extends Model
{
    protected $fillable = ['sale_date', 'amount'];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Sum of sales (in cents) between two dates, inclusive.
     */
    public static function totalCentsBetween(Carbon $start, Carbon $end): int
    {
        return (int) static::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->sum(DB::raw('CAST(ROUND(amount * 100) AS SIGNED)'));
    }

    /**
     * Sales amount in cents for a specific date, or null if no record exists.
     */
    public static function centsOn(Carbon $date): ?int
    {
        $cents = static::query()
            ->where('sale_date', $date->toDateString())
            ->value(DB::raw('CAST(ROUND(amount * 100) AS SIGNED)'));

        return $cents !== null ? (int) $cents : null;
    }

    /**
     * Build the standard indicators array for the given month.
     * When $monthStart is the current month, computes the accrued goal up to today.
     * For past/future months, computes the full month goal.
     */
    public static function indicators(Carbon $monthStart, Carbon $today): array
    {
        $isCurrentMonth = $monthStart->year === $today->year
            && $monthStart->month === $today->month;

        $monthEnd = $monthStart->copy()->endOfMonth();

        // Total sold in the period
        $soldCents = static::totalCentsBetween($monthStart, $monthEnd);

        if ($isCurrentMonth) {
            // Accrued goal: sum of expectations from day 1 up to today
            $accruedGoalCents = SalesExpectation::totalCentsBetween($monthStart, $today);

            // Full month goal
            $fullGoalCents = SalesExpectation::totalCentsBetween($monthStart, $monthEnd);

            // Today's expectation (null = no record)
            $todayExpectationCents = SalesExpectation::centsOn($today);

            // Today's sale (null = no record)
            $todaySaleCents = static::centsOn($today);

            $accruedPercent = Money::percentOf($soldCents, $accruedGoalCents);

            return [
                'is_current_month' => true,
                'sold_cents' => $soldCents,
                'accrued_goal_cents' => $accruedGoalCents,
                'accrued_percent' => $accruedPercent,
                'accrued_percent_tone' => $accruedPercent === null ? 'neutral' : ($accruedPercent >= 100 ? 'green' : 'blue'),
                'full_goal_cents' => $fullGoalCents,
                'today_expectation_cents' => $todayExpectationCents,
                'today_sale_cents' => $todaySaleCents,
            ];
        }

        // Past or future month: full month goal
        $fullGoalCents = SalesExpectation::totalCentsBetween($monthStart, $monthEnd);
        $fullPercent = Money::percentOf($soldCents, $fullGoalCents);

        return [
            'is_current_month' => false,
            'sold_cents' => $soldCents,
            'full_goal_cents' => $fullGoalCents,
            'full_percent' => $fullPercent,
            'full_percent_tone' => $fullPercent === null ? 'neutral' : ($fullPercent >= 100 ? 'green' : 'blue'),
        ];
    }

    public static function periodsWithRecords(): Collection
    {
        $expectations = SalesExpectation::query()
            ->selectRaw('YEAR(expectation_date) as year, MONTH(expectation_date) as month')
            ->toBase();

        return static::query()
            ->selectRaw('YEAR(sale_date) as year, MONTH(sale_date) as month')
            ->union($expectations)
            ->orderBy('year')
            ->orderBy('month')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['year' => (int) $row->year, 'month' => (int) $row->month]);
    }
}
