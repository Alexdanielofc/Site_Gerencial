@props(['type' => 'info', 'title' => null, 'icon' => null])

@php
    $styles = [
        'danger' => ['box' => 'border-red2 bg-red1 text-red7', 'icon' => 'text-red6', 'name' => 'warning'],
        'warning' => ['box' => 'border-yel2 bg-yel1 text-yel8', 'icon' => 'text-yel6', 'name' => 'warning'],
        'success' => ['box' => 'border-grn2 bg-grn1 text-grn7', 'icon' => 'text-grn6', 'name' => 'check'],
        'info' => ['box' => 'border-blu3 bg-blu1 text-ink', 'icon' => 'text-blu6', 'name' => 'info'],
    ];
    $style = $styles[$type] ?? $styles['info'];
    $role = in_array($type, ['danger', 'warning'], true) ? 'alert' : 'status';
    $iconName = $icon ?? $style['name'];
@endphp

<div role="{{ $role }}" {{ $attributes->class(['flex items-start gap-3 rounded-lg border p-4 text-sm', $style['box']]) }}>
    <span class="mt-0.5 shrink-0 {{ $style['icon'] }}">
        <x-dynamic-component :component="'icons.'.$iconName" />
    </span>
    <div class="flex flex-col gap-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div>{{ $slot }}</div>
        @endif
    </div>
</div>
