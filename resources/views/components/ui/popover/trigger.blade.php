{{--
    What opens and closes an acun:popover written in parts; wraps your button or link:
        <acun:popover.trigger><acun:button variant="white">Bilgi</acun:button></acun:popover.trigger>
    The first element inside, when it is a button or a link, gets aria-haspopup="dialog",
    aria-expanded and aria-controls (the content). Attributes go on the wrapper.
--}}
@php
    // Skip whitespace and comments (Livewire's <!--[if BLOCK]--> markers), as Parts::used does.
    $control = preg_replace_callback(
        '/^((?:\s+|<!--.*?-->)*<(?:button|a)\b)/is',
        fn (array $match): string => $match[1].' aria-haspopup="dialog" :aria-expanded="open.toString()" :aria-controls="$id(\'popover\')"',
        $slot->toHtml(),
        1,
    );
@endphp

<div data-slot="popover-trigger" x-ref="popoverTrigger" x-on:click="open = ! open" {{ $attributes }}>
    {!! $control !!}
</div>
