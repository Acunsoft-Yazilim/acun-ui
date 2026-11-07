@props([
    // The carousel's id (acun:carousel id="…"): these arrows move it, wherever they are on the page.
    'for',
    // The "3 / 8" counter between the arrows.
    'counter' => false,
    // false: hidden on phones (below sm), where a swipe moves the strip.
    'mobile' => true,
])

{{--
    A carousel's previous / next buttons outside it: in a section heading, beside "See all".
        <div class="flex items-end justify-between gap-4">
            <h2>Best sellers</h2>
            <acun:carousel.controls for="best-sellers" />
        </div>
        <acun:carousel id="best-sellers" :arrows="false" :per-view="['base' => 2, 'lg' => 4]" :loop="false">…</acun:carousel>
    The carousel plugin runs them as the carousel's own arrows: they show while there is something to slide to (not
    when every slide is in view), are aria-disabled at an end (:loop="false"; with a loop always enabled) and keep
    working after a Livewire update. Without JavaScript they stay hidden (the slides scroll sideways).
--}}
<div
    data-acun-carousel-controls="{{ $for }}"
    {{ $attributes->class([
        'hidden items-center gap-2',
        'data-controls:flex' => $mobile,
        'sm:data-controls:flex' => ! $mobile,
    ]) }}
>
    <button type="button" data-slot="carousel-prev" aria-label="{{ __('Previous') }}" aria-controls="{{ $for }}-viewport" class="flex size-9 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink shadow-xs hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus aria-disabled:cursor-default aria-disabled:opacity-40 aria-disabled:hover:bg-surface">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </button>
    @if ($counter)
        {{-- Screen readers hear the slide's own "3 of 8" name; the counter is for the eyes. --}}
        <span data-slot="carousel-counter" aria-hidden="true" wire:ignore class="min-w-10 text-center text-sm text-muted tabular-nums"></span>
    @endif
    <button type="button" data-slot="carousel-next" aria-label="{{ __('Next') }}" aria-controls="{{ $for }}-viewport" class="flex size-9 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink shadow-xs hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus aria-disabled:cursor-default aria-disabled:opacity-40 aria-disabled:hover:bg-surface">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>
</div>
