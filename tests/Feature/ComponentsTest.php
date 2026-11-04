<?php

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
        ->assertSee('border-cin3', false)
        ->assertSee('Avançado');
});

it('marks only the selected pill as current', function () {
    $this->blade('<x-pill href="/a" :selected="true">2026</x-pill>')
        ->assertSee('aria-current="true"', false)
        ->assertSee('bg-blu6', false);

    $this->blade('<x-pill href="/b">2025</x-pill>')
        ->assertDontSee('aria-current', false)
        ->assertSee('border-cin3', false);
});

it('renders an empty state with title, description and action', function () {
    $this->blade('<x-empty-state title="Nada por aqui" description="Ainda não há registros."><a href="/novo">Criar</a></x-empty-state>')
        ->assertSee('Nada por aqui')
        ->assertSee('Ainda não há registros.')
        ->assertSee('Criar');
});
