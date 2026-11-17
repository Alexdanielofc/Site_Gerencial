<?php

it('renders the management homepage with links to each module', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Site Gerencial')
        ->assertSee('Módulos do sistema')
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

it('renders placeholder modules for invoices and payables', function (string $routeName, string $moduleTitle) {
    $response = $this->get(route($routeName));

    $response->assertOk()
        ->assertSee($moduleTitle)
        ->assertSee('Esta área será desenvolvida em uma próxima etapa.')
        ->assertSee('Site Gerencial');
})->with([
    ['invoices.index', 'Notas fiscais'],
    ['payables.index', 'Contas a pagar'],
]);

it('does not expose authentication pages', function (string $path) {
    $response = $this->get($path);

    $response->assertNotFound();
})->with(['/login', '/register']);
