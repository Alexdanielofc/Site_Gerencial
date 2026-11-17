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

        {{-- Flash messages --}}
        @if (session('error'))
            <x-alert type="danger">{{ session('error') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert type="danger" title="Verifique os campos">
                <ul class="mt-1 list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <x-card size="lg" class="mx-auto w-full max-w-xl">
            <form
                method="POST"
                action="{{ route('sales.update', ['date' => $carbon->toDateString()]) }}"
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
                        <label class="text-sm font-medium text-cin8">Data</label>
                        <p class="rounded-lg border border-cin2 bg-cin1 px-3 py-2.5 text-cin6">
                            {{ $carbon->format('d/m/Y') }} — {{ $dayOfWeek }}
                        </p>
                    </div>

                    {{-- Valor --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="amount" class="text-sm font-medium text-cin8">Valor da venda (R$)</label>
                        <input
                            id="amount"
                            name="amount"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            class="rounded-lg border border-cin3 px-3 py-2.5 text-cin8 focus:border-blu6 focus:outline-none focus:ring-1 focus:ring-blu6 @error('amount') border-red6 @enderror"
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
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-cin2 bg-cin1 px-4 py-3 text-sm">
                            <span class="text-cin6">Expectativa do dia</span>
                            <span class="font-semibold tabular-nums">{{ \App\Support\Money::format((int) round($expectation->amount * 100)) }}</span>
                        </div>
                    @else
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-cin2 bg-cin1 px-4 py-3 text-sm">
                            <span class="text-cin6">Expectativa do dia</span>
                            <span class="text-cin6">Sem expectativa definida</span>
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
