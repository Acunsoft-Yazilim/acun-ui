{{--
    The footer of an acun:card written in parts (buttons, a note); the same box as the
    classic <acun:slot:footer>. Works anywhere, it needs nothing from the card.
    Attributes (class, id…) go on the footer box.
--}}
<div data-slot="card-footer" {{ $attributes->merge(['class' => 'flex items-center gap-2 border-t border-line-soft px-5 py-3']) }}>{{ $slot }}</div>
