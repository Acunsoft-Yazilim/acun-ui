{{--
    The heading line of an acun:alert written in parts; the same as the classic title.
    Attributes (class, id…) go on the <p>.
--}}
<p data-slot="alert-title" {{ $attributes->merge(['class' => 'font-medium']) }}>{{ $slot }}</p>
