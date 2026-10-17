@props([
    'size' => 'md',
    // border: spinning ring · grow: dot that grows and fades
    'type' => 'border',
    'label' => null,
    // primary · secondary · success · danger · warning · info · dark (see Acun\Ui\Support\Tone)
    'color' => 'primary',
    // The same as color (badge and alert call it variant).
    'variant' => null,
])

@php
    $color = $variant ?? $color;
    $sizeClass = ['sm' => 'size-4', 'md' => 'size-6', 'lg' => 'size-10'][$size] ?? 'size-6';
    $colorClass = [
        'primary' => 'text-primary-ink',
        'secondary' => 'text-secondary-line',
        'success' => 'text-success-line',
        'danger' => 'text-danger-line',
        'warning' => 'text-warning-line',
        'info' => 'text-info-line',
        'dark' => 'text-dark-ink',
    ][\Acun\Ui\Support\Tone::name($color)];
@endphp

<span role="status" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 '.$colorClass]) }}>
    @if ($type === 'grow')
        <span class="{{ $sizeClass }} animate-ping rounded-full bg-current opacity-75 motion-reduce:animate-none" aria-hidden="true"></span>
    @else
        <span class="{{ $sizeClass }} animate-spin rounded-full border-2 border-current border-r-transparent motion-reduce:animate-[spin_1.5s_linear_infinite]" aria-hidden="true"></span>
    @endif
    <span class="sr-only">{{ $label ?? __('Loading') }}</span>
</span>
