<nav aria-label="Navegação principal" class="flex flex-wrap items-center gap-1 font-mono text-xs font-semibold sm:gap-2 sm:text-sm">
    <a href="{{ route('site.index') }}"
       @class([
           'rounded px-2 py-2 transition-colors hover:text-blue-300',
           'font-bold text-blue-300 underline decoration-blue-300 decoration-2 underline-offset-4' => request()->routeIs('site.index'),
       ])
       @if (request()->routeIs('site.index')) aria-current="page" @endif>
        Início
    </a>
    <a href="{{ route('sales.index') }}"
       @class([
           'rounded px-2 py-2 transition-colors hover:text-blue-300',
           'font-bold text-blue-300 underline decoration-blue-300 decoration-2 underline-offset-4' => request()->routeIs('sales.*'),
       ])
       @if (request()->routeIs('sales.*')) aria-current="page" @endif>
        Vendas e metas
    </a>
    <a href="{{ route('invoices.index') }}"
       @class([
           'rounded px-2 py-2 transition-colors hover:text-blue-300',
           'font-bold text-blue-300 underline decoration-blue-300 decoration-2 underline-offset-4' => request()->routeIs('invoices.*'),
       ])
       @if (request()->routeIs('invoices.*')) aria-current="page" @endif>
        Notas fiscais
    </a>
    <a href="{{ route('payables.index') }}"
       @class([
           'rounded px-2 py-2 transition-colors hover:text-blue-300',
           'font-bold text-blue-300 underline decoration-blue-300 decoration-2 underline-offset-4' => request()->routeIs('payables.*'),
       ])
       @if (request()->routeIs('payables.*')) aria-current="page" @endif>
        Contas a pagar
    </a>
</nav>
