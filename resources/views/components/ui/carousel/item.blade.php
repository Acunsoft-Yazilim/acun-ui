@props([
    // Name of the slide for screen readers; default: its position, e.g. "2 / 5".
    'label' => null,
])

{{--
    One slide of acun:carousel. Any direct child of the carousel is a slide; this one
    also tells screen readers it is a slide (role="group", aria-roledescription) with
    a name, and is inert while another slide is shown, so Tab skips its links.
    Attributes (class, wire:key…) go on the slide.
--}}
<div
    data-slot="carousel-item"
    role="group"
    aria-roledescription="slide"
    @if ($label !== null)
        aria-label="{{ $label }}"
    @else
        x-bind:aria-label="`${[...$el.parentElement.children].indexOf($el) + 1} / ${count}`"
    @endif
    x-bind:inert="index !== [...$el.parentElement.children].indexOf($el)" {{ $attributes }}
>
    {{ $slot }}
</div>
