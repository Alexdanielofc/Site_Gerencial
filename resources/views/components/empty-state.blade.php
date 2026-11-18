@props(['title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center gap-2 rounded-xl border border-dashed border-line bg-surface px-6 py-10 text-center']) }}>
    <p class="font-semibold">{{ $title }}</p>
    @if ($description)
        <p class="max-w-md text-sm leading-6 text-ink-soft">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
