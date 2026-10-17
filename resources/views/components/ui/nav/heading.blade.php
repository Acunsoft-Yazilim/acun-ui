@props([
    // The menu's first section heading starts with less space above it.
    'first' => false,
])

{{-- Section heading in the vertical menu: DM Mono, small, letter-spaced. Takes no space in the collapsed menu (see panel.css .menu-heading). --}}
<div {{ $attributes->class([
    'menu-heading px-2.5 pb-2 font-mono text-2xs font-medium tracking-[0.08em] text-gray-500 uppercase dark:text-zinc-400',
    'pt-1' => $first,
    'pt-5' => ! $first,
]) }}>{{ $slot }}</div>
