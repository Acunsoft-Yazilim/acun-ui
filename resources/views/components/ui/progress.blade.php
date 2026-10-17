@props([
    // 0-100, a value coming from the server...
    'value' => null,
    // ...or an Alpine expression that changes on the client (e.g. upload progress).
    'alpine' => null,
    'label' => null,
    'size' => 'md',
    'striped' => false,
    'animated' => false,
    // Bar colour: primary · secondary · success · danger · warning · info · dark.
    'color' => 'primary',
    // The same as color (badge and alert call it variant).
    'variant' => null,
])

@php
    $color = $variant ?? $color;
    $percent = max(0, min(100, (int) ($value ?? 0)));
    $heightClass = ['sm' => 'h-1.5', 'md' => 'h-2.5', 'lg' => 'h-4'][$size] ?? 'h-2.5';
    // [track, bar]; the primary bar is the primary button's fill.
    [$trackClass, $barClass] = [
        'primary' => ['bg-brand-100 dark:bg-brand-500/15', 'bg-(--btn-primary-bg)'],
        'secondary' => ['bg-secondary-100 dark:bg-secondary-500/15', 'bg-secondary-500'],
        'success' => ['bg-emerald-100 dark:bg-emerald-500/15', 'bg-emerald-500'],
        'danger' => ['bg-red-100 dark:bg-red-500/15', 'bg-red-500'],
        'warning' => ['bg-amber-100 dark:bg-amber-500/15', 'bg-amber-500'],
        'info' => ['bg-sky-100 dark:bg-sky-500/15', 'bg-sky-500'],
        'dark' => ['bg-dark/10 dark:bg-zinc-700', 'bg-dark dark:bg-zinc-300'],
    ][\Acun\Ui\Support\Tone::name($color)];
@endphp

<div
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($alpine) :aria-valuenow="{{ $alpine }}" @else aria-valuenow="{{ $percent }}" @endif
    aria-label="{{ $label ?? __('Progress') }}"
    {{ $attributes->merge(['class' => "w-full overflow-hidden rounded-full {$trackClass} {$heightClass}"]) }}
>
    <div
        @class([
            'h-full rounded-full transition-[width] duration-500 ease-out motion-reduce:transition-none',
            $barClass,
            'progress-striped' => $striped || $animated,
            'progress-animated' => $animated,
        ])
        @if ($alpine) :style="'width: ' + ({{ $alpine }}) + '%'" @else style="width: {{ $percent }}%" @endif
    ></div>
</div>
