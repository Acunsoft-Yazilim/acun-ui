@props([
    // Scroll axis: vertical · horizontal · both
    'direction' => 'vertical',
    // CSS length ('16rem', '50vh') or a number of pixels. An h-* / max-h-* class works too.
    'maxHeight' => null,
    'height' => null,
    // Fade the edges while more content is hidden in that direction (a small scroll listener of the runtime).
    'shadows' => false,
    // Accessible name; the area becomes a labelled region.
    'label' => null,
])

{{--
    Usage:
    <acun:scroll-area max-height="16rem" label="Son hareketler">…</acun:scroll-area>
    <acun:scroll-area direction="horizontal"><table class="w-max">…</table></acun:scroll-area>
    <acun:scroll-area class="max-h-80" shadows>…</acun:scroll-area>

    The scrollbar is thin and themed with the standard scrollbar-width / scrollbar-color
    properties; ::-webkit-scrollbar covers browsers that do not support them (Safari).
    tabindex="0" lets keyboard users focus the area and scroll it with the arrow keys.
--}}
@php
    $overflow = [
        'vertical' => 'overflow-x-hidden overflow-y-auto',
        'horizontal' => 'overflow-x-auto overflow-y-hidden',
        'both' => 'overflow-auto',
    ][$direction] ?? 'overflow-x-hidden overflow-y-auto';

    $style = collect(['height' => $height, 'max-height' => $maxHeight])
        ->reject(fn ($value) => $value === null || $value === '')
        ->map(fn ($value, $property) => $property.': '.(is_numeric($value) ? $value.'px' : $value))
        ->implode('; ');

    $defaults = array_filter([
        'tabindex' => '0',
        'style' => $style,
        'class' => $overflow.' [scrollbar-width:thin] [scrollbar-color:var(--ac-line-strong)_transparent] hover:[scrollbar-color:var(--ac-line-hover)_transparent] [&::-webkit-scrollbar]:size-2 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-corner]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-line-strong hover:[&::-webkit-scrollbar-thumb]:bg-line-hover focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus',
    ], fn ($value) => $value !== '');
@endphp

<div
    @if ($label) role="region" aria-label="{{ $label }}" @elseif ($attributes->has('aria-labelledby')) role="region" @endif
    {{-- The fading edges: a mask, so they work on any background (js/plugins/scroll-shadows.js). --}}
    @if ($shadows) data-acun-scroll-shadows data-direction="{{ $direction }}" @endif
    {{ $attributes->merge($defaults) }}
>
    {{ $slot }}
</div>
