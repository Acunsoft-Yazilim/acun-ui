@props([
    // What the thumbnail shows, for screen readers (its picture is decorative: alt="").
    'label' => null,
])

{{--
    One thumbnail of acun:carousel, in its <acun:slot:thumbnails>: one per slide, in the
    slides' order. A press shows its slide; the shown slide's thumbnail is aria-current="true"
    (and ringed). Its picture sets its size (a square image, a product drawing…).
--}}
<button
    type="button"
    data-slot="carousel-thumb"
    aria-current="false"
    @if ($label !== null) aria-label="{{ $label }}" @endif
    {{ $attributes->class('acun-carousel-thumb block cursor-pointer overflow-hidden rounded-sm opacity-70 ring-2 ring-transparent ring-offset-2 ring-offset-page transition hover:opacity-100 aria-[current=true]:opacity-100 aria-[current=true]:ring-primary-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus motion-reduce:transition-none [&_img]:pointer-events-none') }}
>
    {{ $slot }}
</button>
