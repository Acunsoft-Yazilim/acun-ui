@props([
    // The set's name: what the carousel's `set` prop, AcunUI.plugins.get(el, 'carousel').showSet(name) and the
    // acun:carousel:set event ({ name }) call it (a product's colour key).
    'name',
    // Said politely to screen readers when the set is shown, e.g. "Red colour images are shown".
    'label' => null,
])

{{--
    Another set of slides (and thumbnails) for acun:carousel: the pictures of a product's other colour. It is a
    <template>: nothing of it is on the page until the carousel shows it in place of its slides (the first one,
    thumbnails and all; the fullscreen view follows). Write every set, the one shown first too (to come back to
    it); the carousel's `set` prop names the one its own slides are:
        <acun:carousel set="black" zoom>
            …the black slides… <acun:slot:thumbnails>…</acun:slot:thumbnails>
            <acun:carousel.set name="black" :label="__(':color colour images are shown', ['color' => 'Black'])">…the black slides… <acun:slot:thumbnails>…</acun:slot:thumbnails></acun:carousel.set>
            <acun:carousel.set name="red" :label="…">…</acun:carousel.set>
        </acun:carousel>
--}}
<template data-slot="carousel-set" data-name="{{ $name }}"@if ($label !== null) data-announce="{{ $label }}"@endif>{{ $slot }}@isset($thumbnails)<div data-slot="carousel-set-thumbs">{{ $thumbnails }}</div>@endisset</template>
