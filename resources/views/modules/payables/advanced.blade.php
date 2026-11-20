<x-layout title="Contas a pagar — Avançado">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Contas a pagar — Avançado"
            description="Histórico analítico de contas e compromissos financeiros por período."
            :back-href="route('payables.index')"
            back-label="Voltar a Contas a pagar"
        >
            <x-slot:actions>
                <x-button variant="primary" :href="route('payables.create')">Nova conta</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-flash />

        {{-- Pílulas de período (due_date) --}}
        <x-period-pills
            :years="$years"
            :months="$months"
            :selected-year="$monthStart->year"
            :selected-month="$monthStart->month"
            route="payables.advanced"
            :params="['situacao' => $statusFilter !== 'todas' ? $statusFilter : null, 'busca' => $search]"
        />

        {{-- Cards do período em R$: total, pago, pendente, vencido --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat
                label="Total no período"
                :value="\App\Support\Money::format($periodTotalCents)"
                tone="neutral"
            />
            <x-stat
                label="Total pago"
                :value="\App\Support\Money::format($periodPaidCents)"
                tone="green"
            />
            <x-stat
                label="Total pendente"
                :value="\App\Support\Money::format($periodPendingCents)"
                tone="blue"
            />
            <x-stat
                label="Total vencido"
                :value="\App\Support\Money::format($periodOverdueCents)"
                :tone="$periodOverdueCents > 0 ? 'red' : 'neutral'"
            />
        </div>

        {{-- Filtros: situação e busca por texto --}}
        <x-card>
            <form method="GET" action="{{ route('payables.advanced') }}" data-loading-text="Buscando..." class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="ano" value="{{ $monthStart->year }}">
                <input type="hidden" name="mes" value="{{ $monthStart->month }}">

                {{-- Filtro de situação --}}
                <div class="flex items-center gap-1.5 rounded-lg border border-line bg-surface p-1 text-xs">
                    @foreach (['todas' => 'Todas', 'pendentes' => 'Pendentes', 'vencidas' => 'Vencidas', 'pagas' => 'Pagas'] as $key => $label)
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

                {{-- Busca por texto na descrição --}}
                <div class="relative min-w-[200px] flex-1">
                    <input
                        type="search"
                        name="busca"
                        value="{{ $search }}"
                        placeholder="Buscar na descrição da conta..."
                        class="w-full text-sm @error('busca') border-red6 @enderror"
                    >
                </div>
                <x-button type="submit" variant="secondary">Buscar</x-button>

                @if ($search !== '' || $statusFilter !== 'todas')
                    <a
                        href="{{ route('payables.advanced', ['ano' => $monthStart->year, 'mes' => $monthStart->month]) }}"
                        class="text-sm font-medium text-ink-soft hover:text-ink"
                    >
                        Limpar filtros
                    </a>
                @endif
            </form>
        </x-card>

        {{-- Tabela de contas --}}
        @if ($payables->isEmpty())
            <x-empty-state
                title="Nenhuma conta encontrada"
                :description="$search !== '' || $statusFilter !== 'todas' ? 'Nenhuma conta corresponde aos filtros aplicados neste período.' : 'Não há contas a pagar cadastradas para o período selecionado.'"
            >
                <x-button variant="primary" :href="route('payables.create')">Registrar primeira conta</x-button>
            </x-empty-state>
        @else
            <x-card size="none">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2 text-left text-xs font-semibold uppercase tracking-wide text-ink-soft">
                                <th class="px-5 py-3">Vencimento</th>
                                <th class="px-5 py-3">Descrição</th>
                                <th class="px-5 py-3 text-right">Valor</th>
                                <th class="px-5 py-3">Situação</th>
                                <th class="px-5 py-3">Pago em</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payables as $payable)
                                @php $status = $payable->status($today); @endphp
                                <tr class="{{ $status === 'vencida' ? 'bg-red1/50 hover:bg-red1/70' : 'hover:bg-surface-2' }}">
                                    <td class="px-5 py-3 font-medium tabular-nums {{ $status === 'vencida' ? 'text-red7 font-semibold' : 'text-ink' }}">
                                        {{ $payable->due_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-ink">
                                        {{ $payable->description }}
                                        @if ($payable->notes)
                                            <p class="text-xs text-ink-soft">{{ $payable->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink">
                                        {{ \App\Support\Money::format((int) round($payable->amount * 100)) }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-badge :status="$status" />
                                    </td>
                                    <td class="px-5 py-3 tabular-nums text-ink-soft">
                                        {{ $payable->paid_at ? $payable->paid_at->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if (! $payable->isPaid())
                                                <form method="POST" action="{{ route('payables.pay', $payable->id) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold uppercase tracking-wide text-grn7 hover:text-grn6"
                                                    >
                                                        <x-icons.check /> Pagar
                                                    </button>
                                                </form>
                                            @endif

                                            <a
                                                href="{{ route('payables.edit', array_filter([
                                                    'payable' => $payable->id,
                                                    'origem' => 'advanced',
                                                    'ano' => $monthStart->year,
                                                    'mes' => $monthStart->month,
                                                    'situacao' => $statusFilter !== 'todas' ? $statusFilter : null,
                                                    'busca' => $search !== '' ? $search : null,
                                                    'page' => $payables->currentPage() > 1 ? $payables->currentPage() : null,
                                                ])) }}"
                                                class="inline-flex items-center gap-1 text-sm font-medium text-accent transition-colors hover:text-blu5"
                                            >
                                                <x-icons.pencil /> EDITAR
                                            </a>
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
                {{ $payables->links() }}
            </div>
        @endif
    </div>
</x-layout>
