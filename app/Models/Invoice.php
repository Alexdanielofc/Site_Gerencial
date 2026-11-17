<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $fillable = [
        'issue_date',
        'invoice_number',
        'customer_name',
        'customer_cnpj',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Formatted CNPJ with mask (00.000.000/0000-00) or null.
     */
    protected function formattedCnpj(): Attribute
    {
        return Attribute::make(
            get: function () {
                $cnpj = preg_replace('/\D/', '', $this->customer_cnpj ?? '');
                if (strlen($cnpj) !== 14) {
                    return $this->customer_cnpj;
                }

                return vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($cnpj));
            }
        );
    }

    /**
     * Total cents between two dates, inclusive.
     */
    public static function totalCentsBetween(Carbon $start, Carbon $end): int
    {
        return (int) static::query()
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->sum(DB::raw('amount * 100'));
    }

    /**
     * Count of invoices between two dates, inclusive.
     */
    public static function countBetween(Carbon $start, Carbon $end): int
    {
        return static::query()
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    /**
     * Count of invoices issued on a specific date.
     */
    public static function countOn(Carbon $date): int
    {
        return static::query()
            ->where('issue_date', $date->toDateString())
            ->count();
    }

    /**
     * Sum of cents issued on a specific date.
     */
    public static function centsOn(Carbon $date): int
    {
        return (int) static::query()
            ->where('issue_date', $date->toDateString())
            ->sum(DB::raw('amount * 100'));
    }

    /**
     * Distinct year/month periods with invoices.
     */
    public static function periodsWithRecords(): Collection
    {
        return static::query()
            ->selectRaw('YEAR(issue_date) as year, MONTH(issue_date) as month')
            ->distinct()
            ->orderBy('year')
            ->orderBy('month')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['year' => (int) $row->year, 'month' => (int) $row->month]);
    }
}
