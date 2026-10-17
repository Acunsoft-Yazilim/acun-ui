{{--
    The text of an acun:alert written in parts: paragraphs, a list, a link, buttons. It
    keeps the classic gap under a title (mt-1) and has none when it comes first.
    Attributes (class, id…) go on the box.
--}}
<div data-slot="alert-description" {{ $attributes->merge(['class' => 'not-first:mt-1']) }}>{{ $slot }}</div>
