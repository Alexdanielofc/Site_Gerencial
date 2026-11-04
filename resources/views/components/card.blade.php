@props(['as' => 'div', 'size' => 'md'])

@php
    $padding = match ($size) {
        'lg' => 'p-6 sm:p-8',
        'none' => '',
        default => 'p-5',
    };
@endphp

<{{ $as }} {{ $attributes->class(['rounded-xl border border-cin2 bg-branco shadow-sm', $padding]) }}>
    {{ $slot }}
</{{ $as }}>
