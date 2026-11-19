<x-layout title="Início">
    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-5 py-8 sm:px-8 sm:py-10">

        {{-- Cabeçalho do Período e Seletor de Ano e Mês --}}
        <header class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-accent">Painel de controle</span>
                <h1 class="text-2xl font-semibold tracking-tight text-ink sm:text-3xl first-letter:uppercase">
                    {{ $monthStart->translatedFormat('F \d\e Y') }}
                </h1>
            </div>

            <x-period-pills
                :years="$years"
                :months="$months"
                :selected-year="$monthStart->year"
                :selected-month="$monthStart->month"
                route="site.index"
            />
        </header>

        {{-- 3 Sections dos Módulos --}}
        <div class="flex flex-col gap-6">

            {{-- 1. VENDAS E METAS --}}
            <section aria-labelledby="sales-section-heading">
                <x-card size="lg" class="flex flex-col gap-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex flex-col gap-1">
                            <h2 id="sales-section-heading" class="text-lg font-semibold text-ink">Vendas e metas</h2>
                            <p class="text-sm text-ink-soft">Registro diário e acompanhamento das expectativas de vendas.</p>
                        </div>
                        <x-button variant="secondary" :href="route('sales.index')">
                            Abrir módulo <x-icons.arrow-right />
                        </x-button>
                    </div>

                    @if ($isCurrentMonth)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <x-stat
                                label="Total vendido no mês"
                                :value="\App\Support\Money::format($salesIndicators['sold_cents'])"
                                tone="blue"
                            />
                            <x-stat
                                label="Meta acumulada até hoje"
                                :value="\App\Support\Money::format($salesIndicators['accrued_goal_cents'])"
                                tone="neutral"
                            />
                            <x-stat
                                label="% da meta acumulada"
                                :value="\App\Support\Money::formatPercent($salesIndicators['accrued_percent'])"
                                :tone="$salesIndicators['accrued_percent_tone']"
                            />
                        </div>

                        {{-- Indicadores do dia atual --}}
                        <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-line bg-surface-2 p-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-ink">Hoje:</span>
                                @if ($salesIndicators['today_sale_cents'] !== null)
                                    <span class="font-semibold text-accent">
                                        {{ \App\Support\Money::format($salesIndicators['today_sale_cents']) }}
                                    </span>
                                @else
                                    <span class="text-ink-soft">Sem registro hoje</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-ink-soft">Expectativa de hoje:</span>
                                @if ($salesIndicators['today_expectation_cents'] !== null && $salesIndicators['today_expectation_cents'] > 0)
                                    <span class="font-semibold text-ink">
                                        {{ \App\Support\Money::format($salesIndicators['today_expectation_cents']) }}
                                    </span>
                                @else
                                    <span class="rounded bg-yel1 px-2 py-0.5 text-xs font-medium text-yel8">Pendente</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <x-stat
                                label="Total vendido"
                                :value="\App\Support\Money::format($salesIndicators['sold_cents'])"
                                tone="blue"
                            />
                            <x-stat
                                label="Meta completa do mês"
                                :value="\App\Support\Money::format($salesIndicators['full_goal_cents'])"
                                tone="neutral"
                            />
                            <x-stat
                                label="% da meta mensal"
                                :value="\App\Support\Money::formatPercent($salesIndicators['full_percent'])"
                                :tone="$salesIndicators['full_percent_tone']"
                            />
                        </div>
                    @endif
                </x-card>
            </section>

            {{-- 2. NOTAS FISCAIS --}}
            <section aria-labelledby="invoices-section-heading">
                <x-card size="lg" class="flex flex-col gap-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex flex-col gap-1">
                            <h2 id="invoices-section-heading" class="text-lg font-semibold text-ink">Notas fiscais</h2>
                            <p class="text-sm text-ink-soft">Organização de notas emitidas para clientes e faturamento.</p>
                        </div>
                        <x-button variant="secondary" :href="route('invoices.index')">
                            Abrir módulo <x-icons.arrow-right />
                        </x-button>
                    </div>

                    @if ($invoiceCount === 0)
                        <x-empty-state
                            title="Nenhuma nota registrada neste período"
                            description="Não há notas fiscais emitidas cadastradas para o período selecionado."
                        />
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $isCurrentMonth ? 'lg:grid-cols-3' : '' }}">
                            <x-stat
                                label="Valor total emitido"
                                :value="\App\Support\Money::format($invoiceTotalCents)"
                                tone="blue"
                            />
                            <x-stat
                                label="Quantidade de notas"
                                :value="(string) $invoiceCount"
                                tone="neutral"
                            />
                            @if ($isCurrentMonth)
                                <x-stat
                                    label="Emitido hoje"
                                    :value="\App\Support\Money::format($todayInvoiceTotalCents)"
                                    :hint="$todayInvoiceCount . ' ' . ($todayInvoiceCount === 1 ? 'nota hoje' : 'notas hoje')"
                                    tone="neutral"
                                />
                            @endif
                        </div>
                    @endif
                </x-card>
            </section>

            {{-- 3. CONTAS A PAGAR --}}
            <section aria-labelledby="payables-section-heading">
                <x-card size="lg" class="flex flex-col gap-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex flex-col gap-1">
                            <h2 id="payables-section-heading" class="text-lg font-semibold text-ink">Contas a pagar</h2>
                            <p class="text-sm text-ink-soft">Acompanhamento de vencimentos e compromissos financeiros.</p>
                        </div>
                        <x-button variant="secondary" :href="route('payables.index')">
                            Abrir módulo <x-icons.arrow-right />
                        </x-button>
                    </div>

                    {{-- Resumo do Período Selecionado --}}
                    <div>
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-ink-soft">
                            Resumo do período ({{ $monthStart->translatedFormat('F/Y') }})
                        </h3>

                        @if ($payablePeriodCount === 0)
                            <x-empty-state
                                title="Nenhuma conta registrada neste período"
                                description="Não há compromissos financeiros com vencimento no mês selecionado."
                            />
                        @else
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <x-stat
                                    label="Total no período"
                                    :value="\App\Support\Money::format($payableTotalCents)"
                                    tone="neutral"
                                />
                                <x-stat
                                    label="Total pago"
                                    :value="\App\Support\Money::format($payablePaidCents)"
                                    tone="green"
                                />
                                <x-stat
                                    label="Total pendente"
                                    :value="\App\Support\Money::format($payablePendingCents)"
                                    tone="blue"
                                />
                            </div>
                        @endif
                    </div>

                    {{-- Bloco Atenção (Independente do período) --}}
                    <div class="flex flex-col gap-4 border-t border-line pt-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">
                                Bloco Atenção (Visão Imediata)
                            </h3>
                            <span class="text-xs text-ink-soft">Independente do mês selecionado</span>
                        </div>

                        {{-- Card de Vencidas --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border {{ $overdueCount > 0 ? 'border-red2 bg-red1/30' : 'border-line bg-surface-2' }} p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide {{ $overdueCount > 0 ? 'text-red7' : 'text-ink-soft' }}">
                                    Contas vencidas em aberto
                                </p>
                                <p class="mt-2 text-2xl font-bold tabular-nums {{ $overdueCount > 0 ? 'text-red6' : 'text-ink' }}">
                                    {{ \App\Support\Money::format($overdueTotalCents) }}
                                </p>
                                <p class="mt-1 text-xs text-ink-soft">
                                    {{ $overdueCount === 0 ? 'Nenhuma conta vencida.' : $overdueCount . ' ' . ($overdueCount === 1 ? 'conta vencida necessita pagamento.' : 'contas vencidas necessitam pagamento.') }}
                                </p>
                            </div>

                            {{-- Próximos Vencimentos (até 5 contas nos próximos 7 dias) --}}
                            <div class="rounded-xl border border-line bg-surface p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">
                                    Próximos vencimentos (próximos 7 dias)
                                </p>

                                @if ($upcomingPayables->isEmpty())
                                    <p class="mt-3 text-sm text-ink-soft">Nenhuma conta com vencimento nos próximos 7 dias.</p>
                                @else
                                    <ul class="mt-3 divide-y divide-line text-sm">
                                        @foreach ($upcomingPayables as $payable)
                                            <li class="flex items-center justify-between py-2 text-xs">
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-ink">{{ $payable->description }}</span>
                                                    <span class="text-ink-soft">{{ $payable->due_date->format('d/m/Y') }}</span>
                                                </div>
                                                <span class="font-semibold tabular-nums text-ink">
                                                    {{ \App\Support\Money::format((int) round($payable->amount * 100)) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                </x-card>
            </section>

        </div>
    </div>
</x-layout>
