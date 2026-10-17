@props([
    // A palette colour: primary · secondary · success · danger · warning · info · dark
    // (also brand = primary, neutral = secondary; see Acun\Ui\Support\Tone).
    'variant' => 'secondary',
    // The same as variant (progress, spinner and stat-card call it color).
    'color' => null,
    // Filled with the colour and white text, instead of a light tint.
    'solid' => false,
])

@php
    $tone = \Acun\Ui\Support\Tone::name($color ?? $variant, 'secondary');
    $variants = $solid ? [
        'primary' => 'bg-(--btn-primary-bg) text-(--btn-primary-color,white)',
        'secondary' => 'bg-secondary-500 text-white',
        'success' => 'bg-emerald-solid text-white',
        'danger' => 'bg-red-600 text-white',
        'warning' => 'bg-amber-solid text-white',
        'info' => 'bg-sky-solid text-white',
        'dark' => 'bg-dark text-white dark:bg-zinc-600',
    ] : [
        'primary' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300',
        'secondary' => 'bg-secondary-100 text-secondary-700 dark:bg-secondary-500/15 dark:text-secondary-300',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'info' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
        'dark' => 'bg-dark/10 text-dark dark:bg-zinc-700 dark:text-zinc-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ' . $variants[$tone]]) }}>
    {{ $slot }}
</span>
