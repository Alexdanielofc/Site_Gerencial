<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class UpdatePayableRequest extends FormRequest
{
    public const ALLOWED_STATUSES = ['todas', 'pendentes', 'vencidas', 'pagas', 'canceladas'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize navigation context (invalid values are discarded without error).
     *
     * @return array{origem: string, ano: ?int, mes: ?int, busca: ?string, situacao: ?string, page: ?int}
     */
    public static function sanitizeContext(Request $request): array
    {
        $rawOrigin = $request->input('origem');
        $origem = is_string($rawOrigin) && in_array($rawOrigin, ['index', 'advanced'], true)
            ? $rawOrigin
            : 'advanced';

        $rawAno = $request->input('ano');
        $ano = is_scalar($rawAno) && filter_var($rawAno, FILTER_VALIDATE_INT) !== false
            ? (int) $rawAno
            : null;
        if ($ano !== null && ($ano < 2000 || $ano > 2100)) {
            $ano = null;
        }

        $rawMes = $request->input('mes');
        $mes = is_scalar($rawMes) && filter_var($rawMes, FILTER_VALIDATE_INT) !== false
            ? (int) $rawMes
            : null;
        if ($mes !== null && ($mes < 1 || $mes > 12)) {
            $mes = null;
        }

        $rawBusca = $request->input('busca');
        $busca = is_string($rawBusca) ? trim($rawBusca) : '';
        if ($busca === '' || mb_strlen($busca) > 100) {
            $busca = null;
        }

        $rawSituacao = $request->input('situacao');
        $situacao = is_string($rawSituacao) && in_array($rawSituacao, self::ALLOWED_STATUSES, true) && $rawSituacao !== 'todas'
            ? $rawSituacao
            : null;

        $rawPage = $request->input('page');
        $page = is_scalar($rawPage) && filter_var($rawPage, FILTER_VALIDATE_INT) !== false
            ? (int) $rawPage
            : null;
        if ($page !== null && $page <= 1) {
            $page = null;
        }

        return [
            'origem' => $origem,
            'ano' => $ano,
            'mes' => $mes,
            'busca' => $busca,
            'situacao' => $situacao,
            'page' => $page,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public static function advancedRouteParams(Request $request): array
    {
        $ctx = self::sanitizeContext($request);
        unset($ctx['origem']);

        return array_filter($ctx, fn ($v) => $v !== null);
    }

    /**
     * @return array<string, int|string>
     */
    public function validatedContextParams(): array
    {
        return self::advancedRouteParams($this);
    }

    public function validatedOrigin(): string
    {
        return self::sanitizeContext($this)['origem'];
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
