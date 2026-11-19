@props(['href', 'selected' => false])

<a href="{{ $href }}"
   {{ $attributes->class([
       'inline-flex items-center rounded-full border px-4 py-1.5 text-sm font-medium transition-colors motion-reduce:transition-none motion-reduce:transform-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blu6',
       'border-blu6 bg-blu6 text-branco' => $selected,
       'border-line bg-surface text-ink hover:border-blu3 hover:bg-blu1' => ! $selected,
   ]) }}
   @if ($selected) aria-current="true" @endif>
    {{ $slot }}
</a>
