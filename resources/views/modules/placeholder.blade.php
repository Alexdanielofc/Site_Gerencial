<x-layout :title="$moduleTitle">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <div class="flex flex-col gap-3">
            <a href="{{ route('site.index') }}" class="inline-flex w-fit items-center gap-2 text-sm font-medium text-blu6 transition-colors hover:text-blu5">
                <x-icons.arrow-left />
                Voltar ao início
            </a>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-blu6">Módulo em preparação</span>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $moduleTitle }}</h1>
                <p class="max-w-2xl text-sm leading-6 text-cin6 sm:text-base">{{ $moduleDescription }}</p>
            </div>
        </div>

        <section class="rounded-xl border border-cin2 bg-branco p-6 shadow-sm sm:p-8">
            <p class="font-medium">Esta área será desenvolvida em uma próxima etapa.</p>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-cin6">
                Por enquanto, esta página contém apenas a navegação e a estrutura visual. Nenhum cadastro, cálculo ou alteração de dados está disponível.
            </p>
        </section>
    </div>
</x-layout>
