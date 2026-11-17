<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Payable extends Model
{
    protected $fillable = [
        'description',
        'amount',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Computed status: 'paga', 'vencida', or 'pendente'.
     */
    public function status(?Carbon $today = null): string
    {
        if ($this->paid_at !== null) {
            return 'paga';
        }

        $today = $today ?? today();

        if ($this->due_date->lt($today)) {
            return 'vencida';
        }

        return 'pendente';
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isOverdue(?Carbon $today = null): bool
    {
        return $this->status($today) === 'vencida';
    }

    public function isPending(?Carbon $today = null): bool
    {
        return $this->status($today) === 'pendente';
    }

    /**
     * Distinct year/month periods by due_date.
     */
    public static function periodsWithRecords(): Collection
    {
        return static::query()
            ->selectRaw('YEAR(due_date) as year, MONTH(due_date) as month')
            ->distinct()
            ->orderBy('year')
            ->orderBy('month')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['year' => (int) $row->year, 'month' => (int) $row->month]);
    }

    /**
     * Open payables requiring attention:
     * Unpaid and overdue, OR unpaid and due within the next 7 days (including today).
     */
    public static function attentionQuery(?Carbon $today = null)
    {
        $today = $today ?? today();
        $in7Days = $today->copy()->addDays(7);

        return static::query()
            ->whereNull('paid_at')
            ->where('due_date', '<=', $in7Days->toDateString())
            ->orderBy('due_date')
            ->orderBy('id');
    }
}
