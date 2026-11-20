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

// ── Idempotência (_submission_id) ───────────────────────────────────────────

it('prevents duplicate payable creation when submitting the same _submission_id twice', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payload = [
        'description' => 'Energia Elétrica',
        'amount' => '350,00',
        'due_date' => '2026-06-20',
        '_submission_id' => 'token-payable-123',
    ];

    $this->post(route('payables.store'), $payload)
        ->assertRedirect(route('payables.index'))
        ->assertSessionHas('success');

    $this->post(route('payables.store'), $payload)
        ->assertRedirect(route('payables.index'))
        ->assertSessionHas('warning', 'Este registro já foi enviado.');

    expect(Payable::count())->toBe(1);
});

it('creates two payables when using different _submission_id tokens', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('payables.store'), [
        'description' => 'Conta A',
        'amount' => '100,00',
        'due_date' => '2026-06-20',
        '_submission_id' => 'token-pay-a',
    ])->assertSessionHas('success');

    $this->post(route('payables.store'), [
        'description' => 'Conta B',
        'amount' => '200,00',
        'due_date' => '2026-06-20',
        '_submission_id' => 'token-pay-b',
    ])->assertSessionHas('success');

    expect(Payable::count())->toBe(2);
});

it('does not consume _submission_id when payable validation fails', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('payables.store'), [
        'description' => '',
        'amount' => '100,00',
        'due_date' => '2026-06-20',
        '_submission_id' => 'token-retry-payable',
    ])->assertSessionHasErrors('description');

    expect(Payable::count())->toBe(0);

    $this->post(route('payables.store'), [
        'description' => 'Conta Corrigida',
        'amount' => '100,00',
        'due_date' => '2026-06-20',
        '_submission_id' => 'token-retry-payable',
    ])->assertSessionHas('success');

    expect(Payable::count())->toBe(1);
});

// ── Contexto de edição e criação (Item 4) ───────────────────────────────────

it('returns to the same past month, status filter, search and page after editing a payable', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Aluguel Abril',
        'amount' => '1200.00',
        'due_date' => '2026-04-10',
    ]);

    $this->get(route('payables.edit', [
        'payable' => $payable->id,
        'origem' => 'advanced',
        'ano' => 2026,
        'mes' => 4,
        'situacao' => 'vencidas',
        'busca' => 'Aluguel',
        'page' => 3,
    ]))
        ->assertOk()
        ->assertSee('name="ano" value="2026"', false)
        ->assertSee('name="mes" value="4"', false)
        ->assertSee('name="situacao" value="vencidas"', false)
        ->assertSee('name="busca" value="Aluguel"', false)
        ->assertSee('name="page" value="3"', false);

    $this->put(route('payables.update', $payable->id), [
        'description' => 'Aluguel Abril Editado',
        'amount' => '1250,00',
        'due_date' => '2026-04-10',
        'origem' => 'advanced',
        'ano' => 2026,
        'mes' => 4,
        'situacao' => 'vencidas',
        'busca' => 'Aluguel',
        'page' => 3,
    ])->assertRedirect(route('payables.advanced', [
        'ano' => 2026,
        'mes' => 4,
        'busca' => 'Aluguel',
        'situacao' => 'vencidas',
        'page' => 3,
    ]));
});

it('discards invalid context parameters and unknown origin without 500 error on payable update', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Conta Segura',
        'amount' => '100.00',
        'due_date' => '2026-06-20',
    ]);

    $this->put(route('payables.update', $payable->id), [
        'description' => 'Conta Segura Atualizada',
        'amount' => '110,00',
        'due_date' => '2026-06-20',
        'origem' => '//attacker.tld',
        'ano' => 3000,
        'mes' => 0,
        'situacao' => 'inexistente',
        'page' => 0,
    ])->assertRedirect(route('payables.advanced'));
});

it('redirects to advanced view of the created payable month when created from create screen', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('payables.store'), [
        'description' => 'Seguro Anual',
        'amount' => '800,00',
        'due_date' => '2026-08-10',
        'origem' => 'create',
    ])->assertRedirect(route('payables.advanced', ['ano' => 2026, 'mes' => 8]))
        ->assertSessionHas('success');
});

// ── Status cancelada em Contas a Pagar (Item 8) ─────────────────────────────

it('gives cancelada priority over paga, vencida and pendente in Payable::status', function () {
    $today = Carbon::create(2026, 6, 15);

    $canceledOverdue = new Payable([
        'due_date' => '2026-06-01',
        'paid_at' => null,
        'canceled_at' => now(),
    ]);
    expect($canceledOverdue->status($today))->toBe('cancelada');

    $canceledPaid = new Payable([
        'due_date' => '2026-06-01',
        'paid_at' => '2026-06-01',
        'canceled_at' => now(),
    ]);
    expect($canceledPaid->status($today))->toBe('cancelada');
    expect($canceledPaid->isPaid())->toBeFalse();
});

it('excludes canceled payables from totals, attention list, overdue, upcoming and dashboard', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Payable::create([
        'description' => 'Conta Ativa Vencida',
        'amount' => '200.00',
        'due_date' => '2026-06-10',
    ]);
    Payable::create([
        'description' => 'Conta Cancelada Vencida',
        'amount' => '900.00',
        'due_date' => '2026-06-10',
        'canceled_at' => now(),
        'cancel_reason' => 'Cobrança indevida',
    ]);
    Payable::create([
        'description' => 'Conta Cancelada Próxima',
        'amount' => '500.00',
        'due_date' => '2026-06-18',
        'canceled_at' => now(),
    ]);

    // Index attention list only has the active overdue payable
    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('Conta Ativa Vencida')
        ->assertDontSee('Conta Cancelada Vencida')
        ->assertDontSee('Conta Cancelada Próxima')
        ->assertSee('R$ 200,00')
        ->assertDontSee('R$ 1.100,00')
        ->assertSee('data-confirm=', false);

    // Advanced lists both, but period totals only count active (R$ 200,00)
    $this->get(route('payables.advanced', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('Conta Ativa Vencida')
        ->assertSee('Conta Cancelada Vencida')
        ->assertSee('Cobrança indevida')
        ->assertSee('Cancelada')
        ->assertSee('R$ 200,00')
        ->assertDontSee('R$ 1.600,00');

    // Dashboard excludes canceled from period summary, overdue and upcoming
    $this->get(route('site.index', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('1 conta vencida necessita pagamento.')
        ->assertDontSee('Conta Cancelada Próxima');
});

it('cancels and reactivates an open payable while preserving context', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Internet Abril',
        'amount' => '150.00',
        'due_date' => '2026-04-20',
    ]);

    $this->post(route('payables.cancel', $payable->id), [
        'cancel_reason' => 'Contrato encerrado',
        'origem' => 'advanced',
        'ano' => 2026,
        'mes' => 4,
    ])->assertRedirect(route('payables.advanced', ['ano' => 2026, 'mes' => 4]))
        ->assertSessionHas('success');

    $payable->refresh();
    expect($payable->isCanceled())->toBeTrue();
    expect($payable->cancel_reason)->toBe('Contrato encerrado');

    $this->post(route('payables.reactivate', $payable->id), [
        'origem' => 'advanced',
        'ano' => 2026,
        'mes' => 4,
    ])->assertRedirect(route('payables.advanced', ['ano' => 2026, 'mes' => 4]))
        ->assertSessionHas('success');

    $payable->refresh();
    expect($payable->isCanceled())->toBeFalse();
    expect($payable->cancel_reason)->toBeNull();
});

it('refuses to cancel a paid payable and refuses to pay a canceled payable', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $paid = Payable::create([
        'description' => 'Conta Já Paga',
        'amount' => '100.00',
        'due_date' => '2026-06-10',
        'paid_at' => '2026-06-10',
    ]);

    $this->post(route('payables.cancel', $paid->id), ['origem' => 'index'])
        ->assertRedirect(route('payables.index'))
        ->assertSessionHas('warning');

    expect($paid->fresh()->isCanceled())->toBeFalse();

    $canceled = Payable::create([
        'description' => 'Conta Cancelada',
        'amount' => '100.00',
        'due_date' => '2026-06-10',
        'canceled_at' => now(),
    ]);

    $this->from(route('payables.advanced'))
        ->post(route('payables.pay', $canceled->id))
        ->assertRedirect(route('payables.advanced'))
        ->assertSessionHas('warning');

    expect($canceled->fresh()->paid_at)->toBeNull();
});

it('blocks editing a canceled payable and shows reactivate alert on edit screen', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payable = Payable::create([
        'description' => 'Conta Bloqueada',
        'amount' => '220.00',
        'due_date' => '2026-06-20',
        'canceled_at' => now(),
    ]);

    $this->get(route('payables.edit', $payable->id))
        ->assertOk()
        ->assertSee('Esta conta a pagar está cancelada e não pode ser editada.')
        ->assertSee('Reativar conta');

    $this->put(route('payables.update', $payable->id), [
        'description' => 'Tentativa Alteração',
        'amount' => '500,00',
        'due_date' => '2026-06-20',
    ])->assertSessionHas('warning');

    expect($payable->fresh()->description)->toBe('Conta Bloqueada');
});

it('includes periods that only have canceled payables in periodsWithRecords', function () {
    Payable::create([
        'description' => 'Conta Janeiro 2025',
        'amount' => '100.00',
        'due_date' => '2025-01-15',
        'canceled_at' => now(),
    ]);

    $periods = Payable::periodsWithRecords();
    expect($periods->all())->toContain(['year' => 2025, 'month' => 1]);
});
