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
            :params="['situacao' => $statusFilter !== 'todas' ? $statusFilter : null, 'busca' => $search]"
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

        {{-- Barra de busca e filtro de situação --}}
        <x-card>
            <form method="GET" action="{{ route('invoices.advanced') }}" data-loading-text="Buscando..." class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="ano" value="{{ $monthStart->year }}">
                <input type="hidden" name="mes" value="{{ $monthStart->month }}">

                {{-- Filtro de situação --}}
                <div class="flex items-center gap-1.5 rounded-lg border border-line bg-surface p-1 text-xs">
                    @foreach (['todas' => 'Todas', 'ativas' => 'Ativas', 'canceladas' => 'Canceladas'] as $key => $label)
                        <button
                            type="submit"
                            name="situacao"
                            value="{{ $key }}"
                            class="cursor-pointer rounded-md px-3 py-1.5 font-medium transition-colors {{ $statusFilter === $key ? 'bg-blu6 text-branco' : 'text-ink-soft hover:bg-surface-2 hover:text-ink' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

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
                @if ($search !== '' || $statusFilter !== 'todas')
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
                :description="$search !== '' || $statusFilter !== 'todas' ? 'Nenhum resultado corresponde à busca informada neste período.' : 'Não há notas fiscais cadastradas para o período selecionado.'"
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
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($invoices as $invoice)
                                @php $isCanceled = $invoice->isCanceled(); @endphp
                                <tr class="hover:bg-surface-2 {{ $isCanceled ? 'opacity-60' : '' }}">
                                    <td class="px-5 py-3 tabular-nums text-ink">
                                        {{ $invoice->issue_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-3 font-medium text-ink">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $invoice->invoice_number ?: '—' }}</span>
                                            @if ($isCanceled)
                                                <x-badge status="cancelada" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-ink">
                                        {{ $invoice->customer_name }}
                                        @if ($invoice->notes)
                                            <p class="text-xs text-ink-soft">{{ $invoice->notes }}</p>
                                        @endif
                                        @if ($isCanceled && $invoice->cancel_reason)
                                            <p class="text-xs text-ink-soft">Motivo: {{ $invoice->cancel_reason }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 tabular-nums text-ink-soft">
                                        {{ $invoice->formatted_cnpj ?: '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold tabular-nums {{ $isCanceled ? 'line-through text-ink-soft' : 'text-ink' }}">
                                        {{ \App\Support\Money::format((int) round($invoice->amount * 100)) }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($isCanceled)
                                                <form method="POST" action="{{ route('invoices.reactivate', $invoice->id) }}" data-loading-text="Reativando...">
                                                    @csrf
                                                    <input type="hidden" name="origem" value="advanced">
                                                    <input type="hidden" name="ano" value="{{ $monthStart->year }}">
                                                    <input type="hidden" name="mes" value="{{ $monthStart->month }}">
                                                    @if ($statusFilter !== 'todas') <input type="hidden" name="situacao" value="{{ $statusFilter }}"> @endif
                                                    @if ($search !== '') <input type="hidden" name="busca" value="{{ $search }}"> @endif
                                                    @if ($invoices->currentPage() > 1) <input type="hidden" name="page" value="{{ $invoices->currentPage() }}"> @endif
                                                    <button
                                                        type="submit"
                                                        class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold uppercase tracking-wide text-accent hover:text-blu5"
                                                    >
                                                        Reativar
                                                    </button>
                                                </form>
                                            @else
                                                <a
                                                    href="{{ route('invoices.edit', array_filter([
                                                        'invoice' => $invoice->id,
                                                        'origem' => 'advanced',
                                                        'ano' => $monthStart->year,
                                                        'mes' => $monthStart->month,
                                                        'situacao' => $statusFilter !== 'todas' ? $statusFilter : null,
                                                        'busca' => $search !== '' ? $search : null,
                                                        'page' => $invoices->currentPage() > 1 ? $invoices->currentPage() : null,
                                                    ])) }}"
                                                    class="inline-flex items-center gap-1 text-sm font-medium text-accent transition-colors hover:text-blu5"
                                                >
                                                    <x-icons.pencil /> EDITAR
                                                </a>
                                                <form
                                                    method="POST"
                                                    action="{{ route('invoices.cancel', $invoice->id) }}"
                                                    data-confirm="Cancelar esta nota? Ela deixará de entrar nos totais."
                                                    data-loading-text="Cancelando..."
                                                >
                                                    @csrf
                                                    <input type="hidden" name="origem" value="advanced">
                                                    <input type="hidden" name="ano" value="{{ $monthStart->year }}">
                                                    <input type="hidden" name="mes" value="{{ $monthStart->month }}">
                                                    @if ($statusFilter !== 'todas') <input type="hidden" name="situacao" value="{{ $statusFilter }}"> @endif
                                                    @if ($search !== '') <input type="hidden" name="busca" value="{{ $search }}"> @endif
                                                    @if ($invoices->currentPage() > 1) <input type="hidden" name="page" value="{{ $invoices->currentPage() }}"> @endif
                                                    <button
                                                        type="submit"
                                                        class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold uppercase tracking-wide text-ink-soft hover:text-red6"
                                                    >
                                                        <x-icons.ban /> Cancelar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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
