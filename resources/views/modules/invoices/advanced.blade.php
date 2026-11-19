<x-layout title="Notas fiscais — Avançado">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Notas fiscais — Avançado"
            description="Histórico completo e consulta de notas fiscais por período."
            :back-href="route('invoices.index')"
            back-label="Voltar a Notas fiscais"
        >
            <x-slot:actions>
                <x-button variant="primary" :href="route('invoices.create')">Nova nota</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-flash />

        {{-- Pílulas de período --}}
        <x-period-pills
            :years="$years"
            :months="$months"
            :selected-year="$monthStart->year"
            :selected-month="$monthStart->month"
            route="invoices.advanced"
            :params="['busca' => $search]"
        />

        {{-- Indicadores do período --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat
                label="Quantidade de notas"
                :value="(string) $periodCount"
                tone="neutral"
            />
            <x-stat
                label="Valor total emitido"
                :value="\App\Support\Money::format($periodTotalCents)"
                tone="blue"
            />
            <x-stat
                label="Ticket médio"
                :value="\App\Support\Money::format($averageTicket)"
                tone="neutral"
            />
        </div>

        {{-- Barra de busca --}}
        <x-card>
            <form method="GET" action="{{ route('invoices.advanced') }}" data-loading-text="Buscando..." class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="ano" value="{{ $monthStart->year }}">
                <input type="hidden" name="mes" value="{{ $monthStart->month }}">

                <div class="relative min-w-[240px] flex-1">
                    <input
                        type="search"
                        name="busca"
                        value="{{ $search }}"
                        placeholder="Buscar por cliente ou número da nota..."
                        class="w-full text-sm @error('busca') border-red6 @enderror"
                    >
                </div>
                <x-button type="submit" variant="secondary">Buscar</x-button>
                @if ($search !== '')
                    <a
                        href="{{ route('invoices.advanced', ['ano' => $monthStart->year, 'mes' => $monthStart->month]) }}"
                        class="text-sm font-medium text-ink-soft hover:text-ink"
                    >
                        Limpar busca
                    </a>
                @endif
            </form>
        </x-card>

        {{-- Tabela de notas fiscais --}}
        @if ($invoices->isEmpty())
            <x-empty-state
                title="Nenhuma nota fiscal encontrada"
                :description="$search !== '' ? 'Nenhum resultado corresponde à busca informada neste período.' : 'Não há notas fiscais cadastradas para o período selecionado.'"
            >
                <x-button variant="primary" :href="route('invoices.create')">Registrar primeira nota</x-button>
            </x-empty-state>
        @else
            <x-card size="none">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2 text-left text-xs font-semibold uppercase tracking-wide text-ink-soft">
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Número</th>
                                <th class="px-5 py-3">Cliente</th>
                                <th class="px-5 py-3">CNPJ</th>
                                <th class="px-5 py-3 text-right">Valor</th>
                                <th class="px-5 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($invoices as $invoice)
                                <tr class="hover:bg-surface-2">
                                    <td class="px-5 py-3 tabular-nums text-ink">
                                        {{ $invoice->issue_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-3 font-medium text-ink">
                                        {{ $invoice->invoice_number ?: '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-ink">
                                        {{ $invoice->customer_name }}
                                        @if ($invoice->notes)
                                            <p class="text-xs text-ink-soft">{{ $invoice->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 tabular-nums text-ink-soft">
                                        {{ $invoice->formatted_cnpj ?: '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink">
                                        {{ \App\Support\Money::format((int) round($invoice->amount * 100)) }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a
                                            href="{{ route('invoices.edit', ['invoice' => $invoice->id, 'origem' => 'advanced']) }}"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-accent transition-colors hover:text-blu5"
                                        >
                                            <x-icons.pencil /> EDITAR
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- Paginação --}}
            <div class="mt-2">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</x-layout>
