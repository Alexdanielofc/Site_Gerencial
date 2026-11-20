<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesExpectation extends Model
{
    protected $fillable = ['expectation_date', 'amount'];

    protected function casts(): array
    {
        return [
            'expectation_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Sum of expectations (in cents) between two dates, inclusive.
     */
    public static function totalCentsBetween(Carbon $start, Carbon $end): int
    {
        return (int) static::query()
            ->whereBetween('expectation_date', [$start->toDateString(), $end->toDateString()])
            ->sum(DB::raw('CAST(ROUND(amount * 100) AS SIGNED)'));
    }

    /**
     * Expectation amount in cents for a specific date, or null if no record exists.
     */
    public static function centsOn(Carbon $date): ?int
    {
        $cents = static::query()
            ->where('expectation_date', $date->toDateString())
            ->value(DB::raw('CAST(ROUND(amount * 100) AS SIGNED)'));

        return $cents !== null ? (int) $cents : null;
    }
}
