@props([
    // A palette colour: primary · secondary · success · danger · warning · info · dark
    // (also brand = primary, neutral = secondary; see Acun\Ui\Support\Tone).
    'variant' => 'secondary',
    // The same as variant (progress, spinner and stat-card call it color).
    'color' => null,
    // Filled with the colour and its own text colour, instead of a light tint.
    'solid' => false,
])

@php
    $tone = \Acun\Ui\Support\Tone::name($color ?? $variant, 'secondary');
    $variants = $solid ? [
        'primary' => 'bg-primary text-on-primary',
        'secondary' => 'bg-secondary text-on-secondary',
        'success' => 'bg-success text-on-success',
        'danger' => 'bg-danger text-on-danger',
        'warning' => 'bg-warning text-on-warning',
        'info' => 'bg-info text-on-info',
        'dark' => 'bg-dark text-on-dark',
    ] : [
        'primary' => 'bg-primary-tint text-primary-tint-ink',
        'secondary' => 'bg-secondary-tint text-secondary-tint-ink',
        'success' => 'bg-success-tint text-success-tint-ink',
        'danger' => 'bg-danger-tint text-danger-tint-ink',
        'warning' => 'bg-warning-tint text-warning-tint-ink',
        'info' => 'bg-info-tint text-info-tint-ink',
        'dark' => 'bg-dark-soft text-dark-tint-ink',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ' . $variants[$tone]]) }}>
    {{ $slot }}
</span>
