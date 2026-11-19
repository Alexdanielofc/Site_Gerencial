<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class UpdateSalesExpectationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function allowedMonths(?Carbon $today = null): array
    {
        $today = $today ?? today();

        return [
            $today->copy()->subMonthNoOverflow()->startOfMonth(),
            $today->copy()->startOfMonth(),
            $today->copy()->addMonthNoOverflow()->startOfMonth(),
        ];
    }

    public static function isMonthAllowed(Carbon $monthStart, ?Carbon $today = null): bool
    {
        $allowed = self::allowedMonths($today);

        return collect($allowed)->contains(
            fn (Carbon $m) => $m->year === $monthStart->year && $m->month === $monthStart->month
        );
    }

    public function monthStart(): Carbon
    {
        $today = today();
        $ano = $this->integer('ano') ?: $today->year;
        $mes = $this->integer('mes') ?: $today->month;

        if ($ano < 2000 || $ano > 2100 || $mes < 1 || $mes > 12) {
            return Carbon::create($today->year, $today->month, 1)->startOfDay();
        }

        return Carbon::create($ano, $mes, 1)->startOfDay();
    }

    protected function prepareForValidation(): void
    {
        $rawValues = $this->input('expectations');

        if (! is_array($rawValues)) {
            return;
        }

        $prepared = [];
        foreach ($rawValues as $key => $value) {
            if (is_array($value)) {
                $prepared[$key] = $value;

                continue;
            }

            // ConvertEmptyStringsToNull turns '' into null before this point.
            if ($value === null) {
                $prepared[$key] = '0.00';

                continue;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $prepared[$key] = '0.00';

                    continue;
                }

                $decimal = Money::parseToDecimal($trimmed);
                $prepared[$key] = $decimal ?? $trimmed;

                continue;
            }

            $prepared[$key] = $value;
        }

        $this->merge(['expectations' => $prepared]);
    }

    public function rules(): array
    {
        return [
            'expectations' => ['required', 'array'],
            'expectations.*' => [
                'bail',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_array($value)) {
                        $fail('informe um valor válido.');
                    }
                },
                'numeric',
                'min:0',
                'max:9999999999.99',
                'decimal:0,2',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $monthStart = $this->monthStart();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $expectations = $this->input('expectations', []);

            if (! is_array($expectations)) {
                return;
            }

            foreach (array_keys($expectations) as $dateStr) {
                $date = Carbon::createFromFormat('!Y-m-d', (string) $dateStr);

                if (! $date || $date->format('Y-m-d') !== (string) $dateStr) {
                    $v->errors()->add("expectations.{$dateStr}", 'Data inválida.');

                    continue;
                }

                if ($date->lt($monthStart) || $date->gt($monthEnd)) {
                    $v->errors()->add("expectations.{$dateStr}", 'Data fora do mês exibido.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'expectations.required' => 'Nenhuma expectativa foi enviada.',
            'expectations.array' => 'Formato inválido de expectativas.',
            'expectations.*.numeric' => 'informe um valor válido.',
            'expectations.*.min' => 'o valor não pode ser negativo.',
            'expectations.*.max' => 'o valor informado é excessivo.',
            'expectations.*.decimal' => 'informe um valor com até 2 casas decimais.',
        ];
    }

    public function attributes(): array
    {
        $monthStart = $this->monthStart();
        $daysCount = $monthStart->daysInMonth;
        $attrs = [];

        for ($d = 1; $d <= $daysCount; $d++) {
            $date = Carbon::create($monthStart->year, $monthStart->month, $d);
            $key = $date->toDateString();
            $attrs["expectations.{$key}"] = 'Expectativa de '.$date->format('d/m/Y');
        }

        return $attrs;
    }
}
