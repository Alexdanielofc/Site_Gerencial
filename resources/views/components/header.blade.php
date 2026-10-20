<header class="border-b border-cin6 bg-cin8 text-cin3">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
        <a href="{{ route('site.index') }}" class="group flex items-center gap-3 transition-colors hover:text-blu3" aria-label="Site Gerencial, início">
            <span class="starter-mark flex size-11 items-center justify-center rounded-lg bg-blu6 font-mono text-sm font-bold text-branco transition-transform group-hover:-rotate-6">
                SG
            </span>
            <span class="flex flex-col">
                <span class="font-bold leading-tight">Site Gerencial</span>
                <span class="font-mono text-[0.78rem] uppercase tracking-[0.18em]">Empresa</span>
            </span>
        </a>

        <x-navbar />
    </div>
</header>
