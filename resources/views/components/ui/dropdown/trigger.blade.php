{{--
    What opens and closes an acun:dropdown written in parts; wraps your button or link:
        <acun:dropdown.trigger>
            <acun:button variant="white">İşlemler <acun:icon name="chevron-down" size="4" /></acun:button>
        </acun:dropdown.trigger>
    A click toggles the panel; Arrow Down / Up open it on its first / last item. The first
    element inside, when it is a button or a link, gets aria-expanded and aria-controls
    (the panel). Attributes go on the wrapper.
--}}
@php
    // Skip whitespace and comments (Livewire's <!--[if BLOCK]--> markers), as Parts::used does.
    $control = preg_replace_callback(
        '/^((?:\s+|<!--.*?-->)*<(?:button|a)\b)/is',
        fn (array $match): string => $match[1].' :aria-expanded="open.toString()" :aria-controls="$id(\'dropdown-content\')"',
        $slot->toHtml(),
        1,
    );
@endphp

<div
    data-slot="dropdown-trigger"
    x-ref="dropdownTrigger"
    x-on:click="open = ! open"
    x-on:keydown.down.prevent="openMenu(1)"
    x-on:keydown.up.prevent="openMenu(-1)" {{ $attributes }}
>
    {!! $control !!}
</div>
