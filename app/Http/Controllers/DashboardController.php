<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Models\DailySale;
use App\Models\Invoice;
use App\Models\Payable;
use App\Support\PeriodPills;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(PeriodRequest $request): View
    {
        $today = today();
        $monthStart = $request->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $isCurrentMonth = $monthStart->year === $today->year && $monthStart->month === $today->month;

        // Periods with records from ANY module + current month
        $recordPeriods = DailySale::periodsWithRecords()
            ->concat(Invoice::periodsWithRecords())
            ->concat(Payable::periodsWithRecords());

        ['years' => $years, 'months' => $months] = PeriodPills::build($recordPeriods, $monthStart, $today);

        // ── 1. Vendas e metas ───────────────────────────────────────────────
        $salesIndicators = DailySale::indicators($monthStart, $today);

        // ── 2. Notas fiscais ────────────────────────────────────────────────
        $invoiceCount = Invoice::countBetween($monthStart, $monthEnd);
        $invoiceTotalCents = Invoice::totalCentsBetween($monthStart, $monthEnd);
        $todayInvoiceCount = $isCurrentMonth ? Invoice::countOn($today) : 0;
        $todayInvoiceTotalCents = $isCurrentMonth ? Invoice::centsOn($today) : 0;

        // ── 3. Contas a pagar (Resumo do período) ───────────────────────────
        $payableTotalCents = (int) Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum(DB::raw('amount * 100'));

        $payablePaidCents = (int) Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNotNull('paid_at')
            ->sum(DB::raw('amount * 100'));

        $payablePendingCents = (int) Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNull('paid_at')
            ->sum(DB::raw('amount * 100'));

        $payablePeriodCount = Payable::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();

        // ── 3.1 Contas a pagar (Bloco Atenção: independente do período) ─────
        $overdueQuery = Payable::query()
            ->whereNull('paid_at')
            ->where('due_date', '<', $today->toDateString());

        $overdueCount = $overdueQuery->count();
        $overdueTotalCents = (int) $overdueQuery->sum(DB::raw('amount * 100'));

        $in7Days = $today->copy()->addDays(7);
        $upcomingPayables = Payable::query()
            ->whereNull('paid_at')
            ->whereBetween('due_date', [$today->toDateString(), $in7Days->toDateString()])
            ->orderBy('due_date')
            ->orderBy('id')
            ->take(5)
            ->get();

        return view('index', compact(
            'today',
            'monthStart',
            'isCurrentMonth',
            'years',
            'months',
            'salesIndicators',
            'invoiceCount',
            'invoiceTotalCents',
            'todayInvoiceCount',
            'todayInvoiceTotalCents',
            'payableTotalCents',
            'payablePaidCents',
            'payablePendingCents',
            'payablePeriodCount',
            'overdueCount',
            'overdueTotalCents',
            'upcomingPayables'
        ));
    }
}
