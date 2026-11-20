@props(['status'])

@php
    $configs = [
        'paga' => [
            'box'   => 'border-grn2 bg-grn1 text-grn7',
            'icon'  => 'check',
            'color' => 'text-grn6',
            'label' => 'Paga',
        ],
        'vencida' => [
            'box'   => 'border-red2 bg-red1 text-red7',
            'icon'  => 'warning',
            'color' => 'text-red6',
            'label' => 'Vencida',
        ],
        'pendente' => [
            'box'   => 'border-yel2 bg-yel1 text-yel8',
            'icon'  => 'warning',
            'color' => 'text-yel6',
            'label' => 'Pendente',
        ],
        'cancelada' => [
            'box'   => 'border-line bg-surface-2 text-ink-soft',
            'icon'  => 'ban',
            'color' => 'text-ink-soft',
            'label' => 'Cancelada',
        ],
    ];

    $config = $configs[$status] ?? $configs['pendente'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium', $config['box']]) }}>
    <span class="shrink-0 {{ $config['color'] }}">
        <x-dynamic-component :component="'icons.' . $config['icon']" class="size-3.5" />
    </span>
    {{ $slot->isNotEmpty() ? $slot : $config['label'] }}
</span>
