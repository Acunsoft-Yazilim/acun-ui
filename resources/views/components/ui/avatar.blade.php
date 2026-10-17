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
        'primary' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'secondary' => 'bg-secondary-100 text-secondary-700 dark:bg-secondary-500/15 dark:text-secondary-300',
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'info' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'dark' => 'bg-dark text-white dark:bg-zinc-700 dark:text-zinc-100',
    ][\Acun\Ui\Support\Tone::name($color)];
    $statusClass = ['online' => 'bg-emerald-500', 'offline' => 'bg-gray-400', 'away' => 'bg-amber-500', 'busy' => 'bg-red-500'][$status] ?? null;
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
        <span class="absolute right-0 bottom-0 block size-2.5 rounded-full ring-2 ring-white dark:ring-zinc-900 {{ $statusClass }}" role="img" aria-label="{{ $status }}"></span>
    @endif
</span>
