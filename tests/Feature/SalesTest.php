<?php

use App\Models\DailySale;
use App\Models\SalesExpectation;
use Illuminate\Support\Carbon;

// ── Página principal ────────────────────────────────────────────────────────

it('renders the sales index page', function () {
    $this->get(route('sales.index'))->assertOk()->assertSee('Vendas e metas');
});

it('shows the form when there is no sale today', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertSee('Venda diária', false);
});

it('shows the already-added alert when there is a sale today', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '500.00']);

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertSee('Registro de hoje já adicionado.', false)
        ->assertSee('R$ 500,00')
        ->assertSee('EDITAR');
});

it('shows DIA SEM EXPECTATIVA alert when expectation is missing', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertSee('DIA SEM EXPECTATIVA', false);
});

it('shows DIA SEM EXPECTATIVA alert when expectation is zero', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    SalesExpectation::create(['expectation_date' => '2026-06-15', 'amount' => '0.00']);

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertSee('DIA SEM EXPECTATIVA', false);
});

it('shows the expectation value when it is set and greater than zero', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    SalesExpectation::create(['expectation_date' => '2026-06-15', 'amount' => '1200.00']);

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertSee('R$ 1.200,00')
        ->assertDontSee('DIA SEM EXPECTATIVA', false);
});

// ── Registrar venda ─────────────────────────────────────────────────────────

it('registers a sale for today', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('sales.store'), ['amount' => '1500,00'])
        ->assertRedirect(route('sales.index'));

    expect(DailySale::where('sale_date', '2026-06-15')->first()->amount)->toBe('1500.00');
});

it('does not duplicate a sale for today and redirects with message', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '1000.00']);

    $this->post(route('sales.store'), ['amount' => '500,00'])
        ->assertRedirect(route('sales.index'));

    // Amount did not change, only 1 record
    expect(DailySale::where('sale_date', '2026-06-15')->count())->toBe(1);
    expect(DailySale::where('sale_date', '2026-06-15')->first()->amount)->toBe('1000.00');

    $this->get(route('sales.index'))->assertSee('Registro de hoje já adicionado.', false);
});

it('rejects a future date via store', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Store always uses today, so POST can't create a future date — test validation instead
    $this->post(route('sales.store'), ['amount' => 'texto_invalido'])
        ->assertRedirect();

    expect(DailySale::count())->toBe(0);
});

it('rejects an invalid amount on store', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('sales.store'), ['amount' => 'abc'])
        ->assertSessionHasErrors(['amount']);
});

// ── Formato de valores ──────────────────────────────────────────────────────

it('accepts amount in format 1.234,56', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('sales.store'), ['amount' => '1.234,56'])
        ->assertRedirect(route('sales.index'));

    expect(DailySale::first()->amount)->toBe('1234.56');
});

it('accepts amount in format 1234.5', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('sales.store'), ['amount' => '1234.5'])
        ->assertRedirect(route('sales.index'));

    expect(DailySale::first()->amount)->toBe('1234.50');
});

it('accepts zero amount', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->post(route('sales.store'), ['amount' => '0,00'])
        ->assertRedirect(route('sales.index'));

    expect(DailySale::first()->amount)->toBe('0.00');
});

// ── Editar venda ────────────────────────────────────────────────────────────

it('renders the edit page for an existing sale', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '800.00']);

    $this->get(route('sales.edit', ['date' => '2026-06-15']))
        ->assertOk()
        ->assertSee('Editar venda')
        ->assertSee('15/06/2026');
});

it('renders the edit page for a day without a sale (create via edit)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.edit', ['date' => '2026-06-10']))
        ->assertOk()
        ->assertSee('Editar venda');
});

it('returns 404 for a future date on edit', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.edit', ['date' => '2026-06-20']))
        ->assertNotFound();
});

it('returns 404 for an invalid date on edit', function () {
    $this->get('/modulos/vendas/dia/2026-13-01/editar')->assertNotFound();
});

it('updates an existing sale', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '500.00']);

    $this->put(route('sales.update', ['date' => '2026-06-15']), ['amount' => '750,00'])
        ->assertRedirect(route('sales.index'));

    expect(DailySale::where('sale_date', '2026-06-15')->first()->amount)->toBe('750.00');
});

it('creates a sale for a past date via update', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->put(route('sales.update', ['date' => '2026-06-10']), ['amount' => '300,00'])
        ->assertRedirect(route('sales.index'));

    expect(DailySale::where('sale_date', '2026-06-10')->first()->amount)->toBe('300.00');
});

it('rejects a future date on update', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->put(route('sales.update', ['date' => '2026-06-20']), ['amount' => '300,00'])
        ->assertStatus(404);
});

// ── Indicadores e metas ─────────────────────────────────────────────────────

it('computes accrued goal without future days', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Set up expectation for all days 1-15
    for ($d = 1; $d <= 15; $d++) {
        SalesExpectation::create([
            'expectation_date' => "2026-06-{$d}",
            'amount' => '100.00',
        ]);
    }
    // Future days (16-30) also have expectations — should NOT count
    for ($d = 16; $d <= 30; $d++) {
        SalesExpectation::create([
            'expectation_date' => "2026-06-{$d}",
            'amount' => '100.00',
        ]);
    }

    $monthStart = Carbon::create(2026, 6, 1);
    $today = Carbon::create(2026, 6, 15);
    $indicators = DailySale::indicators($monthStart, $today);

    expect($indicators['accrued_goal_cents'])->toBe(150000); // 15 × 100.00 × 100
});

it('computes full month goal', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    for ($d = 1; $d <= 30; $d++) {
        SalesExpectation::create([
            'expectation_date' => "2026-06-{$d}",
            'amount' => '100.00',
        ]);
    }

    $monthStart = Carbon::create(2026, 6, 1);
    $today = Carbon::create(2026, 6, 15);
    $indicators = DailySale::indicators($monthStart, $today);

    expect($indicators['full_goal_cents'])->toBe(300000); // 30 × 100.00 × 100
});

it('shows dash for accrued percent when goal is zero', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $monthStart = Carbon::create(2026, 6, 1);
    $today = Carbon::create(2026, 6, 15);
    $indicators = DailySale::indicators($monthStart, $today);

    expect($indicators['accrued_percent'])->toBeNull();
    expect($indicators['accrued_percent_tone'])->toBe('neutral');
});

it('shows green tone when percent is 100 or more', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    SalesExpectation::create(['expectation_date' => '2026-06-15', 'amount' => '500.00']);
    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '500.00']);

    $monthStart = Carbon::create(2026, 6, 1);
    $today = Carbon::create(2026, 6, 15);
    $indicators = DailySale::indicators($monthStart, $today);

    expect($indicators['accrued_percent'])->toBeGreaterThanOrEqual(100.0);
    expect($indicators['accrued_percent_tone'])->toBe('green');
});

it('handles months with 28, 29, 30 and 31 days', function (int $year, int $month, int $days) {
    $this->travelTo(Carbon::create($year, $month, $days));

    for ($d = 1; $d <= $days; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        SalesExpectation::create(['expectation_date' => $date, 'amount' => '100.00']);
    }

    $monthStart = Carbon::create($year, $month, 1);
    $today = Carbon::create($year, $month, $days);
    $indicators = DailySale::indicators($monthStart, $today);

    expect($indicators['full_goal_cents'])->toBe($days * 10000);
})->with([
    [2023, 2, 28],  // 28 dias
    [2024, 2, 29],  // 29 dias (bissexto)
    [2026, 6, 30],  // 30 dias
    [2026, 1, 31],  // 31 dias
]);

it('sale and expectation are independent', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    DailySale::create(['sale_date' => '2026-06-15', 'amount' => '999.00']);
    // No expectation created

    $monthStart = Carbon::create(2026, 6, 1);
    $today = Carbon::create(2026, 6, 15);
    $indicators = DailySale::indicators($monthStart, $today);

    expect($indicators['sold_cents'])->toBe(99900);
    expect($indicators['accrued_goal_cents'])->toBe(0);
});

// ── Área avançada ───────────────────────────────────────────────────────────

it('renders the advanced page', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.advanced'))
        ->assertOk()
        ->assertSee('Avançado');
});

it('shows all days of the month in the advanced table', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.advanced', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('01/06/2026')
        ->assertSee('30/06/2026');
});

it('does not show EDITAR for future days in advanced table', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->get(route('sales.advanced', ['ano' => 2026, 'mes' => 6]));

    // 16 to 30 are future — no edit link should point to them
    $response->assertOk()->assertDontSee('2026-06-20/editar', false);
});

it('shows sem registro for days without a sale in advanced table', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.advanced', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('sem registro');
});

it('does not create a database row for days without sale in the advanced view', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.advanced', ['ano' => 2026, 'mes' => 6]));

    expect(DailySale::count())->toBe(0);
});

// ── Expectativas ────────────────────────────────────────────────────────────

it('renders the expectations page', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.expectations.edit'))
        ->assertOk()
        ->assertSee('Expectativa de venda do mês');
});

it('shows the 3-month window pills', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.expectations.edit'))
        ->assertOk()
        ->assertSee('Maio') // previous
        ->assertSee('Junho') // current
        ->assertSee('Julho'); // next
});

it('redirects to current month when outside the 3-month window', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $this->get(route('sales.expectations.edit', ['ano' => 2026, 'mes' => 1]))
        ->assertRedirect();
});

it('saves expectations for the month', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $expectations = [];
    for ($d = 1; $d <= 30; $d++) {
        $expectations[sprintf('2026-06-%02d', $d)] = '100,00';
    }

    $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => $expectations,
        'ano' => 2026,
        'mes' => 6,
    ])->assertRedirect(route('sales.expectations.edit', ['ano' => 2026, 'mes' => 6]));

    expect(SalesExpectation::count())->toBe(30);
    expect(SalesExpectation::where('expectation_date', '2026-06-01')->first()->amount)->toBe('100.00');
});

it('converts empty expectation fields to 0.00 without deleting rows', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    // Pre-fill all 30 days with 100.00
    for ($d = 1; $d <= 30; $d++) {
        SalesExpectation::create([
            'expectation_date' => sprintf('2026-06-%02d', $d),
            'amount' => '100.00',
        ]);
    }

    // Now send empty value for day 1
    $expectations = [];
    for ($d = 1; $d <= 30; $d++) {
        $expectations[sprintf('2026-06-%02d', $d)] = $d === 1 ? '' : '100,00';
    }

    $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => $expectations,
        'ano' => 2026,
        'mes' => 6,
    ])->assertRedirect();

    // Row still exists, amount is now 0.00
    expect(SalesExpectation::count())->toBe(30);
    expect(SalesExpectation::where('expectation_date', '2026-06-01')->first()->amount)->toBe('0.00');
});

it('does not duplicate expectations on second save', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $expectations = [];
    for ($d = 1; $d <= 30; $d++) {
        $expectations[sprintf('2026-06-%02d', $d)] = '100,00';
    }
    $params = ['expectations' => $expectations, 'ano' => 2026, 'mes' => 6];

    $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), $params);
    $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), $params);

    expect(SalesExpectation::count())->toBe(30);
});

it('shows saved values in the expectations form', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    SalesExpectation::create(['expectation_date' => '2026-06-15', 'amount' => '999.99']);

    $this->get(route('sales.expectations.edit', ['ano' => 2026, 'mes' => 6]))
        ->assertOk()
        ->assertSee('999,99');
});

// ── Validações estritas de expectativas (Fase 2) ──────────────────────────────

it('fails validation when expectation has non-numeric text', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->from(route('sales.expectations.edit', ['ano' => 2026, 'mes' => 6]))
        ->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
            'expectations' => [
                '2026-06-01' => 'abc',
            ],
            'ano' => 2026,
            'mes' => 6,
        ]);

    $response->assertRedirect(route('sales.expectations.edit', ['ano' => 2026, 'mes' => 6]));
    $response->assertSessionHasErrors('expectations.2026-06-01');
    expect(SalesExpectation::count())->toBe(0);
});

it('fails validation when expectation is negative', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => [
            '2026-06-01' => '-10,00',
        ],
        'ano' => 2026,
        'mes' => 6,
    ]);

    $response->assertSessionHasErrors('expectations.2026-06-01');
    expect(SalesExpectation::count())->toBe(0);
});

it('fails validation when expectation exceeds maximum', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => [
            '2026-06-01' => '99999999999999',
        ],
        'ano' => 2026,
        'mes' => 6,
    ]);

    $response->assertSessionHasErrors('expectations.2026-06-01');
    expect(SalesExpectation::count())->toBe(0);
});

it('fails validation when expectation is an injected array', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => [
            '2026-06-01' => ['malicious' => 'data'],
        ],
        'ano' => 2026,
        'mes' => 6,
    ]);

    $response->assertSessionHasErrors('expectations.2026-06-01');
    expect(SalesExpectation::count())->toBe(0);
});

it('fails validation when a date key is outside the displayed month', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => [
            '2026-06-01' => '100,00',
            '2026-07-01' => '100,00',
        ],
        'ano' => 2026,
        'mes' => 6,
    ]);

    $response->assertSessionHasErrors('expectations.2026-07-01');
    expect(SalesExpectation::count())->toBe(0);
});

it('redirects when month is outside the 3-month window', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 1]), [
        'expectations' => [
            '2026-01-01' => '100,00',
        ],
        'ano' => 2026,
        'mes' => 1,
    ]);

    $response->assertRedirect(route('sales.expectations.edit', ['ano' => 2026, 'mes' => 6]));
    $response->assertSessionHas('warning');
    expect(SalesExpectation::count())->toBe(0);
});

it('does not save any record if a single day has validation error (atomic transaction)', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => [
            '2026-06-01' => '100,00',
            '2026-06-02' => 'invalido',
            '2026-06-03' => '200,00',
        ],
        'ano' => 2026,
        'mes' => 6,
    ]);

    $response->assertSessionHasErrors('expectations.2026-06-02');
    expect(SalesExpectation::count())->toBe(0);
});

it('accepts numbers in diverse valid formats like 1.234,56 and 1234.5', function () {
    $this->travelTo(Carbon::create(2026, 6, 15));

    $response = $this->put(route('sales.expectations.update', ['ano' => 2026, 'mes' => 6]), [
        'expectations' => [
            '2026-06-01' => '1.234,56',
            '2026-06-02' => '1234,56',
            '2026-06-03' => '1234.5',
        ],
        'ano' => 2026,
        'mes' => 6,
    ]);

    $response->assertRedirect();
    expect(SalesExpectation::where('expectation_date', '2026-06-01')->value('amount'))->toBe('1234.56');
    expect(SalesExpectation::where('expectation_date', '2026-06-02')->value('amount'))->toBe('1234.56');
    expect(SalesExpectation::where('expectation_date', '2026-06-03')->value('amount'))->toBe('1234.50');
});

it('returns 404 when edit sale route receives an invalid date format', function () {
    $this->get('/modulos/vendas/dia/2026-02-30/editar')->assertNotFound();
    $this->get('/modulos/vendas/dia/invalido/editar')->assertNotFound();
});

it('returns 404 when update sale route receives an invalid date format', function () {
    $this->put('/modulos/vendas/dia/2026-02-30', ['amount' => '100,00'])->assertNotFound();
    $this->put('/modulos/vendas/dia/invalido', ['amount' => '100,00'])->assertNotFound();
});
