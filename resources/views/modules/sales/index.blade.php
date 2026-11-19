<x-layout title="Vendas e metas">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Vendas e metas"
            description="Acompanhe as vendas do dia e o progresso das metas."
            :back-href="route('site.index')"
            back-label="Voltar ao início"
        >
            <x-slot:actions>
                <x-button variant="primary" :href="route('sales.expectations.edit')">EXPECTATIVA DE VENDA MÊS</x-button>
                <x-button variant="secondary" :href="route('sales.advanced')">AVANÇADO</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-flash />

        {{-- Indicadores do mês --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat
                label="Vendido no mês"
                :value="\App\Support\Money::format($indicators['sold_cents'])"
                tone="blue"
            />
            <x-stat
                label="Meta acumulada até hoje"
                :value="\App\Support\Money::format($indicators['accrued_goal_cents'])"
                tone="neutral"
            />
            <x-stat
                label="% da meta acumulada"
                :value="\App\Support\Money::formatPercent($indicators['accrued_percent'])"
                :tone="$indicators['accrued_percent_tone']"
            />
        </div>

        {{-- Card principal: data, expectativa e formulário/registro --}}
        <x-card size="lg">
            <div class="flex flex-col gap-6">

                {{-- Data e dia da semana --}}
                <div class="flex items-center gap-3">
                    <span class="shrink-0 text-ink-soft"><x-icons.calendar /></span>
                    <div>
                        <p class="text-lg font-semibold">{{ $today->format('d/m/Y') }}</p>
                        <p class="text-sm text-ink-soft">{{ Str::ucfirst($today->translatedFormat('l')) }}</p>
                    </div>
                </div>

                {{-- Expectativa de hoje --}}
                @if ($todayExpectation && $todayExpectation->amount > 0)
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-line bg-surface-2 px-4 py-3 text-sm">
                        <span class="text-ink-soft">Expectativa de hoje</span>
                        <span class="font-semibold tabular-nums">{{ \App\Support\Money::format((int) round($todayExpectation->amount * 100)) }}</span>
                    </div>
                @else
                    <x-alert type="danger" title="DIA SEM EXPECTATIVA">
                        Nenhuma expectativa definida para hoje.
                        <a href="{{ route('sales.expectations.edit') }}" class="font-medium underline hover:no-underline">Definir expectativas do mês</a>
                    </x-alert>
                @endif

                <hr class="border-line">

                {{-- Venda de hoje --}}
                @if ($todaySale)
                    <div class="flex flex-col gap-3">
                        <x-alert type="success" title="Registro de hoje já adicionado.">
                            Valor registrado: <strong>{{ \App\Support\Money::format((int) round($todaySale->amount * 100)) }}</strong>
                        </x-alert>
                        <div>
                            <x-button
                                variant="secondary"
                                :href="route('sales.edit', ['date' => $today->toDateString(), 'origem' => 'index'])"
                            >
                                <x-icons.pencil /> EDITAR
                            </x-button>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('sales.store') }}" novalidate>
                        @csrf
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="amount" class="text-sm font-medium text-ink">Venda diária (R$)</label>
                                <input
                                    id="amount"
                                    name="amount"
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    class="rounded-lg border border-line px-3 py-2.5 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('amount') border-red6 @enderror"
                                    placeholder="0,00"
                                    value="{{ old('amount') }}"
                                    aria-describedby="{{ $errors->has('amount') ? 'amount-error' : '' }}"
                                >
                                @error('amount')
                                    <p id="amount-error" class="text-sm text-red6" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <x-button type="submit">Salvar</x-button>
                            </div>
                        </div>
                    </form>
                @endif

            </div>
        </x-card>
    </div>
</x-layout>
