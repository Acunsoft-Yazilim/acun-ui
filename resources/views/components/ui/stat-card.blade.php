@props([
    'label',
    'value',
    // Change percentage (e.g. 12.5 or -3); green/red depending on its sign.
    'change' => null,
    'changeLabel' => null,
    // acun:icon name.
    'icon' => null,
    // Icon colour: primary · secondary · success · danger · warning · info · dark (brand = primary).
    'color' => 'primary',
    // The same as color (badge and alert call it variant).
    'variant' => null,
    // The value's own colour, for a figure that is good or bad by itself (success · danger · warning · info…); null: the text colour.
    'valueColor' => null,
    // A short line under the value ("12 iade bekliyor", "Hedef: ₺150.000").
    'hint' => null,
])

@php
    $color = $variant ?? $color;
    $iconColor = [
        'primary' => 'bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'secondary' => 'bg-secondary-100 text-secondary-600 dark:bg-secondary-500/15 dark:text-secondary-300',
        'success' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
        'danger' => 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400',
        'warning' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'info' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400',
        'dark' => 'bg-dark/10 text-dark dark:bg-zinc-800 dark:text-zinc-300',
    ][\Acun\Ui\Support\Tone::name($color)];
    $isIncrease = $change !== null && (float) $change >= 0;
    $valueClass = $valueColor === null ? 'text-gray-900 dark:text-zinc-100' : [
        'primary' => 'text-brand-600 dark:text-brand-400',
        'secondary' => 'text-secondary-600 dark:text-secondary-300',
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
        'info' => 'text-sky-600 dark:text-sky-400',
        'dark' => 'text-dark dark:text-zinc-100',
    ][\Acun\Ui\Support\Tone::name($valueColor)];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-sm bg-white p-5 shadow-sm dark:bg-zinc-900']) }}>
    <div class="flex items-start justify-between gap-3">
        @if ($icon)
            <span class="flex size-10 items-center justify-center rounded-sm {{ $iconColor }}">
                <acun:icon :name="$icon" size="6" />
            </span>
        @endif
        @isset($actions)
            <div class="ms-auto">{{ $actions }}</div>
        @endisset
    </div>

    <div>
        <p class="text-sm text-gray-500 dark:text-zinc-400">{{ $label }}</p>
        <p @class(['mt-1 text-2xl font-semibold tabular-nums', $valueClass])>{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">{{ $hint }}</p>
        @endif
    </div>

    @if ($change !== null)
        <p @class(['flex items-center gap-1 text-sm font-medium', 'text-emerald-600 dark:text-emerald-400' => $isIncrease, 'text-red-600 dark:text-red-400' => ! $isIncrease])>
            <acun:icon :name="$isIncrease ? 'arrow-trending-up' : 'arrow-trending-down'" size="4" />
            <span class="tabular-nums">{{ $isIncrease ? '+' : '' }}{{ extension_loaded('intl') ? \Illuminate\Support\Number::format((float) $change, maxPrecision: 1, locale: app()->getLocale()) : rtrim(rtrim(number_format((float) $change, 1, ',', '.'), '0'), ',') }}%</span>
            @if ($changeLabel)
                <span class="font-normal text-gray-500 dark:text-zinc-400">{{ $changeLabel }}</span>
            @endif
        </p>
    @endif

    {{ $slot }}
</div>
