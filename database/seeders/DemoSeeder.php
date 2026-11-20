<?php

namespace Database\Seeders;

use App\Models\DailySale;
use App\Models\Invoice;
use App\Models\Payable;
use App\Models\SalesExpectation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $dbName = (string) DB::connection()->getDatabaseName();

        if (! str_ends_with($dbName, '_demo') && ! str_ends_with($dbName, '_test')) {
            throw new \RuntimeException(
                "Recusado: DemoSeeder só pode ser executado em bancos terminados em '_demo' (atual: '{$dbName}')."
            );
        }

        mt_srand(20261118);

        $today = today();
        $now = now();

        DB::transaction(function () use ($today, $now): void {
            DailySale::query()->delete();
            SalesExpectation::query()->delete();
            Invoice::query()->delete();
            Payable::query()->delete();

            // 3 previous months + current month + next month (for expectations)
            $monthOffsets = [-3, -2, -1, 0, 1];
            $expectationsRows = [];
            $salesRows = [];
            $invoiceRows = [];
            $payableRows = [];

            $invoiceSeq = 1;

            foreach ($monthOffsets as $offset) {
                $monthStart = $today->copy()->startOfMonth()->addMonthsNoOverflow($offset);
                $daysInMonth = $monthStart->daysInMonth;

                // 1) Expectations (all 5 months) and Daily Sales (only up to yesterday)
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $date = Carbon::create($monthStart->year, $monthStart->month, $d);
                    $dateStr = $date->toDateString();
                    $isSunday = $date->isSunday();
                    $isSaturday = $date->isSaturday();

                    $expectedAmount = $isSunday
                        ? '0.00'
                        : ($isSaturday
                            ? number_format(mt_rand(90000, 140000) / 100, 2, '.', '')
                            : number_format(mt_rand(130000, 220000) / 100, 2, '.', ''));

                    $expectationsRows[] = [
                        'expectation_date' => $dateStr,
                        'amount' => $expectedAmount,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Sales: Monday to Saturday, strictly before today (TODAY has no sale)
                    if (! $isSunday && $date->lt($today)) {
                        $saleCents = $isSaturday
                            ? mt_rand(85000, 155000)
                            : mt_rand(120000, 240000);

                        $salesRows[] = [
                            'sale_date' => $dateStr,
                            'amount' => number_format($saleCents / 100, 2, '.', ''),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                // Skip invoices and payables for the next month (+1)
                if ($offset > 0) {
                    continue;
                }

                // 2) Invoices: 12 to 25 per month (only dates <= today), at least 1 canceled
                $maxIssueDay = $offset === 0 ? max(1, $today->day) : $daysInMonth;
                $invoiceCount = mt_rand(12, 18);

                for ($i = 1; $i <= $invoiceCount; $i++) {
                    $issueDay = mt_rand(1, $maxIssueDay);
                    $issueDate = Carbon::create($monthStart->year, $monthStart->month, $issueDay);
                    $customerIdx = (($invoiceSeq - 1) % 15) + 1;
                    $isCanceled = ($i === $invoiceCount);

                    $invoiceRows[] = [
                        'issue_date' => $issueDate->toDateString(),
                        'invoice_number' => sprintf('NF-DEMO-%04d', $invoiceSeq),
                        'customer_name' => sprintf('Cliente Exemplo %02d Ltda', $customerIdx),
                        'customer_cnpj' => sprintf('%02d1234560001%02d', $customerIdx, ($customerIdx * 3) % 90 + 10),
                        'amount' => number_format(mt_rand(18000, 350000) / 100, 2, '.', ''),
                        'notes' => $i % 4 === 0 ? 'Faturamento demonstrativo referente ao período.' : null,
                        'canceled_at' => $isCanceled ? $now : null,
                        'cancel_reason' => $isCanceled ? 'Emissão de teste cancelada para demonstração' : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $invoiceSeq++;
                }

                // 3) Payables: recurring generic expenses with mix of paid, pending, overdue and 1 canceled
                $templates = [
                    ['day' => 5, 'desc' => 'Aluguel', 'cents' => 280000],
                    ['day' => 10, 'desc' => 'Energia elétrica', 'cents' => mt_rand(42000, 58000)],
                    ['day' => 12, 'desc' => 'Água e saneamento', 'cents' => mt_rand(14000, 22000)],
                    ['day' => 15, 'desc' => 'Internet e telefonia', 'cents' => 19990],
                    ['day' => 18, 'desc' => 'Fornecedor A', 'cents' => mt_rand(95000, 185000)],
                    ['day' => 22, 'desc' => 'Fornecedor B', 'cents' => mt_rand(75000, 145000)],
                    ['day' => min($daysInMonth, 25), 'desc' => 'Serviços de manutenção', 'cents' => 35000],
                ];

                foreach ($templates as $idx => $tpl) {
                    $dueDay = min($daysInMonth, $tpl['day']);
                    $dueDate = Carbon::create($monthStart->year, $monthStart->month, $dueDay);
                    $isCanceled = ($idx === 6);
                    $paidAt = null;

                    if (! $isCanceled) {
                        if ($offset < 0) {
                            // Past months: mostly paid, leave 1 overdue in the immediate previous month
                            $leaveOverdue = ($offset === -1 && $idx === 5);
                            if (! $leaveOverdue) {
                                $paidAt = $dueDate->lte($today) ? $dueDate->toDateString() : $today->toDateString();
                            }
                        } else {
                            // Current month: pay the first 2 if due_date <= today, leave others pending/overdue
                            if ($idx < 2 && $dueDate->lte($today)) {
                                $paidAt = $dueDate->toDateString();
                            }
                        }
                    }

                    $payableRows[] = [
                        'description' => $tpl['desc'],
                        'amount' => number_format($tpl['cents'] / 100, 2, '.', ''),
                        'due_date' => $dueDate->toDateString(),
                        'paid_at' => $paidAt,
                        'notes' => null,
                        'canceled_at' => $isCanceled ? $now : null,
                        'cancel_reason' => $isCanceled ? 'Lançamento substituído na demonstração' : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Guarantee at least 1 overdue and 1 upcoming payable in the current month window
            $overdueDate = $today->copy()->subDays(2)->toDateString();
            $upcomingDate = $today->copy()->addDays(3)->toDateString();

            $payableRows[] = [
                'description' => 'Fornecedor A — Parcela complementar',
                'amount' => '640.00',
                'due_date' => $overdueDate,
                'paid_at' => null,
                'notes' => 'Compromisso demonstrativo em aberto.',
                'canceled_at' => null,
                'cancel_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $payableRows[] = [
                'description' => 'Fornecedor B — Remessa programada',
                'amount' => '890.00',
                'due_date' => $upcomingDate,
                'paid_at' => null,
                'notes' => 'Vencimento nos próximos 7 dias.',
                'canceled_at' => null,
                'cancel_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            SalesExpectation::insert($expectationsRows);
            DailySale::insert($salesRows);
            Invoice::insert($invoiceRows);
            Payable::insert($payableRows);
        });
    }
}
