<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'canceled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'amount' => 'decimal:2',
            'canceled_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('canceled_at');
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->whereNotNull('canceled_at');
    }

    public function isCanceled(): bool
    {
        return $this->canceled_at !== null;
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
     * Total cents between two dates, inclusive (active invoices only).
     */
    public static function totalCentsBetween(Carbon $start, Carbon $end): int
    {
        return (int) static::query()
            ->active()
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->sum(DB::raw('CAST(ROUND(amount * 100) AS SIGNED)'));
    }

    /**
     * Count of invoices between two dates, inclusive (active invoices only).
     */
    public static function countBetween(Carbon $start, Carbon $end): int
    {
        return static::query()
            ->active()
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    /**
     * Count of invoices issued on a specific date (active invoices only).
     */
    public static function countOn(Carbon $date): int
    {
        return static::query()
            ->active()
            ->where('issue_date', $date->toDateString())
            ->count();
    }

    /**
     * Sum of cents issued on a specific date (active invoices only).
     */
    public static function centsOn(Carbon $date): int
    {
        return (int) static::query()
            ->active()
            ->where('issue_date', $date->toDateString())
            ->sum(DB::raw('CAST(ROUND(amount * 100) AS SIGNED)'));
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
