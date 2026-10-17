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
    // [track, bar]: the track is the colour's soft tint; the primary bar is the primary fill, the
    // others the colour's line (its 500 shade; 400 in the dark theme), the dark one its line too.
    [$trackClass, $barClass] = [
        'primary' => ['bg-primary-soft', 'bg-primary'],
        'secondary' => ['bg-secondary-soft', 'bg-secondary'],
        'success' => ['bg-success-soft', 'bg-success-line'],
        'danger' => ['bg-danger-soft', 'bg-danger-line'],
        'warning' => ['bg-warning-soft', 'bg-warning-line'],
        'info' => ['bg-info-soft', 'bg-info-line'],
        'dark' => ['bg-dark-soft', 'bg-dark-line'],
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
