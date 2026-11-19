<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Http\Requests\StorePayableRequest;
use App\Http\Requests\UpdatePayableRequest;
use App\Models\Payable;
use App\Support\SubmissionGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayableController extends Controller
{
    /**
     * GET /modulos/contas-a-pagar
     */
    public function index(): View
    {
        $today = today();
        $in7Days = $today->copy()->addDays(7);

        // List "Em atenção": unpaid overdue OR unpaid due within 7 days
        $attentionPayables = Payable::query()
            ->whereNull('paid_at')
            ->where('due_date', '<=', $in7Days->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        // Indicador: Total em aberto (todas as contas não pagas)
        $totalOpenCents = (int) Payable::query()
            ->whereNull('paid_at')
            ->sum(DB::raw('amount * 100'));

        // Indicador: Vencidas (em aberto e vencimento < hoje)
        $overdueQuery = Payable::query()
            ->whereNull('paid_at')
            ->where('due_date', '<', $today->toDateString());

        $overdueCount = $overdueQuery->count();
        $overdueTotalCents = (int) $overdueQuery->sum(DB::raw('amount * 100'));

        // Indicador: A vencer em até 7 dias (em aberto e hoje <= due_date <= hoje+7)
        $upcomingQuery = Payable::query()
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
        if (! SubmissionGuard::firstTime($request)) {
            return redirect()->route('payables.index')
                ->with('warning', 'Este registro já foi enviado.');
        }

        Payable::create($request->validated());

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
        $monthsByYear = Payable::periodsWithRecords()
            ->push(['year' => $today->year, 'month' => $today->month])
            ->unique(fn (array $p) => $p['year'].'-'.$p['month'])
            ->groupBy('year')
            ->map(fn (Collection $periods) => $periods->pluck('month')->sort()->values())
            ->sortKeys();

        $years = $monthsByYear->map(function (Collection $months, int $year) use ($monthStart) {
            return [
                'year' => $year,
                'month' => match (true) {
                    $year === $monthStart->year => $monthStart->month,
                    $year === today()->year => today()->month,
                    $months->contains($monthStart->month) => $monthStart->month,
                    default => $months->last(),
                },
            ];
        })->values();

        $months = $monthsByYear->get($monthStart->year, collect())->map(fn (int $month) => [
            'month' => $month,
            'label' => Str::ucfirst(Carbon::create(2000, $month, 1)->translatedFormat('F')),
        ]);

        $statusFilter = (string) $request->input('situacao', 'todas');
        $search = trim((string) $request->input('busca', ''));

        // Period Totals in R$
        $periodTotalCents = (int) Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum(DB::raw('amount * 100'));

        $periodPaidCents = (int) Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNotNull('paid_at')
            ->sum(DB::raw('amount * 100'));

        $periodPendingCents = (int) Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNull('paid_at')
            ->where('due_date', '>=', $today->toDateString())
            ->sum(DB::raw('amount * 100'));

        $periodOverdueCents = (int) Payable::query()
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
            'pagas' => $query->whereNotNull('paid_at'),
            'vencidas' => $query->whereNull('paid_at')->where('due_date', '<', $today->toDateString()),
            'pendentes' => $query->whereNull('paid_at')->where('due_date', '>=', $today->toDateString()),
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
        $origin = $request->input('origem', 'advanced');

        return view('modules.payables.edit', compact('payable', 'today', 'origin'));
    }

    /**
     * PUT /modulos/contas-a-pagar/{payable}
     */
    public function update(UpdatePayableRequest $request, Payable $payable): RedirectResponse
    {
        $payable->update($request->validated());

        $origin = $request->input('origem', 'advanced');

        if ($origin === 'index') {
            return redirect()->route('payables.index')
                ->with('success', 'Conta a pagar atualizada com sucesso.');
        }

        return redirect()->route('payables.advanced')
            ->with('success', 'Conta a pagar atualizada com sucesso.');
    }

    /**
     * POST /modulos/contas-a-pagar/{payable}/pagar
     */
    public function pay(Payable $payable): RedirectResponse
    {
        if ($payable->paid_at !== null) {
            return redirect()->back()
                ->with('warning', 'Conta já paga em '.$payable->paid_at->format('d/m/Y').'.');
        }

        $payable->update(['paid_at' => today()]);

        return redirect()->back()
            ->with('success', 'Conta marcada como paga com sucesso.');
    }
}
