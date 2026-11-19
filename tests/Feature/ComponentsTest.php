<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('renders a card with the chosen tag and size', function () {
    $this->blade('<x-card as="section" size="lg">Conteúdo</x-card>')
        ->assertSee('<section', false)
        ->assertSee('p-6', false)
        ->assertSee('Conteúdo');
});

it('renders a stat card with label, value, hint and tone', function () {
    $this->blade('<x-stat label="Total vendido" value="R$ 1.000,00" hint="No mês" tone="green" />')
        ->assertSee('Total vendido')
        ->assertSee('R$ 1.000,00')
        ->assertSee('No mês')
        ->assertSee('text-grn7', false);
});

it('renders alerts with the right role for each type', function (string $type, string $role) {
    $this->blade('<x-alert :type="$type" title="Título do aviso">Texto do aviso</x-alert>', ['type' => $type])
        ->assertSee('role="'.$role.'"', false)
        ->assertSee('Título do aviso')
        ->assertSee('Texto do aviso');
})->with([
    ['danger', 'alert'],
    ['warning', 'alert'],
    ['success', 'status'],
    ['info', 'status'],
]);

it('renders a button as a link when it has an href', function () {
    $this->blade('<x-button href="/destino">Editar</x-button>')
        ->assertSee('<a href="/destino"', false)
        ->assertSee('bg-blu6', false)
        ->assertSee('Editar');
});

it('renders a secondary button element with the default type', function () {
    $this->blade('<x-button variant="secondary">Avançado</x-button>')
        ->assertSee('<button type="button"', false)
        ->assertSee('border-line', false)
        ->assertSee('Avançado');
});

it('marks only the selected pill as current', function () {
    $this->blade('<x-pill href="/a" :selected="true">2026</x-pill>')
        ->assertSee('aria-current="true"', false)
        ->assertSee('bg-blu6', false);

    $this->blade('<x-pill href="/b">2025</x-pill>')
        ->assertDontSee('aria-current', false)
        ->assertSee('border-line', false);
});

it('renders an empty state with title, description and action', function () {
    $this->blade('<x-empty-state title="Nada por aqui" description="Ainda não há registros."><a href="/novo">Criar</a></x-empty-state>')
        ->assertSee('Nada por aqui')
        ->assertSee('Ainda não há registros.')
        ->assertSee('Criar');
});

it('renders a page header with back link, description and actions', function () {
    $this->blade('<x-page-header title="Vendas e metas" description="Resumo do módulo." back-href="/" back-label="Voltar ao início"><x-slot:actions><a href="/avancado">Avançado</a></x-slot:actions></x-page-header>')
        ->assertSee('Vendas e metas')
        ->assertSee('Resumo do módulo.')
        ->assertSee('Voltar ao início')
        ->assertSee('href="/"', false)
        ->assertSee('Avançado');
});

it('renders a page header without back link or actions', function () {
    $this->blade('<x-page-header title="Contas a pagar" />')
        ->assertSee('Contas a pagar')
        ->assertDontSee('Voltar');
});

it('renders a badge with the correct style and icon for each status', function (string $status, string $expectedClass, string $expectedText) {
    $this->blade('<x-badge :status="$status" />', ['status' => $status])
        ->assertSee($expectedClass, false)
        ->assertSee($expectedText);
})->with([
    ['paga', 'border-grn2 bg-grn1 text-grn7', 'Paga'],
    ['vencida', 'border-red2 bg-red1 text-red7', 'Vencida'],
    ['pendente', 'border-yel2 bg-yel1 text-yel8', 'Pendente'],
]);

it('renders the theme toggle button with the correct aria attributes', function () {
    $this->blade('<x-theme-toggle />')
        ->assertSee('<button', false)
        ->assertSee('data-theme-toggle', false)
        ->assertSee('aria-label="Alternar tema"', false)
        ->assertSee('aria-pressed="false"', false);
});

it('ensures dark theme does not redefine branco', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->not->toBeEmpty();

    preg_match("/html\[data-theme='dark'\]\s*\{([^}]+)\}/s", $css, $matches);
    expect($matches)->not->toBeEmpty();

    $darkBlock = $matches[1];
    expect($darkBlock)->not->toContain('--branco');
});

it('renders flash messages for each type and escapes html content', function () {
    session()->flash('success', 'Registro <b>salvo</b> com sucesso.');
    session()->flash('error', 'Ocorreu um erro.');
    session()->flash('warning', 'Atenção necessária.');
    session()->flash('info', 'Informação do sistema.');

    $this->blade('<x-flash />')
        ->assertSee('Registro &lt;b&gt;salvo&lt;/b&gt; com sucesso.', false)
        ->assertSee('Ocorreu um erro.')
        ->assertSee('Atenção necessária.')
        ->assertSee('Informação do sistema.')
        ->assertDontSee('<b>salvo</b>', false);
});

it('does not render anything when there are no flash messages or errors', function () {
    $rendered = (string) $this->blade('<x-flash />');
    expect(trim($rendered))->toBeEmpty();
});

it('renders validation errors list when except-errors is false and hides when true', function () {
    $viewErrors = new ViewErrorBag;
    $bag = new MessageBag(['campo' => ['Campo obrigatório']]);
    $viewErrors->put('default', $bag);

    session()->flash('errors', $viewErrors);

    $this->blade('<x-flash />')
        ->assertSee('Verifique os campos')
        ->assertSee('Campo obrigatório');

    $this->blade('<x-flash :except-errors="true" />')
        ->assertDontSee('Verifique os campos')
        ->assertDontSee('Campo obrigatório');
});
