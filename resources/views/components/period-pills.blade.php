@props([
    'years',
    'months',
    'selectedYear',
    'selectedMonth',
    'route',
    'params' => [],
])

@php
    $extraParams = array_filter($params, fn ($v) => $v !== null && $v !== '');
@endphp

<nav aria-label="Selecionar período" {{ $attributes->class(['flex flex-col gap-3']) }}>
    <div class="flex flex-wrap items-center gap-2">
        <span class="w-12 text-sm font-medium text-ink-soft">Ano</span>
        @foreach ($years as $item)
            <x-pill
                :href="route($route, array_merge(['ano' => $item['year'], 'mes' => $item['month']], $extraParams))"
                :selected="$item['year'] === $selectedYear"
            >
                {{ $item['year'] }}
            </x-pill>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <span class="w-12 text-sm font-medium text-ink-soft">Mês</span>
        @foreach ($months as $item)
            <x-pill
                :href="route($route, array_merge(['ano' => $selectedYear, 'mes' => $item['month']], $extraParams))"
                :selected="$item['month'] === $selectedMonth"
            >
                {{ $item['label'] }}
            </x-pill>
        @endforeach
    </div>
</nav>
