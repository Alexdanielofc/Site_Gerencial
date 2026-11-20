<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cancel_reason')) {
            $reason = trim((string) $this->input('cancel_reason'));
            $this->merge([
                'cancel_reason' => $reason !== '' ? $reason : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cancel_reason' => 'motivo do cancelamento',
        ];
    }
}
