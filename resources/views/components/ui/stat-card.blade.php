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
        'primary' => 'bg-primary-soft text-primary-ink',
        'secondary' => 'bg-secondary-soft text-secondary-ink',
        'success' => 'bg-success-soft text-success-ink',
        'danger' => 'bg-danger-soft text-danger-ink',
        'warning' => 'bg-warning-soft text-warning-ink',
        'info' => 'bg-info-soft text-info-ink',
        'dark' => 'bg-dark-tint text-dark-ink',
    ][\Acun\Ui\Support\Tone::name($color)];
    $isIncrease = $change !== null && (float) $change >= 0;
    $valueClass = $valueColor === null ? 'text-ink' : [
        'primary' => 'text-primary-ink',
        'secondary' => 'text-secondary-ink',
        'success' => 'text-success-ink',
        'danger' => 'text-danger-ink',
        'warning' => 'text-warning-ink',
        'info' => 'text-info-ink',
        'dark' => 'text-dark-ink',
    ][\Acun\Ui\Support\Tone::name($valueColor)];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-sm bg-surface p-5 shadow-sm']) }}>
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
        <p class="text-sm text-muted">{{ $label }}</p>
        <p @class(['mt-1 text-2xl font-semibold tabular-nums', $valueClass])>{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
        @endif
    </div>

    @if ($change !== null)
        <p @class(['flex items-center gap-1 text-sm font-medium', 'text-success-ink' => $isIncrease, 'text-danger-ink' => ! $isIncrease])>
            <acun:icon :name="$isIncrease ? 'arrow-trending-up' : 'arrow-trending-down'" size="4" />
            <span class="tabular-nums">{{ $isIncrease ? '+' : '' }}{{ extension_loaded('intl') ? \Illuminate\Support\Number::format((float) $change, maxPrecision: 1, locale: app()->getLocale()) : rtrim(rtrim(number_format((float) $change, 1, ',', '.'), '0'), ',') }}%</span>
            @if ($changeLabel)
                <span class="font-normal text-muted">{{ $changeLabel }}</span>
            @endif
        </p>
    @endif

    {{ $slot }}
</div>
