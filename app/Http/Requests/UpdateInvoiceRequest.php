<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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

        if ($this->has('customer_cnpj')) {
            $cnpj = preg_replace('/\D/', '', (string) $this->input('customer_cnpj'));
            $merge['customer_cnpj'] = $cnpj !== '' ? $cnpj : null;
        }

        if ($this->has('invoice_number')) {
            $number = trim((string) $this->input('invoice_number'));
            $merge['invoice_number'] = $number !== '' ? $number : null;
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
        $invoiceId = $this->route('invoice');
        if (is_object($invoiceId)) {
            $invoiceId = $invoiceId->id;
        }

        return [
            'issue_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'invoice_number' => ['nullable', 'string', 'max:40', Rule::unique('invoices', 'invoice_number')->ignore($invoiceId)],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_cnpj' => ['nullable', 'digits:14'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
