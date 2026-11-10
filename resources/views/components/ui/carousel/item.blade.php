@aware(['zoom' => false])

@props([
    // Name of the slide for screen readers; default: its position, e.g. "2 of 5".
    'label' => null,
    // How long this slide stays when the carousel changes by itself (its interval), in milliseconds: a video slide
    // longer than the others; null: the carousel's.
    'interval' => null,
])

{{--
    One slide of acun:carousel. Any direct child of the carousel is a slide; this one
    also tells screen readers it is a slide (role="group", aria-roledescription) with
    a name. While another slide is shown (one slide in view) the carousel plugin makes
    it inert, so Tab skips its links. In a carousel with zoom its picture (the first
    img, picture, svg or .swiper-zoom-target in it) zooms. Attributes (class, wire:key…)
    go on the slide.
--}}
<div
    data-slot="carousel-item"
    role="group"
    aria-roledescription="{{ __('slide') }}"
    @if ($label !== null) aria-label="{{ $label }}" @endif
    @if ($interval) data-interval="{{ (int) $interval }}" @endif
    {{ $attributes->class('acun-carousel-slide') }}
>
    @if ($zoom)
        <div class="acun-carousel-zoom flex size-full items-center justify-center group-data-ready/carousel:cursor-zoom-in [.swiper-slide-zoomed_&]:cursor-move">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
</div>
