@props(['title', 'description' => null, 'eyebrow' => null, 'backHref' => null, 'backLabel' => 'Voltar'])

<div {{ $attributes->class(['flex flex-col gap-3']) }}>
    @if ($backHref)
        <a href="{{ $backHref }}" class="inline-flex w-fit items-center gap-2 text-sm font-medium text-blu6 transition-colors hover:text-blu5">
            <x-icons.arrow-left />
            {{ $backLabel }}
        </a>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-col gap-2">
            @if ($eyebrow)
                <span class="text-sm font-medium text-blu6">{{ $eyebrow }}</span>
            @endif
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $title }}</h1>
            @if ($description)
                <p class="max-w-2xl text-sm leading-6 text-cin6 sm:text-base">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
