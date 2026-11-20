<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Models\DailySale;
use App\Models\Invoice;
use App\Models\Payable;
use App\Support\BackupStatus;
use App\Support\PeriodPills;
use Illuminate\Contracts\View\View;

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

        // ── 3. Contas a pagar (Resumo do período em 1 consulta) ─────────────
        $payableSummary = Payable::query()
            ->active()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('
                COUNT(*) as period_count,
                COALESCE(SUM(CAST(ROUND(amount * 100) AS SIGNED)), 0) as total_cents,
                COALESCE(SUM(CASE WHEN paid_at IS NOT NULL THEN CAST(ROUND(amount * 100) AS SIGNED) ELSE 0 END), 0) as paid_cents,
                COALESCE(SUM(CASE WHEN paid_at IS NULL THEN CAST(ROUND(amount * 100) AS SIGNED) ELSE 0 END), 0) as pending_cents
            ')
            ->toBase()
            ->first();

        $payablePeriodCount = (int) ($payableSummary->period_count ?? 0);
        $payableTotalCents = (int) ($payableSummary->total_cents ?? 0);
        $payablePaidCents = (int) ($payableSummary->paid_cents ?? 0);
        $payablePendingCents = (int) ($payableSummary->pending_cents ?? 0);

        // ── 3.1 Contas a pagar (Bloco Atenção: independente do período) ─────
        $overdueSummary = Payable::query()
            ->active()
            ->whereNull('paid_at')
            ->where('due_date', '<', $today->toDateString())
            ->selectRaw('
                COUNT(*) as overdue_count,
                COALESCE(SUM(CAST(ROUND(amount * 100) AS SIGNED)), 0) as overdue_total_cents
            ')
            ->toBase()
            ->first();

        $overdueCount = (int) ($overdueSummary->overdue_count ?? 0);
        $overdueTotalCents = (int) ($overdueSummary->overdue_total_cents ?? 0);

        $in7Days = $today->copy()->addDays(7);
        $upcomingPayables = Payable::query()
            ->active()
            ->whereNull('paid_at')
            ->whereBetween('due_date', [$today->toDateString(), $in7Days->toDateString()])
            ->orderBy('due_date')
            ->orderBy('id')
            ->take(5)
            ->get();

        $backupBanner = BackupStatus::banner($today);

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
            'upcomingPayables',
            'backupBanner'
        ));
    }
}
