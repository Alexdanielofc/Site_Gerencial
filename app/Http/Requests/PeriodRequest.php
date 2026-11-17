<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ano' => ['nullable', 'integer', 'between:2000,2100'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    public function monthStart(): Carbon
    {
        return Carbon::create(
            $this->integer('ano') ?: now()->year,
            $this->integer('mes') ?: now()->month,
            1,
            0,
            0,
            0,
        );
    }
}
