<x-layout title="Nova nota fiscal">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Nova nota fiscal"
            description="Cadastre uma nota fiscal para qualquer data até hoje."
            :back-href="route('invoices.advanced')"
            back-label="Voltar ao avançado"
        />

        <x-flash />

        <x-card size="lg" class="mx-auto w-full max-w-2xl">
            <form method="POST" action="{{ route('invoices.store') }}" data-loading-text="Salvando..." novalidate>
                @csrf
                <input type="hidden" name="_submission_id" value="{{ old('_submission_id', (string) \Illuminate\Support\Str::uuid()) }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Data de emissão --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="issue_date" class="text-sm font-medium text-ink">Data de emissão</label>
                        <input
                            id="issue_date"
                            name="issue_date"
                            type="date"
                            max="{{ $today->toDateString() }}"
                            value="{{ old('issue_date', $today->toDateString()) }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('issue_date') border-red6 @enderror"
                            required
                        >
                        @error('issue_date')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Número da nota --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="invoice_number" class="text-sm font-medium text-ink">Número da nota <span class="text-xs font-normal text-ink-soft">(opcional)</span></label>
                        <input
                            id="invoice_number"
                            name="invoice_number"
                            type="text"
                            placeholder="Ex.: NF-1050"
                            value="{{ old('invoice_number') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('invoice_number') border-red6 @enderror"
                        >
                        @error('invoice_number')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nome do cliente --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="customer_name" class="text-sm font-medium text-ink">Cliente</label>
                        <input
                            id="customer_name"
                            name="customer_name"
                            type="text"
                            placeholder="Razão social ou nome do cliente"
                            value="{{ old('customer_name') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('customer_name') border-red6 @enderror"
                            required
                        >
                        @error('customer_name')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CNPJ --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="customer_cnpj" class="text-sm font-medium text-ink">CNPJ <span class="text-xs font-normal text-ink-soft">(opcional, 14 dígitos)</span></label>
                        <input
                            id="customer_cnpj"
                            name="customer_cnpj"
                            type="text"
                            inputmode="numeric"
                            placeholder="00.000.000/0000-00"
                            value="{{ old('customer_cnpj') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('customer_cnpj') border-red6 @enderror"
                        >
                        @error('customer_cnpj')
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

                    {{-- Observações --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="notes" class="text-sm font-medium text-ink">Observações <span class="text-xs font-normal text-ink-soft">(opcional)</span></label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="Informações adicionais da nota fiscal"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('notes') border-red6 @enderror"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <x-button type="submit">Salvar nota</x-button>
                    <x-button variant="secondary" :href="route('invoices.advanced')">Cancelar</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layout>
