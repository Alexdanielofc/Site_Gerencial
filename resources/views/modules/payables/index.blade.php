<x-layout title="Contas a pagar">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Contas a pagar"
            description="Acompanhe compromissos e pagamentos futuros."
            :back-href="route('site.index')"
            back-label="Voltar ao início"
        >
            <x-slot:actions>
                <x-button variant="secondary" :href="route('payables.advanced')">AVANÇADO</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-flash />

        {{-- Indicadores --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat
                label="Total em aberto"
                :value="\App\Support\Money::format($totalOpenCents)"
                tone="neutral"
            />
            <x-stat
                label="Contas vencidas"
                :value="\App\Support\Money::format($overdueTotalCents)"
                :hint="$overdueCount . ' ' . ($overdueCount === 1 ? 'conta vencida' : 'contas vencidas')"
                :tone="$overdueCount > 0 ? 'red' : 'neutral'"
            />
            <x-stat
                label="A vencer em até 7 dias"
                :value="\App\Support\Money::format($upcomingTotalCents)"
                :hint="$upcomingCount . ' ' . ($upcomingCount === 1 ? 'conta próxima' : 'contas próximas')"
                tone="blue"
            />
        </div>

        {{-- Formulário de nova conta a pagar --}}
        <x-card size="lg">
            <h2 class="mb-5 text-lg font-semibold text-ink">Registrar conta a pagar</h2>

            <form method="POST" action="{{ route('payables.store') }}" novalidate>
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Descrição --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="description" class="text-sm font-medium text-ink">Descrição da conta</label>
                        <input
                            id="description"
                            name="description"
                            type="text"
                            placeholder="Ex.: Energia elétrica, Fornecedor X"
                            value="{{ old('description') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('description') border-red6 @enderror"
                            required
                        >
                        @error('description')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Valor --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="amount" class="text-sm font-medium text-ink">Valor (R$)</label>
                        <input
                            id="amount"
                            name="amount"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="0,00"
                            value="{{ old('amount') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('amount') border-red6 @enderror"
                            required
                        >
                        @error('amount')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Data de vencimento --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="due_date" class="text-sm font-medium text-ink">Data de vencimento</label>
                        <input
                            id="due_date"
                            name="due_date"
                            type="date"
                            value="{{ old('due_date', $today->toDateString()) }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('due_date') border-red6 @enderror"
                            required
                        >
                        @error('due_date')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observações --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="notes" class="text-sm font-medium text-ink">Observações <span class="text-xs font-normal text-ink-soft">(opcional)</span></label>
                        <input
                            id="notes"
                            name="notes"
                            type="text"
                            placeholder="Código de barras, chave PIX, notas de referência"
                            value="{{ old('notes') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('notes') border-red6 @enderror"
                        >
                        @error('notes')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <x-button type="submit">Salvar conta</x-button>
                </div>
            </form>
        </x-card>

        {{-- Lista 'Em atenção' --}}
        <section aria-labelledby="attention-heading" class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="attention-heading" class="text-lg font-semibold text-ink">
                    Em atenção (Vencidas e a vencer em até 7 dias)
                </h2>
                <span class="text-xs text-ink-soft">Ordenadas pelo vencimento</span>
            </div>

            @if ($attentionPayables->isEmpty())
                <x-empty-state
                    title="Nenhuma conta requer atenção no momento"
                    description="Não há contas vencidas ou com vencimento nos próximos 7 dias pendentes de pagamento."
                />
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
                                    <th class="px-5 py-3">Observações</th>
                                    <th class="px-5 py-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($attentionPayables as $payable)
                                    @php $status = $payable->status($today); @endphp
                                    <tr class="{{ $status === 'vencida' ? 'bg-red1/40 hover:bg-red1/60' : 'hover:bg-surface-2' }}">
                                        <td class="px-5 py-3 font-medium tabular-nums {{ $status === 'vencida' ? 'text-red7 font-semibold' : 'text-ink' }}">
                                            {{ $payable->due_date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-5 py-3 text-ink">{{ $payable->description }}</td>
                                        <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink">
                                            {{ \App\Support\Money::format((int) round($payable->amount * 100)) }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <x-badge :status="$status" />
                                        </td>
                                        <td class="px-5 py-3 text-ink-soft">{{ $payable->notes ?: '—' }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <form method="POST" action="{{ route('payables.pay', $payable->id) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold uppercase tracking-wide text-grn7 hover:text-grn6"
                                                    >
                                                        <x-icons.check /> Pagar
                                                    </button>
                                                </form>

                                                <a
                                                    href="{{ route('payables.edit', ['payable' => $payable->id, 'origem' => 'index']) }}"
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
            @endif
        </section>
    </div>
</x-layout>
