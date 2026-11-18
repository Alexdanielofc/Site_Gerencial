@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $classes = [
        'inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold uppercase tracking-wide transition-all duration-150',
        'hover:scale-[1.03] active:scale-95',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blu6',
        'bg-blu6 text-branco hover:bg-blu7 hover:shadow-md' => $variant === 'primary',
        'border border-line bg-surface text-ink hover:bg-surface-2 hover:border-ink-soft hover:shadow-sm' => $variant === 'secondary',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
