<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SalesExpectationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('site.index');

Route::prefix('modulos')->group(function (): void {

    // ── Vendas e metas ──────────────────────────────────────────────────────
    Route::prefix('vendas')->group(function (): void {
        // Estáticas antes das dinâmicas
        Route::get('/avancado', [SalesController::class, 'advanced'])->name('sales.advanced');
        Route::get('/expectativas', [SalesExpectationController::class, 'edit'])->name('sales.expectations.edit');
        Route::put('/expectativas', [SalesExpectationController::class, 'update'])->name('sales.expectations.update');

        // Dinâmicas
        Route::get('/', [SalesController::class, 'index'])->name('sales.index');
        Route::post('/', [SalesController::class, 'store'])->name('sales.store');
        Route::get('/dia/{date}/editar', [SalesController::class, 'edit'])->name('sales.edit')
            ->where('date', '\d{4}-\d{2}-\d{2}');
        Route::put('/dia/{date}', [SalesController::class, 'update'])->name('sales.update')
            ->where('date', '\d{4}-\d{2}-\d{2}');
    });

    // ── Notas fiscais ──────────────────────────────────────────────────────
    Route::prefix('notas-fiscais')->group(function (): void {
        // Estáticas antes das dinâmicas
        Route::get('/avancado', [InvoiceController::class, 'advanced'])->name('invoices.advanced');
        Route::get('/nova', [InvoiceController::class, 'create'])->name('invoices.create');

        // Dinâmicas e principais
        Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/{invoice}/editar', [InvoiceController::class, 'edit'])->name('invoices.edit')
            ->whereNumber('invoice');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update')
            ->whereNumber('invoice');
    });

    // ── Contas a pagar ─────────────────────────────────────────────────────
    Route::prefix('contas-a-pagar')->group(function (): void {
        // Estáticas antes das dinâmicas
        Route::get('/avancado', [PayableController::class, 'advanced'])->name('payables.advanced');
        Route::get('/nova', [PayableController::class, 'create'])->name('payables.create');

        // Dinâmicas e principais
        Route::get('/', [PayableController::class, 'index'])->name('payables.index');
        Route::post('/', [PayableController::class, 'store'])->name('payables.store');
        Route::get('/{payable}/editar', [PayableController::class, 'edit'])->name('payables.edit')
            ->whereNumber('payable');
        Route::put('/{payable}', [PayableController::class, 'update'])->name('payables.update')
            ->whereNumber('payable');
        Route::post('/{payable}/pagar', [PayableController::class, 'pay'])->name('payables.pay')
            ->whereNumber('payable');
    });
});
