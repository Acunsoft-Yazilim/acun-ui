@props([
    'name',
    // Heading text (or a <acun:slot:title>). Leave it out to write the item in parts:
    // acun:accordion.heading and acun:accordion.content.
    'title' => null,
    // A section that cannot be opened: its heading (or trigger) is disabled.
    'disabled' => false,
])

{{--
    One section of acun:accordion; its name opens it (accordion open="…") and wires
    its parts together. acun:accordion-item is the old name and renders the same.
    With title: class styles the content; any other attribute (x-show, wire:key, id…)
    goes on the item itself. Written in parts, every attribute goes on the item and
    the parts take their own.
--}}
@if ($title !== null)
    <div {{ $attributes->except('class')->merge(['data-slot' => 'accordion-item', 'data-name' => $name, 'class' => 'border-b border-line']) }}>
        <acun:accordion.heading>{{ $title }}</acun:accordion.heading>
        <acun:accordion.content {{ $attributes->only('class') }}>{{ $slot }}</acun:accordion.content>
    </div>
@else
    <div {{ $attributes->merge(['data-slot' => 'accordion-item', 'data-name' => $name, 'class' => 'border-b border-line']) }}>
        {{ $slot }}
    </div>
@endif
