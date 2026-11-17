<?php

use App\Models\Payable;
use Illuminate\Support\Carbon;

// ── Situações calculadas (Regras) ───────────────────────────────────────────

it('calculates status as paga when paid_at is set', function () {
    $payable = new Payable([
        'due_date' => '2026-06-20',
        'paid_at' => '2026-06-10',
    ]);

    expect($payable->status(Carbon::create(2026, 6, 15)))->toBe('paga');
    expect($payable->isPaid())->toBeTrue();
});

it('calculates status as vencida when unpaid and due_date is before today', function () {
    $payable = new Payable([
        'due_date' => '2026-06-10',
        'paid_at' => null,
    ]);

    expect($payable->status(Carbon::create(2026, 6, 15)))->toBe('vencida');
    expect($payable->isOverdue(Carbon::create(2026, 6, 15)))->toBeTrue();
});

it('calculates status as pendente when unpaid and due_date is in the future', function () {
    $payable = new Payable([
        'due_date' => '2026-06-20',
        'paid_at' => null,
    ]);

    expect($payable->status(Carbon::create(2026, 6, 15)))->toBe('pendente');
    expect($payable->isPending(Carbon::create(2026, 6, 15)))->toBeTrue();
});

it('calculates status as pendente when unpaid and due_date is today (vence hoje = pendente)', function () {
    $payable = new Payable([
        'due_date' => '2026-06-15',
        'paid_at' => null,
    ]);

    expect($payable->status(Carbon::create(2026, 6, 15)))->toBe('pendente');
    expect($payable->isPending(Carbon::create(2026, 6, 15)))->toBeTrue();
});

// ── Página principal ────────────────────────────────────────────────────────

it('renders the payables index page', function () {
    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('Contas a pagar')
        ->assertSee('Registrar conta a pagar');
});

it('shows empty state when no payables need attention', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('Nenhuma conta requer atenção no momento');
});

it('lists overdue and next 7 days payables in Em atenção', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Overdue
    Payable::create([
        'description' => 'Conta Vencida',
        'amount' => '100.00',
        'due_date' => '2026-06-10',
    ]);
    // Due today
    Payable::create([
        'description' => 'Conta Hoje',
        'amount' => '200.00',
        'due_date' => '2026-06-15',
    ]);
    // Due in 5 days
    Payable::create([
        'description' => 'Conta em 5 Dias',
        'amount' => '300.00',
        'due_date' => '2026-06-20',
    ]);
    // Due in 15 days (should NOT appear in Em atenção)
    Payable::create([
        'description' => 'Conta Longe',
        'amount' => '400.00',
        'due_date' => '2026-06-30',
    ]);

    $response = $this->get(route('payables.index'));

    $response->assertOk()
        ->assertSee('Conta Vencida')
        ->assertSee('Conta Hoje')
        ->assertSee('Conta em 5 Dias')
        ->assertDontSee('Conta Longe');
});

// ── Criar e validar contas (Regras C1 a C4) ──────────────────────────────────

it('creates a new payable (C1: multiple per day allowed, C4: free due_date)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('payables.store'), [
        'description' => 'Fornecedor A',
        'amount' => '500,00',
        'due_date' => '2026-07-10',
    ])->assertRedirect(route('payables.index'));

    $this->post(route('payables.store'), [
        'description' => 'Fornecedor B',
        'amount' => '300,00',
        'due_date' => '2026-07-10', // same due_date
    ])->assertRedirect(route('payables.index'));

    expect(Payable::count())->toBe(2);
});

it('enforces minimum amount 0.01 (C2)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->post(route('payables.store'), [
        'description' => 'Inválido',
        'amount' => '0,00',
        'due_date' => '2026-06-20',
    ]);

    $response->assertSessionHasErrors(['amount']);
    expect(Payable::count())->toBe(0);
});

it('rejects future paid_at (C3)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->post(route('payables.store'), [
        'description' => 'Conta',
        'amount' => '100,00',
        'due_date' => '2026-06-20',
        'paid_at' => '2026-06-25', // future
    ]);

    $response->assertSessionHasErrors(['paid_at']);
    expect(Payable::count())->toBe(0);
});

// ── Marcar como paga (payables.pay) e reabrir (C5) ───────────────────────────

it('marks an open payable as paid with today date', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Aluguel',
        'amount' => '1200.00',
        'due_date' => '2026-06-15',
    ]);

    $this->post(route('payables.pay', $payable->id))
        ->assertRedirect();

    $payable->refresh();
    expect($payable->paid_at->toDateString())->toBe('2026-06-15');
    expect($payable->isPaid())->toBeTrue();
});

it('does not alter already paid payable and shows warning', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Aluguel Pago',
        'amount' => '1200.00',
        'due_date' => '2026-06-10',
        'paid_at' => '2026-06-08',
    ]);

    $response = $this->post(route('payables.pay', $payable->id));
    $response->assertRedirect();

    $payable->refresh();
    expect($payable->paid_at->toDateString())->toBe('2026-06-08'); // did not change
});

it('allows reopening a paid payable by clearing paid_at on update (C5)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Conta Paga',
        'amount' => '450.00',
        'due_date' => '2026-06-15',
        'paid_at' => '2026-06-12',
    ]);

    $this->put(route('payables.update', $payable->id), [
        'description' => 'Conta Reaberta',
        'amount' => '450,00',
        'due_date' => '2026-06-15',
        'paid_at' => '', // clear paid_at to reopen
    ])->assertRedirect();

    $payable->refresh();
    expect($payable->paid_at)->toBeNull();
    expect($payable->isPaid())->toBeFalse();
});

// ── Área avançada e filtros ─────────────────────────────────────────────────

it('renders the advanced page with period cards', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Payable::create([
        'description' => 'Conta Paga',
        'amount' => '500.00',
        'due_date' => '2026-06-05',
        'paid_at' => '2026-06-05',
    ]);
    Payable::create([
        'description' => 'Conta Vencida',
        'amount' => '300.00',
        'due_date' => '2026-06-10',
    ]);
    Payable::create([
        'description' => 'Conta Pendente',
        'amount' => '200.00',
        'due_date' => '2026-06-25',
    ]);

    $this->get(route('payables.advanced', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('Contas a pagar — Avançado')
        ->assertSee('R$ 1.000,00') // total
        ->assertSee('R$ 500,00')   // pago
        ->assertSee('R$ 300,00')   // vencido
        ->assertSee('R$ 200,00');  // pendente
});

it('filters payables by situation (vencidas, pagas, pendentes)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Payable::create([
        'description' => 'Conta Alpha',
        'amount' => '100.00',
        'due_date' => '2026-06-05',
        'paid_at' => '2026-06-05',
    ]);
    Payable::create([
        'description' => 'Conta Beta',
        'amount' => '200.00',
        'due_date' => '2026-06-10',
    ]);

    // Only pagas
    $this->get(route('payables.advanced', ['ano' => 2026, 'mes' => 6, 'situacao' => 'pagas']))
        ->assertOk()
        ->assertSee('Conta Alpha')
        ->assertDontSee('Conta Beta');

    // Only vencidas
    $this->get(route('payables.advanced', ['ano' => 2026, 'mes' => 6, 'situacao' => 'vencidas']))
        ->assertOk()
        ->assertSee('Conta Beta')
        ->assertDontSee('Conta Alpha');
});

it('filters payables by description search', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Payable::create([
        'description' => 'Energia Elétrica',
        'amount' => '300.00',
        'due_date' => '2026-06-20',
    ]);
    Payable::create([
        'description' => 'Fornecedor Gráfico',
        'amount' => '800.00',
        'due_date' => '2026-06-20',
    ]);

    $response = $this->get(route('payables.advanced', ['ano' => 2026, 'mes' => 6, 'busca' => 'Energia']));
    $response->assertOk()
        ->assertSee('Energia Elétrica')
        ->assertDontSee('Fornecedor Gráfico');
});

it('renders create page with form', function () {
    $this->get(route('payables.create'))
        ->assertOk()
        ->assertSee('Nova conta a pagar');
});
