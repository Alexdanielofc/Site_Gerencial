<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('amount')) {
            $merge['amount'] = Money::parseToDecimal($this->input('amount'));
        }

        if ($this->has('paid_at') && trim((string) $this->input('paid_at')) === '') {
            $merge['paid_at'] = null;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'paid_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
