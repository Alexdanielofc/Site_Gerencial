<header class="border-b border-gray-600 bg-gray-800 text-gray-300">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
        <a href="{{ route('site.index') }}" class="group flex items-center gap-3 transition-colors hover:text-blue-300" aria-label="Site Gerencial, início">
            <span class="starter-mark flex size-11 items-center justify-center rounded-lg bg-blue-600 font-mono text-sm font-bold text-white transition-transform group-hover:-rotate-3">
                SG
            </span>
            <span class="flex flex-col">
                <span class="font-bold leading-tight">Site Gerencial</span>
                <span class="font-mono text-[0.65rem] uppercase tracking-[0.18em]">Starter kit</span>
            </span>
        </a>

        <x-navbar />
    </div>
</header>
