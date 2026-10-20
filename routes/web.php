<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'index')->name('site.index');

Route::prefix('modulos')->group(function (): void {
    Route::view('/vendas', 'modules.placeholder', [
        'moduleTitle' => 'Vendas e metas',
        'moduleDescription' => 'Acompanhe as vendas do dia, as expectativas e o progresso das metas.',
    ])->name('sales.index');

    Route::view('/notas-fiscais', 'modules.placeholder', [
        'moduleTitle' => 'Notas fiscais de clientes CNPJ',
        'moduleDescription' => 'Organize as notas emitidas para clientes e acompanhe os pagamentos.',
    ])->name('invoices.index');

    Route::view('/contas-a-pagar', 'modules.placeholder', [
        'moduleTitle' => 'Contas a pagar',
        'moduleDescription' => 'Tenha um espaço para acompanhar compromissos e pagamentos futuros.',
    ])->name('payables.index');
});
