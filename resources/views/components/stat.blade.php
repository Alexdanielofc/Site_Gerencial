@props(['label', 'value', 'hint' => null, 'tone' => 'blue'])

@php
    $valueColor = match ($tone) {
        'green' => 'text-grn7',
        'red' => 'text-red7',
        'neutral' => 'text-ink',
        default => 'text-blu6',
    };
@endphp

<x-card {{ $attributes->class(['flex flex-col gap-1']) }}>
    <p class="text-sm font-medium text-ink-soft">{{ $label }}</p>
    <p class="{{ $valueColor }} text-2xl font-semibold tabular-nums sm:text-3xl">{{ $value }}</p>
    @if ($hint)
        <p class="text-xs text-ink-soft">{{ $hint }}</p>
    @endif
</x-card>
