<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
