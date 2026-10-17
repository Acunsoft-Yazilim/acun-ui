{{--
    A small group label between the rows of an acun:dropdown panel:
        <acun:dropdown.heading>Sipariş</acun:dropdown.heading>
    Attributes go on the label.
--}}
<div data-slot="dropdown-heading" {{ $attributes->class('px-3 pt-2 pb-1 font-mono text-2xs font-medium tracking-[0.08em] text-gray-500 uppercase dark:text-zinc-400') }}>{{ $slot }}</div>
