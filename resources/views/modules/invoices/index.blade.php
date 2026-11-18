<x-layout title="Notas fiscais">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Notas fiscais"
            description="Organize as notas emitidas para clientes e acompanhe os valores."
            :back-href="route('site.index')"
            back-label="Voltar ao início"
        >
            <x-slot:actions>
                <x-button variant="secondary" :href="route('invoices.advanced')">AVANÇADO</x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Flash messages --}}
        @if (session('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif
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

        {{-- Indicadores do mês atual --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat
                label="Total emitido no mês"
                :value="\App\Support\Money::format($monthTotalCents)"
                tone="blue"
            />
            <x-stat
                label="Notas emitidas no mês"
                :value="(string) $monthCount"
                tone="neutral"
            />
            <x-stat
                label="Emitido hoje"
                :value="\App\Support\Money::format($todayTotalCents)"
                :hint="$todayCount . ' ' . ($todayCount === 1 ? 'nota hoje' : 'notas hoje')"
                tone="neutral"
            />
        </div>

        {{-- Formulário de nova nota fiscal --}}
        <x-card size="lg">
            <h2 class="mb-5 text-lg font-semibold text-ink">Registrar nota fiscal</h2>

            <form method="POST" action="{{ route('invoices.store') }}" novalidate>
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
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

                    {{-- Observações --}}
                    <div class="flex flex-col gap-1.5 sm:col-span-2 lg:col-span-3">
                        <label for="notes" class="text-sm font-medium text-ink">Observações <span class="text-xs font-normal text-ink-soft">(opcional)</span></label>
                        <input
                            id="notes"
                            name="notes"
                            type="text"
                            placeholder="Informações adicionais da nota fiscal"
                            value="{{ old('notes') }}"
                            class="rounded-lg border border-line px-3 py-2 text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent @error('notes') border-red6 @enderror"
                        >
                        @error('notes')
                            <p class="text-xs text-red6" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <x-button type="submit">Salvar nota fiscal</x-button>
                </div>
            </form>
        </x-card>

        {{-- Lista de notas de hoje --}}
        <section aria-labelledby="today-invoices-heading" class="flex flex-col gap-3">
            <h2 id="today-invoices-heading" class="text-lg font-semibold text-ink">
                Notas fiscais de hoje ({{ $today->format('d/m/Y') }})
            </h2>

            @if ($todayInvoices->isEmpty())
                <x-empty-state
                    title="Nenhuma nota registrada hoje"
                    description="Utilize o formulário acima para registrar uma nova nota fiscal emitida hoje."
                />
            @else
                <x-card size="none">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2 text-left text-xs font-semibold uppercase tracking-wide text-ink-soft">
                                    <th class="px-5 py-3">Número</th>
                                    <th class="px-5 py-3">Cliente</th>
                                    <th class="px-5 py-3">CNPJ</th>
                                    <th class="px-5 py-3 text-right">Valor</th>
                                    <th class="px-5 py-3">Observações</th>
                                    <th class="px-5 py-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-cin2">
                                @foreach ($todayInvoices as $invoice)
                                    <tr class="hover:bg-surface-2">
                                        <td class="px-5 py-3 font-medium text-ink">
                                            {{ $invoice->invoice_number ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-ink">{{ $invoice->customer_name }}</td>
                                        <td class="px-5 py-3 tabular-nums text-ink-soft">{{ $invoice->formatted_cnpj ?: '—' }}</td>
                                        <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink">
                                            {{ \App\Support\Money::format((int) round($invoice->amount * 100)) }}
                                        </td>
                                        <td class="px-5 py-3 text-ink-soft">{{ $invoice->notes ?: '—' }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <a
                                                href="{{ route('invoices.edit', ['invoice' => $invoice->id, 'origem' => 'index']) }}"
                                                class="inline-flex items-center gap-1 text-sm font-medium text-accent transition-colors hover:text-blu5"
                                            >
                                                <x-icons.pencil /> EDITAR
                                            </a>
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
