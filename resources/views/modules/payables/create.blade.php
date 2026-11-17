<x-layout title="Nova conta a pagar">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Nova conta a pagar"
            description="Cadastre um novo compromisso financeiro para acompanhamento."
            :back-href="route('payables.advanced')"
            back-label="Voltar ao avançado"
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

        <x-card size="lg" class="mx-auto w-full max-w-2xl">
            <form method="POST" action="{{ route('payables.store') }}" novalidate>
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Descrição --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="description" class="text-sm font-medium text-cin8">Descrição</label>
                        <input
                            id="description"
                            name="description"
                            type="text"
                            placeholder="Ex.: Internet, Fornecedor de bobinas"
                            value="{{ old('description') }}"
                            class="rounded-lg border border-cin3 px-3 py-2 text-cin8 focus:border-blu6 focus:outline-none focus:ring-1 focus:ring-blu6 @error('description') border-red6 @enderror"
                            required
                        >
                        @error('description')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Valor --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="amount" class="text-sm font-medium text-cin8">Valor (R$)</label>
                        <input
                            id="amount"
                            name="amount"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="0,00"
                            value="{{ old('amount') }}"
                            class="rounded-lg border border-cin3 px-3 py-2 text-cin8 focus:border-blu6 focus:outline-none focus:ring-1 focus:ring-blu6 @error('amount') border-red6 @enderror"
                            required
                        >
                        @error('amount')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Data de vencimento --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="due_date" class="text-sm font-medium text-cin8">Data de vencimento</label>
                        <input
                            id="due_date"
                            name="due_date"
                            type="date"
                            value="{{ old('due_date', $today->toDateString()) }}"
                            class="rounded-lg border border-cin3 px-3 py-2 text-cin8 focus:border-blu6 focus:outline-none focus:ring-1 focus:ring-blu6 @error('due_date') border-red6 @enderror"
                            required
                        >
                        @error('due_date')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observações --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="notes" class="text-sm font-medium text-cin8">Observações <span class="text-xs font-normal text-cin6">(opcional)</span></label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="Informações adicionais da conta"
                            class="rounded-lg border border-cin3 px-3 py-2 text-cin8 focus:border-blu6 focus:outline-none focus:ring-1 focus:ring-blu6 @error('notes') border-red6 @enderror"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <x-button type="submit">Salvar conta</x-button>
                    <x-button variant="secondary" :href="route('payables.advanced')">Cancelar</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layout>
