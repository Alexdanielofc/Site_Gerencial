@props(['exceptErrors' => false])

@php
    $flashes = array_filter([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'danger', 'message' => session('error')],
        ['type' => 'warning', 'message' => session('warning')],
        ['type' => 'info', 'message' => session('info')],
    ], fn ($f) => ! empty($f['message']));

    $viewErrors = $errors ?? session('errors');
    $hasErrors = ! $exceptErrors && $viewErrors instanceof \Illuminate\Support\ViewErrorBag && $viewErrors->any();
@endphp

@if (! empty($flashes) || $hasErrors)
    <div {{ $attributes->class(['flex flex-col gap-3']) }}>
        @foreach ($flashes as $flash)
            <x-alert :type="$flash['type']">
                {{ $flash['message'] }}
            </x-alert>
        @endforeach

        @if ($hasErrors)
            <x-alert type="danger" title="Verifique os campos">
                <ul class="mt-1 list-disc pl-4">
                    @foreach ($viewErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif
    </div>
@endif
