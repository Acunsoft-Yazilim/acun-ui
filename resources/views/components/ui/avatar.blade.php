@props([
    'src' => null,
    'name' => null,
    'size' => 'md',
    // circle · rounded-sm
    'shape' => 'circle',
    // online · offline · away · busy
    'status' => null,
    // Initials colour: primary · secondary · success · danger · warning · info · dark (brand = primary, neutral = secondary).
    'color' => 'primary',
])

@php
    $sizeClass = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-12 text-base', 'xl' => 'size-16 text-lg'][$size] ?? 'size-10 text-sm';
    $radiusClass = $shape === 'rounded-sm' ? 'rounded-sm' : 'rounded-full';
    $colorClass = [
        'primary' => 'bg-primary-soft text-primary-tint-ink',
        'secondary' => 'bg-secondary-soft text-secondary-tint-ink',
        'success' => 'bg-success-soft text-success-tint-ink',
        'danger' => 'bg-danger-soft text-danger-tint-ink',
        'warning' => 'bg-warning-soft text-warning-tint-ink',
        'info' => 'bg-info-soft text-info-tint-ink',
        'dark' => 'bg-dark text-on-dark',
    ][\Acun\Ui\Support\Tone::name($color)];
    $statusClass = ['online' => 'bg-success-line', 'offline' => 'bg-faint', 'away' => 'bg-warning-line', 'busy' => 'bg-danger-line'][$status] ?? null;
    $initials = $name ? \Illuminate\Support\Str::of($name)->explode(' ')->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') : null;
@endphp

<span {{ $attributes->merge(['class' => "relative inline-flex flex-none {$sizeClass}"]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name ?? '' }}" class="size-full object-cover {{ $radiusClass }}">
    @else
        <span class="flex size-full items-center justify-center font-semibold {{ $radiusClass }} {{ $colorClass }}" @if ($name) title="{{ $name }}" @endif>
            {{ $initials ?? $slot }}
        </span>
    @endif

    @if ($statusClass)
        <span class="absolute right-0 bottom-0 block size-2.5 rounded-full ring-2 ring-surface {{ $statusClass }}" role="img" aria-label="{{ $status }}"></span>
    @endif
</span>
