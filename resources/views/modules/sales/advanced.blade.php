<x-layout title="Vendas avançado">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Vendas e metas — Avançado"
            description="Histórico completo de vendas por mês."
            :back-href="route('sales.index')"
            back-label="Voltar a Vendas"
        />

        <x-flash />

        {{-- Pílulas de período --}}
        <x-period-pills
            :years="$years"
            :months="$months"
            :selected-year="$monthStart->year"
            :selected-month="$monthStart->month"
            route="sales.advanced"
        />

        {{-- Indicadores do período --}}
        @if ($indicators['is_current_month'])
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat label="Total vendido" :value="\App\Support\Money::format($indicators['sold_cents'])" tone="blue" />
                <x-stat label="Meta completa do mês" :value="\App\Support\Money::format($indicators['full_goal_cents'])" tone="neutral" />
                <x-stat
                    label="% da meta mensal"
                    :value="\App\Support\Money::formatPercent(\App\Support\Money::percentOf($indicators['sold_cents'], $indicators['full_goal_cents']))"
                    :tone="\App\Support\Money::percentOf($indicators['sold_cents'], $indicators['full_goal_cents']) === null ? 'neutral' : (\App\Support\Money::percentOf($indicators['sold_cents'], $indicators['full_goal_cents']) >= 100 ? 'green' : 'blue')"
                />
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat label="Total vendido" :value="\App\Support\Money::format($indicators['sold_cents'])" tone="blue" />
                <x-stat label="Meta completa do mês" :value="\App\Support\Money::format($indicators['full_goal_cents'])" tone="neutral" />
                <x-stat
                    label="% da meta mensal"
                    :value="\App\Support\Money::formatPercent($indicators['full_percent'])"
                    :tone="$indicators['full_percent_tone']"
                />
            </div>
        @endif

        {{-- Tabela de dias do mês --}}
        <x-card size="none">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2 text-left text-xs font-semibold uppercase tracking-wide text-ink-soft">
                            <th class="px-5 py-3">Data</th>
                            <th class="px-5 py-3">Dia da semana</th>
                            <th class="px-5 py-3 text-right">Valor vendido</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($days as $day)
                            <tr class="{{ $day['is_future'] ? 'opacity-50' : 'hover:bg-surface-2' }}">
                                <td class="px-5 py-3 tabular-nums text-ink">{{ $day['label'] }}</td>
                                <td class="px-5 py-3 text-ink-soft">{{ $day['weekday'] }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">
                                    @if ($day['has_record'])
                                        <span class="font-medium">{{ \App\Support\Money::format($day['amount']) }}</span>
                                    @else
                                        <span class="text-ink-soft">{{ \App\Support\Money::format(0) }}</span>
                                        <span class="ml-2 text-xs text-ink-soft">sem registro</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if (! $day['is_future'])
                                        <a
                                            href="{{ route('sales.edit', ['date' => $day['date'], 'origem' => 'advanced', 'ano' => $monthStart->year, 'mes' => $monthStart->month]) }}"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-accent transition-colors hover:text-blu5"
                                        >
                                            <x-icons.pencil /> EDITAR
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-layout>
