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
<div data-slot="card-body" @if ($collapsible) x-show="open" x-collapse.duration.200ms @endif {{ $attributes->class(['flex-1', 'p-5' => $padding]) }}>
    {{ $slot }}
</div>
