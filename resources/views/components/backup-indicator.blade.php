@php
    $summary = \App\Support\BackupStatus::summary();
@endphp

<a
    href="{{ route('backup.settings') }}"
    title="{{ $summary['title'] }}"
    class="inline-flex items-center gap-1.5 rounded-full border border-chrome-line bg-surface-2/40 px-2.5 py-1 text-xs text-chrome-ink transition-colors hover:border-chrome-ink hover:text-white focus:outline-none focus:ring-1 focus:ring-blu4"
    aria-label="{{ $summary['title'] }}"
>
    <span class="shrink-0 text-chrome-ink opacity-80">
        <x-icons.clock class="size-3.5" />
    </span>
    <span class="size-1.5 shrink-0 rounded-full {{ $summary['color_dot'] }}" aria-hidden="true"></span>
    <span class="font-medium {{ $summary['color_text'] }}">{{ $summary['label'] }}</span>
</a>
