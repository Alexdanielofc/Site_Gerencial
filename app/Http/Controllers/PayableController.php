<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPayableRequest;
use App\Http\Requests\PeriodRequest;
use App\Http\Requests\StorePayableRequest;
use App\Http\Requests\UpdatePayableRequest;
use App\Models\Payable;
use App\Support\PeriodPills;
use App\Support\SubmissionGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends Controller
{
    /**
     * GET /modulos/contas-a-pagar
     */
    public function index(): View
    {
        $today = today();
        $in7Days = $today->copy()->addDays(7);

        // List "Em atenção": active unpaid overdue OR active unpaid due within 7 days
        $attentionPayables = Payable::query()
            ->active()
            ->whereNull('paid_at')
            ->where('due_date', '<=', $in7Days->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        // Indicador: Total em aberto (todas as contas ativas não pagas)
        $totalOpenCents = (int) Payable::query()
            ->active()
            ->whereNull('paid_at')
            ->sum(DB::raw('amount * 100'));

        // Indicador: Vencidas (ativas em aberto e vencimento < hoje)
        $overdueQuery = Payable::query()
            ->active()
            ->whereNull('paid_at')
            ->where('due_date', '<', $today->toDateString());

        $overdueCount = $overdueQuery->count();
        $overdueTotalCents = (int) $overdueQuery->sum(DB::raw('amount * 100'));

        // Indicador: A vencer em até 7 dias (ativas em aberto e hoje <= due_date <= hoje+7)
        $upcomingQuery = Payable::query()
            ->active()
            ->whereNull('paid_at')
            ->whereBetween('due_date', [$today->toDateString(), $in7Days->toDateString()]);

        $upcomingCount = $upcomingQuery->count();
        $upcomingTotalCents = (int) $upcomingQuery->sum(DB::raw('amount * 100'));

        return view('modules.payables.index', compact(
            'today',
            'attentionPayables',
            'totalOpenCents',
            'overdueCount',
            'overdueTotalCents',
            'upcomingCount',
            'upcomingTotalCents'
        ));
    }

    /**
     * POST /modulos/contas-a-pagar
     */
    public function store(StorePayableRequest $request): RedirectResponse
    {
        $fromCreate = $request->input('origem') === 'create';

        if (! SubmissionGuard::firstTime($request)) {
            if ($fromCreate) {
                return redirect()->route('payables.advanced')
                    ->with('warning', 'Este registro já foi enviado.');
            }

            return redirect()->route('payables.index')
                ->with('warning', 'Este registro já foi enviado.');
        }

        $payable = Payable::create($request->validated());

        if ($fromCreate) {
            return redirect()->route('payables.advanced', [
                'ano' => $payable->due_date->year,
                'mes' => $payable->due_date->month,
            ])->with('success', 'Conta a pagar registrada com sucesso.');
        }

        return redirect()->route('payables.index')
            ->with('success', 'Conta a pagar registrada com sucesso.');
    }

    /**
     * GET /modulos/contas-a-pagar/avancado
     */
    public function advanced(PeriodRequest $request): View
    {
        $monthStart = $request->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $today = today();

        // Pílulas: períodos com registro por due_date + mês atual
        ['years' => $years, 'months' => $months] = PeriodPills::build(
            Payable::periodsWithRecords(),
            $monthStart,
            $today
        );

        $statusFilter = (string) $request->input('situacao', 'todas');
        if (! in_array($statusFilter, UpdatePayableRequest::ALLOWED_STATUSES, true)) {
            $statusFilter = 'todas';
        }
        $search = trim((string) $request->input('busca', ''));

        // Period Totals in R$ (active only)
        $periodTotalCents = (int) Payable::query()
            ->active()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum(DB::raw('amount * 100'));

        $periodPaidCents = (int) Payable::query()
            ->active()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNotNull('paid_at')
            ->sum(DB::raw('amount * 100'));

        $periodPendingCents = (int) Payable::query()
            ->active()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNull('paid_at')
            ->where('due_date', '>=', $today->toDateString())
            ->sum(DB::raw('amount * 100'));

        $periodOverdueCents = (int) Payable::query()
            ->active()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNull('paid_at')
            ->where('due_date', '<', $today->toDateString())
            ->sum(DB::raw('amount * 100'));

        // Query with filters
        $query = Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        if ($search !== '') {
            $query->where('description', 'like', "%{$search}%");
        }

        match ($statusFilter) {
            'pagas' => $query->active()->whereNotNull('paid_at'),
            'vencidas' => $query->active()->whereNull('paid_at')->where('due_date', '<', $today->toDateString()),
            'pendentes' => $query->active()->whereNull('paid_at')->where('due_date', '>=', $today->toDateString()),
            'canceladas' => $query->canceled(),
            default => null,
        };

        $payables = $query->orderBy('due_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('modules.payables.advanced', compact(
            'monthStart',
            'years',
            'months',
            'today',
            'payables',
            'statusFilter',
            'search',
            'periodTotalCents',
            'periodPaidCents',
            'periodPendingCents',
            'periodOverdueCents'
        ));
    }

    /**
     * GET /modulos/contas-a-pagar/nova
     */
    public function create(): View
    {
        $today = today();

        return view('modules.payables.create', compact('today'));
    }

    /**
     * GET /modulos/contas-a-pagar/{payable}/editar
     */
    public function edit(Payable $payable, Request $request): View
    {
        $today = today();
        $context = UpdatePayableRequest::sanitizeContext($request);
        $origin = $context['origem'];
        $contextParams = UpdatePayableRequest::advancedRouteParams($request);
        $backHref = $origin === 'index'
            ? route('payables.index')
            : route('payables.advanced', $contextParams);

        return view('modules.payables.edit', compact('payable', 'today', 'origin', 'context', 'backHref'));
    }

    /**
     * PUT /modulos/contas-a-pagar/{payable}
     */
    public function update(UpdatePayableRequest $request, Payable $payable): RedirectResponse
    {
        if ($payable->isCanceled()) {
            return $this->redirectToContext($request)
                ->with('warning', 'Conta cancelada não pode ser editada. Reative-a antes de editar.');
        }

        $payable->update($request->validated());

        return $this->redirectToContext($request)
            ->with('success', 'Conta a pagar atualizada com sucesso.');
    }

    /**
     * POST /modulos/contas-a-pagar/{payable}/pagar
     */
    public function pay(Payable $payable): RedirectResponse
    {
        if ($payable->isCanceled()) {
            return redirect()->back()
                ->with('warning', 'Conta cancelada não pode ser marcada como paga.');
        }

        if ($payable->paid_at !== null) {
            return redirect()->back()
                ->with('warning', 'Conta já paga em '.$payable->paid_at->format('d/m/Y').'.');
        }

        $payable->update(['paid_at' => today()]);

        return redirect()->back()
            ->with('success', 'Conta marcada como paga com sucesso.');
    }

    /**
     * POST /modulos/contas-a-pagar/{payable}/cancelar
     */
    public function cancel(CancelPayableRequest $request, Payable $payable): RedirectResponse
    {
        if ($payable->isCanceled()) {
            return $this->redirectToContext($request)
                ->with('warning', 'Esta conta já está cancelada.');
        }

        if ($payable->paid_at !== null) {
            return $this->redirectToContext($request)
                ->with('warning', 'Conta paga não pode ser cancelada. Reabra a conta limpando a data de pagamento antes de cancelar.');
        }

        $payable->update([
            'canceled_at' => now(),
            'cancel_reason' => $request->validated('cancel_reason'),
        ]);

        return $this->redirectToContext($request)
            ->with('success', 'Conta a pagar cancelada com sucesso.');
    }

    /**
     * POST /modulos/contas-a-pagar/{payable}/reativar
     */
    public function reactivate(Request $request, Payable $payable): RedirectResponse
    {
        if (! $payable->isCanceled()) {
            return $this->redirectToContext($request)
                ->with('warning', 'Esta conta já está ativa.');
        }

        $payable->update([
            'canceled_at' => null,
            'cancel_reason' => null,
        ]);

        return $this->redirectToContext($request)
            ->with('success', 'Conta a pagar reativada com sucesso.');
    }

    private function redirectToContext(Request $request): RedirectResponse
    {
        $context = UpdatePayableRequest::sanitizeContext($request);

        if ($context['origem'] === 'index') {
            return redirect()->route('payables.index');
        }

        return redirect()->route('payables.advanced', UpdatePayableRequest::advancedRouteParams($request));
    }
}
