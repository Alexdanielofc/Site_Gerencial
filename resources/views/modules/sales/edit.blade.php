<x-layout title="Editar venda — {{ $carbon->format('d/m/Y') }}">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Editar venda"
            :description="'Data: ' . $carbon->format('d/m/Y') . ' — ' . $dayOfWeek"
            :back-href="$origin === 'advanced'
                ? route('sales.advanced', array_filter(['ano' => $ano, 'mes' => $mes]))
                : route('sales.index')"
            back-label="Voltar"
        />

        <x-flash />

        <x-card size="lg" class="mx-auto w-full max-w-xl">
            <form
                method="POST"
                action="{{ route('sales.update', ['date' => $carbon->toDateString()]) }}"
                data-loading-text="Salvando..."
                novalidate
            >
                @csrf
                @method('PUT')

                <input type="hidden" name="origem" value="{{ $origin }}">
                @if ($ano) <input type="hidden" name="ano" value="{{ $ano }}"> @endif
                @if ($mes) <input type="hidden" name="mes" value="{{ $mes }}"> @endif

                <div class="flex flex-col gap-6">

                    {{-- Data (somente leitura) --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-ink">Data</label>
                        <p class="rounded-lg border border-line bg-surface-2 px-3 py-2.5 text-ink-soft">
                            {{ $carbon->format('d/m/Y') }} — {{ $dayOfWeek }}
                        </p>
                    </div>

                    {{-- Valor --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="amount" class="text-sm font-medium text-ink">Valor da venda (R$)</label>
                        <input
                            id="amount"
                            name="amount"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            class="rounded-lg border border-line px-3 py-2.5 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('amount') border-red6 @enderror"
                            placeholder="0,00"
                            value="{{ old('amount', $sale ? number_format($sale->amount, 2, ',', '.') : '') }}"
                            aria-describedby="{{ $errors->has('amount') ? 'amount-error' : '' }}"
                        >
                        @error('amount')
                            <p id="amount-error" class="text-sm text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Expectativa do dia (só leitura) --}}
                    @if ($expectation)
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-line bg-surface-2 px-4 py-3 text-sm">
                            <span class="text-ink-soft">Expectativa do dia</span>
                            <span class="font-semibold tabular-nums">{{ \App\Support\Money::format((int) round($expectation->amount * 100)) }}</span>
                        </div>
                    @else
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-line bg-surface-2 px-4 py-3 text-sm">
                            <span class="text-ink-soft">Expectativa do dia</span>
                            <span class="text-ink-soft">Sem expectativa definida</span>
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <x-button type="submit">Salvar</x-button>
                        <x-button
                            variant="secondary"
                            :href="$origin === 'advanced'
                                ? route('sales.advanced', array_filter(['ano' => $ano, 'mes' => $mes]))
                                : route('sales.index')"
                        >
                            Cancelar
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</x-layout>
