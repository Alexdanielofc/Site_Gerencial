<?php

use App\Models\Invoice;
use Illuminate\Support\Carbon;

// ── Página principal ────────────────────────────────────────────────────────

it('renders the invoices index page', function () {
    $this->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('Notas fiscais')
        ->assertSee('Registrar nota fiscal');
});

it('shows empty state when no invoices exist today', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('Nenhuma nota registrada hoje');
});

it('lists today invoices with edit links', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $invoice = Invoice::create([
        'issue_date' => '2026-06-15',
        'invoice_number' => 'NF-100',
        'customer_name' => 'Empresa Teste',
        'customer_cnpj' => '12345678000195',
        'amount' => '1500.00',
    ]);

    $this->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('NF-100')
        ->assertSee('Empresa Teste')
        ->assertSee('12.345.678/0001-95')
        ->assertSee('R$ 1.500,00')
        ->assertSee(route('invoices.edit', ['invoice' => $invoice->id, 'origem' => 'index']));
});

// ── Criar e validar notas (Regras N1 a N4) ───────────────────────────────────

it('creates a new invoice (N1: multiple on same day allowed)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'invoice_number' => 'NF-01',
        'customer_name' => 'Cliente Um',
        'amount' => '250,50',
    ])->assertRedirect(route('invoices.index'));

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'invoice_number' => 'NF-02',
        'customer_name' => 'Cliente Dois',
        'amount' => '400,00',
    ])->assertRedirect(route('invoices.index'));

    expect(Invoice::count())->toBe(2);
    expect(Invoice::where('issue_date', '2026-06-15')->count())->toBe(2);
});

it('enforces unique invoice_number when informed (N2)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Invoice::create([
        'issue_date' => '2026-06-15',
        'invoice_number' => 'NF-DUPLICADA',
        'customer_name' => 'Cliente Existente',
        'amount' => '100.00',
    ]);

    $response = $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'invoice_number' => 'NF-DUPLICADA',
        'customer_name' => 'Outro Cliente',
        'amount' => '200,00',
    ]);

    $response->assertSessionHasErrors(['invoice_number']);
    expect(Invoice::count())->toBe(1);
});

it('allows multiple invoices without invoice_number (N2: multiple nulls allowed)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'invoice_number' => '',
        'customer_name' => 'Sem Número 1',
        'amount' => '100,00',
    ])->assertRedirect();

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'invoice_number' => null,
        'customer_name' => 'Sem Número 2',
        'amount' => '200,00',
    ])->assertRedirect();

    expect(Invoice::count())->toBe(2);
});

it('rejects future issue_date (N3)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-16',
        'customer_name' => 'Futuro',
        'amount' => '100,00',
    ]);

    $response->assertSessionHasErrors(['issue_date']);
    expect(Invoice::count())->toBe(0);
});

it('enforces minimum amount 0.01 (N4)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'customer_name' => 'Zero',
        'amount' => '0,00',
    ]);

    $response->assertSessionHasErrors(['amount']);
    expect(Invoice::count())->toBe(0);
});

it('normalizes customer_cnpj by stripping non-digit characters', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'customer_name' => 'Empresa CNPJ',
        'customer_cnpj' => '12.345.678/0001-95',
        'amount' => '150,00',
    ])->assertRedirect();

    $invoice = Invoice::first();
    expect($invoice->customer_cnpj)->toBe('12345678000195');
    expect($invoice->formatted_cnpj)->toBe('12.345.678/0001-95');
});

// ── Editar nota fiscal ──────────────────────────────────────────────────────

it('renders the edit page with pre-filled values', function () {
    $invoice = Invoice::create([
        'issue_date' => '2026-06-10',
        'invoice_number' => 'NF-555',
        'customer_name' => 'Cliente Editar',
        'amount' => '750.00',
    ]);

    $this->get(route('invoices.edit', $invoice->id))
        ->assertOk()
        ->assertSee('Editar nota fiscal')
        ->assertSee('NF-555')
        ->assertSee('Cliente Editar')
        ->assertSee('750,00');
});

it('updates an invoice successfully and allows keeping the same number', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $invoice = Invoice::create([
        'issue_date' => '2026-06-10',
        'invoice_number' => 'NF-555',
        'customer_name' => 'Cliente Original',
        'amount' => '750.00',
    ]);

    $this->put(route('invoices.update', $invoice->id), [
        'issue_date' => '2026-06-10',
        'invoice_number' => 'NF-555', // same number
        'customer_name' => 'Cliente Atualizado',
        'amount' => '900,00',
    ])->assertRedirect();

    $invoice->refresh();
    expect($invoice->customer_name)->toBe('Cliente Atualizado');
    expect($invoice->amount)->toBe('900.00');
});

// ── Área avançada, busca e indicadores ──────────────────────────────────────

it('renders the advanced page with period indicators', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Invoice::create([
        'issue_date' => '2026-06-01',
        'customer_name' => 'Cliente A',
        'amount' => '1000.00',
    ]);
    Invoice::create([
        'issue_date' => '2026-06-15',
        'customer_name' => 'Cliente B',
        'amount' => '2000.00',
    ]);

    $this->get(route('invoices.advanced', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('Notas fiscais — Avançado')
        ->assertSee('R$ 3.000,00') // total
        ->assertSee('2')           // quantidade
        ->assertSee('R$ 1.500,00'); // ticket médio
});

it('filters invoices by search query (customer name or invoice number)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    Invoice::create([
        'issue_date' => '2026-06-01',
        'invoice_number' => 'NF-ALPHA',
        'customer_name' => 'Padaria Central',
        'amount' => '500.00',
    ]);
    Invoice::create([
        'issue_date' => '2026-06-02',
        'invoice_number' => 'NF-BETA',
        'customer_name' => 'Oficina Rápida',
        'amount' => '800.00',
    ]);

    // Search by customer
    $response = $this->get(route('invoices.advanced', ['ano' => 2026, 'mes' => 6, 'busca' => 'Padaria']));
    $response->assertOk()
        ->assertSee('Padaria Central')
        ->assertDontSee('Oficina Rápida');

    // Search by invoice number
    $response = $this->get(route('invoices.advanced', ['ano' => 2026, 'mes' => 6, 'busca' => 'BETA']));
    $response->assertOk()
        ->assertSee('Oficina Rápida')
        ->assertDontSee('Padaria Central');
});

it('renders create page with empty form', function () {
    $this->get(route('invoices.create'))
        ->assertOk()
        ->assertSee('Nova nota fiscal');
});

// ── Idempotência (_submission_id) ───────────────────────────────────────────

it('prevents duplicate invoice creation when submitting the same _submission_id twice', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $payload = [
        'issue_date' => '2026-06-15',
        'customer_name' => 'Cliente Teste',
        'amount' => '250,00',
        '_submission_id' => 'token-invoice-123',
    ];

    $this->post(route('invoices.store'), $payload)
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('success');

    $this->post(route('invoices.store'), $payload)
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('warning', 'Este registro já foi enviado.');

    expect(Invoice::count())->toBe(1);
});

it('creates two invoices when using different _submission_id tokens', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'customer_name' => 'Cliente A',
        'amount' => '100,00',
        '_submission_id' => 'token-a',
    ])->assertSessionHas('success');

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'customer_name' => 'Cliente B',
        'amount' => '200,00',
        '_submission_id' => 'token-b',
    ])->assertSessionHas('success');

    expect(Invoice::count())->toBe(2);
});

it('does not consume _submission_id when invoice validation fails', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Invalid payload (missing customer_name)
    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'customer_name' => '',
        'amount' => '100,00',
        '_submission_id' => 'token-retry-invoice',
    ])->assertSessionHasErrors('customer_name');

    expect(Invoice::count())->toBe(0);

    // Resubmit valid payload with the same token
    $this->post(route('invoices.store'), [
        'issue_date' => '2026-06-15',
        'customer_name' => 'Cliente Corrigido',
        'amount' => '100,00',
        '_submission_id' => 'token-retry-invoice',
    ])->assertSessionHas('success');

    expect(Invoice::count())->toBe(1);
});

// ── Contexto de edição e criação (Item 4) ───────────────────────────────────

it('returns to the same past month and search after editing an invoice', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $invoice = Invoice::create([
        'issue_date' => '2026-04-10',
        'invoice_number' => 'NF-400',
        'customer_name' => 'Cliente Abril',
        'amount' => '300.00',
    ]);

    $this->get(route('invoices.edit', [
        'invoice' => $invoice->id,
        'origem' => 'advanced',
        'ano' => 2026,
        'mes' => 4,
        'busca' => 'Abril',
        'page' => 2,
    ]))
        ->assertOk()
        ->assertSee('name="ano" value="2026"', false)
        ->assertSee('name="mes" value="4"', false)
        ->assertSee('name="busca" value="Abril"', false)
        ->assertSee('name="page" value="2"', false);

    $this->put(route('invoices.update', $invoice->id), [
        'issue_date' => '2026-04-10',
        'invoice_number' => 'NF-400',
        'customer_name' => 'Cliente Abril Editado',
        'amount' => '350,00',
        'origem' => 'advanced',
        'ano' => 2026,
        'mes' => 4,
        'busca' => 'Abril',
        'page' => 2,
    ])->assertRedirect(route('invoices.advanced', [
        'ano' => 2026,
        'mes' => 4,
        'busca' => 'Abril',
        'page' => 2,
    ]));
});

it('discards invalid context parameters and unknown origin without 500 error on invoice update', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $invoice = Invoice::create([
        'issue_date' => '2026-06-10',
        'customer_name' => 'Cliente Seguro',
        'amount' => '100.00',
    ]);

    $this->put(route('invoices.update', $invoice->id), [
        'issue_date' => '2026-06-10',
        'customer_name' => 'Cliente Seguro Atualizado',
        'amount' => '120,00',
        'origem' => 'https://evil.example.com',
        'ano' => 1900,
        'mes' => 99,
        'busca' => str_repeat('x', 150),
        'page' => -5,
    ])->assertRedirect(route('invoices.advanced'));
});

it('redirects to advanced view of the created invoice month when created from create screen', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('invoices.store'), [
        'issue_date' => '2026-03-20',
        'customer_name' => 'Cliente Março',
        'amount' => '500,00',
        'origem' => 'create',
    ])->assertRedirect(route('invoices.advanced', ['ano' => 2026, 'mes' => 3]))
        ->assertSessionHas('success');
});
