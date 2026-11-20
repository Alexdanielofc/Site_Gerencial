<x-layout title="Editar conta a pagar">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Editar conta a pagar"
            :description="'Conta: ' . $payable->description"
            :back-href="$backHref"
            back-label="Voltar"
        />

        <x-flash />

        <x-card size="lg" class="mx-auto w-full max-w-2xl">
            <form method="POST" action="{{ route('payables.update', $payable->id) }}" data-loading-text="Salvando..." novalidate>
                @csrf
                @method('PUT')

                <input type="hidden" name="origem" value="{{ $origin }}">
                @if ($context['ano']) <input type="hidden" name="ano" value="{{ $context['ano'] }}"> @endif
                @if ($context['mes']) <input type="hidden" name="mes" value="{{ $context['mes'] }}"> @endif
                @if ($context['busca']) <input type="hidden" name="busca" value="{{ $context['busca'] }}"> @endif
                @if ($context['situacao']) <input type="hidden" name="situacao" value="{{ $context['situacao'] }}"> @endif
                @if ($context['page']) <input type="hidden" name="page" value="{{ $context['page'] }}"> @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Descrição --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="description" class="text-sm font-medium text-ink">Descrição</label>
                        <input
                            id="description"
                            name="description"
                            type="text"
                            placeholder="Ex.: Internet, Fornecedor de bobinas"
                            value="{{ old('description', $payable->description) }}"
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
                            value="{{ old('amount', number_format($payable->amount, 2, ',', '.')) }}"
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
                            value="{{ old('due_date', $payable->due_date->toDateString()) }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('due_date') border-red6 @enderror"
                            required
                        >
                        @error('due_date')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Data de pagamento (limpar para reabrir) --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="paid_at" class="text-sm font-medium text-ink">
                            Data de pagamento <span class="text-xs font-normal text-ink-soft">(deixe em branco se a conta estiver em aberto)</span>
                        </label>
                        <input
                            id="paid_at"
                            name="paid_at"
                            type="date"
                            max="{{ $today->toDateString() }}"
                            value="{{ old('paid_at', $payable->paid_at ? $payable->paid_at->toDateString() : '') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('paid_at') border-red6 @enderror"
                        >
                        @error('paid_at')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observações --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="notes" class="text-sm font-medium text-ink">Observações <span class="text-xs font-normal text-ink-soft">(opcional)</span></label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="Informações adicionais da conta"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('notes') border-red6 @enderror"
                        >{{ old('notes', $payable->notes) }}</textarea>
                        @error('notes')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <x-button type="submit">Atualizar conta</x-button>
                    <x-button
                        variant="secondary"
                        :href="$backHref"
                    >
                        Cancelar
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layout>
