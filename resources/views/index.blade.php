<x-layout title="Início">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <section aria-labelledby="modules-heading" class="flex flex-col gap-5">
            <div>
                <h2 id="modules-heading" class="text-lg font-semibold">Módulos do sistema</h2>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <article class="flex h-full flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-semibold">Vendas e metas</h3>
                            <p class="text-sm leading-5 text-gray-600">Registro diário e acompanhamento das expectativas de vendas.</p>
                        </div>
                        <span class="rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white">Em breve</span>
                    </div>
                    <a href="{{ route('sales.index') }}" class="mt-auto inline-flex w-fit items-center gap-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-500">
                        Acessar módulo <x-icons.arrow-right />
                    </a>
                </article>

                <article class="flex h-full flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-semibold">Notas fiscais</h3>
                            <p class="text-sm leading-5 text-gray-600">Organização de notas emitidas para clientes CNPJ e pagamentos.</p>
                        </div>
                        <span class="rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white">Em breve</span>
                    </div>
                    <a href="{{ route('invoices.index') }}" class="mt-auto inline-flex w-fit items-center gap-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-500">
                        Acessar módulo <x-icons.arrow-right />
                    </a>
                </article>

                <article class="flex h-full flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-semibold">Contas a pagar</h3>
                            <p class="text-sm leading-5 text-gray-600">Acompanhamento de vencimentos e pagamentos da papelaria.</p>
                        </div>
                        <span class="rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white">Em breve</span>
                    </div>
                    <a href="{{ route('payables.index') }}" class="mt-auto inline-flex w-fit items-center gap-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-500">
                        Acessar módulo <x-icons.arrow-right />
                    </a>
                </article>
            </div>
        </section>
    </div>
</x-layout>
