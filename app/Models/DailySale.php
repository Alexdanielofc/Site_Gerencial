<?php

namespace App\Models;

use illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

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

    public static function periodsWithRecords(): Collection
    {
        $expectations = SalesExpectation::query()
            ->selectRaw('YEAR(expectation_date) as year, MONTH(expectation_date) as month')
            ->toBase();

        return static::query()
            ->selectRaw('YEAR(sale_date) as year, MONTH(sale_date) as month')
            ->union($expectations)
            ->orderBY('year')
            ->orderBy('month')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['year' => (int) $row->year, 'month' => (int) $row->month]);
    }
}
