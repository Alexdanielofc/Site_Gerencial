<?php

use App\Models\DailySale;
use App\Models\Invoice;
use App\Models\Payable;
use App\Models\SalesExpectation;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Carbon;

it('refuses to run demo:seed on non-_demo databases including site_gerencial_test', function () {
    $this->artisan('demo:seed --yes')
        ->expectsOutputToContain("Operação recusada por segurança: o comando demo:seed só pode rodar em bancos cujo nome termine em '_demo'")
        ->assertExitCode(1);

    expect(DailySale::count())->toBe(0);
    expect(Invoice::count())->toBe(0);
    expect(Payable::count())->toBe(0);
});

it('generates expected deterministic demo data volumes without today or future sales and does not duplicate on second run', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $seeder = new DemoSeeder;
    $seeder->run();

    $todayStr = Carbon::create(2026, 6, 15)->toDateString();

    // No sale today and no future sales
    expect(DailySale::where('sale_date', '>=', $todayStr)->count())->toBe(0);
    expect(DailySale::count())->toBeGreaterThan(60);

    // Expectations cover 5 months (3 past + current + next) and Sundays are 0.00
    $expectationsCount = SalesExpectation::count();
    expect($expectationsCount)->toBeGreaterThanOrEqual(150);

    // Invoices: 4 months * (12..18) = 48..72, with at least 4 canceled
    $invoicesCount = Invoice::count();
    expect($invoicesCount)->toBeBetween(48, 100);
    expect(Invoice::canceled()->count())->toBeGreaterThanOrEqual(4);

    // Payables: mix of paid, pending, overdue and canceled
    $payablesCount = Payable::count();
    expect($payablesCount)->toBeGreaterThanOrEqual(25);
    expect(Payable::canceled()->count())->toBeGreaterThanOrEqual(4);
    expect(Payable::active()->whereNotNull('paid_at')->count())->toBeGreaterThan(0);
    expect(Payable::active()->whereNull('paid_at')->where('due_date', '<', $todayStr)->count())->toBeGreaterThan(0);
    expect(Payable::active()->whereNull('paid_at')->where('due_date', '>=', $todayStr)->count())->toBeGreaterThan(0);

    // Running a second time does not duplicate rows
    $seeder->run();

    expect(DailySale::count())->toBeGreaterThan(60);
    expect(SalesExpectation::count())->toBe($expectationsCount);
    expect(Invoice::count())->toBe($invoicesCount);
    expect(Payable::count())->toBe($payablesCount);
});
