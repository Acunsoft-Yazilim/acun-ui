@aware(['collapsible' => false])

@props([
    // false when a table or list should reach the edges.
    'padding' => true,
])

{{--
    The body of an acun:card written in parts; the same box as the classic body (p-5,
    grows so footers of cards in a row line up). It folds when the card is collapsible.
    Attributes (class, id, wire:*…) go on the body box.
--}}
@if ($collapsible)
{{-- Folds in CSS: a one-row grid whose row closes from 1fr to 0fr (js/plugins/card.js); the padding is inside. --}}
<div data-slot="card-body" data-open {{ $attributes->class('grid flex-1 grid-rows-[0fr] transition-[grid-template-rows] duration-200 ease-out data-open:grid-rows-[1fr] motion-reduce:transition-none') }}>
    <div class="min-h-0 overflow-hidden"><div @class(['p-5' => $padding])>{{ $slot }}</div></div>
</div>
@else
<div data-slot="card-body" {{ $attributes->class(['flex-1', 'p-5' => $padding]) }}>
    {{ $slot }}
</div>
@endif
