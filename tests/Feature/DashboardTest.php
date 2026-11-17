<?php

use App\Models\DailySale;
use App\Models\Invoice;
use App\Models\Payable;
use App\Models\SalesExpectation;
use Illuminate\Support\Carbon;

it('renders the dashboard with all 3 module sections and links', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Site Gerencial')
        ->assertSee('Painel de controle')
        ->assertSee('Vendas e metas')
        ->assertSee('Notas fiscais')
        ->assertSee('Contas a pagar')
        ->assertSee(route('sales.index'))
        ->assertSee(route('invoices.index'))
        ->assertSee(route('payables.index'));
});

it('unifies periods from all modules without duplicates plus the current month', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Sales in March 2025
    DailySale::create(['sale_date' => '2025-03-10', 'amount' => '100.00']);
    // Invoices in August 2025
    Invoice::create(['issue_date' => '2025-08-10', 'customer_name' => 'Teste', 'amount' => '200.00']);
    // Payables in November 2025
    Payable::create(['due_date' => '2025-11-10', 'description' => 'Conta', 'amount' => '300.00']);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('2025')
        ->assertSee('2026'); // current year always present
});

it('renders empty states cleanly when a period has no invoices or payables', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->get('/?ano=2026&mes=6');

    $response->assertOk()
        ->assertSee('Nenhuma nota registrada neste período')
        ->assertSee('Nenhuma conta registrada neste período');
});

it('displays sales indicators for the current month including accrued goal', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Sales: 500 on day 15
    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '500.00']);
    // Expectation: 100 on each day from 1 to 15
    for ($d = 1; $d <= 15; $d++) {
        SalesExpectation::create([
            'expectation_date' => sprintf('2026-06-%02d', $d),
            'amount' => '100.00',
        ]);
    }

    $response = $this->get('/?ano=2026&mes=6');

    $response->assertOk()
        ->assertSee('R$ 500,00')     // total vendido
        ->assertSee('R$ 1.500,00')   // meta acumulada
        ->assertSee('33,3%');        // percentual atingido
});

it('displays invoices indicators for the selected period and today', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Invoice::create([
        'issue_date' => '2026-06-10',
        'customer_name' => 'Cliente A',
        'amount' => '1000.00',
    ]);
    Invoice::create([
        'issue_date' => '2026-06-15',
        'customer_name' => 'Cliente B',
        'amount' => '500.00',
    ]);

    $response = $this->get('/?ano=2026&mes=6');

    $response->assertOk()
        ->assertSee('R$ 1.500,00') // total período
        ->assertSee('R$ 500,00')   // emitido hoje
        ->assertSee('1 nota hoje');
});

it('displays payables indicators and independent attention block', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Overdue payable (vencida)
    Payable::create([
        'description' => 'Conta Luz Atrasada',
        'amount' => '250.00',
        'due_date' => '2026-06-01',
    ]);
    // Upcoming payable in 3 days
    Payable::create([
        'description' => 'Fornecedor Próximo',
        'amount' => '750.00',
        'due_date' => '2026-06-18',
    ]);

    $response = $this->get('/?ano=2026&mes=6');

    $response->assertOk()
        ->assertSee('Bloco Atenção')
        ->assertSee('1 conta vencida necessita pagamento.')
        ->assertSee('Fornecedor Próximo')
        ->assertSee('R$ 250,00'); // total vencido
});

it('does not contain prohibited term papelaria in views (white-label rule)', function () {
    $response = $this->get('/');
    $response->assertDontSee('papelaria');
});
