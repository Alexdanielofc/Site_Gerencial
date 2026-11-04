@props(['title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center gap-2 rounded-xl border border-dashed border-cin3 bg-branco px-6 py-10 text-center']) }}>
    <p class="font-semibold">{{ $title }}</p>
    @if ($description)
        <p class="max-w-md text-sm leading-6 text-cin6">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
