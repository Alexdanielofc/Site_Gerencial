<x-layout title="Expectativas do mês">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Expectativa de venda do mês"
            description="Defina o valor esperado de vendas para cada dia do mês."
            :back-href="route('sales.index')"
            back-label="Voltar a Vendas"
        />

        {{-- Flash messages --}}
        @if (session('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif
        @if (session('warning'))
            <x-alert type="warning">{{ session('warning') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="danger">{{ session('error') }}</x-alert>
        @endif

        {{-- Pílulas: apenas 3 meses --}}
        <nav aria-label="Selecionar mês" class="flex flex-wrap gap-2">
            @foreach ($pills as $pill)
                <x-pill
                    :href="route('sales.expectations.edit', ['ano' => $pill['year'], 'mes' => $pill['month']])"
                    :selected="$pill['selected']"
                >
                    {{ $pill['label'] }}
                </x-pill>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('sales.expectations.update', ['ano' => $monthStart->year, 'mes' => $monthStart->month]) }}" novalidate>
            @csrf
            @method('PUT')

            @if ($errors->any())
                <x-alert type="danger" title="Verifique os campos">
                    <ul class="mt-1 list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <x-card size="none">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2 text-left text-xs font-semibold uppercase tracking-wide text-ink-soft">
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Dia da semana</th>
                                <th class="px-5 py-3 text-right">Expectativa (R$)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cin2">
                            @foreach ($days as $day)
                                <tr class="hover:bg-surface-2">
                                    <td class="px-5 py-3 tabular-nums text-ink">{{ $day['label'] }}</td>
                                    <td class="px-5 py-3 text-ink-soft">{{ $day['weekday'] }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            name="expectations[{{ $day['date'] }}]"
                                            value="{{ $day['amount'] }}"
                                            placeholder="0,00"
                                            class="w-32 rounded border border-line px-2.5 py-1.5 text-right tabular-nums text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                            aria-label="Expectativa de {{ $day['label'] }}"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <div class="mt-4 flex gap-3">
                <x-button type="submit">Salvar expectativas</x-button>
                <x-button variant="secondary" :href="route('sales.index')">Cancelar</x-button>
            </div>
        </form>
    </div>
</x-layout>
