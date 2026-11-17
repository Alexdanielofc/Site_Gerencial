<?php

it('renders the management homepage with links to each module', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Site Gerencial')
        ->assertSee('Painel de controle')
        ->assertSee(route('sales.index'))
        ->assertSee(route('invoices.index'))
        ->assertSee(route('payables.index'))
        ->assertDontSee('Bem-vindo ao sistema de gestão')
        ->assertDontSee('Sobre esta etapa');
});

it('renders the sales module index page', function () {
    $response = $this->get(route('sales.index'));

    $response->assertOk()
        ->assertSee('Vendas e metas')
        ->assertSee('Site Gerencial');
});

it('renders the invoices module index page', function () {
    $response = $this->get(route('invoices.index'));

    $response->assertOk()
        ->assertSee('Notas fiscais')
        ->assertSee('Site Gerencial');
});

it('renders the payables module index page', function () {
    $response = $this->get(route('payables.index'));

    $response->assertOk()
        ->assertSee('Contas a pagar')
        ->assertSee('Site Gerencial');
});

it('does not expose authentication pages', function (string $path) {
    $response = $this->get($path);

    $response->assertNotFound();
})->with(['/login', '/register']);
