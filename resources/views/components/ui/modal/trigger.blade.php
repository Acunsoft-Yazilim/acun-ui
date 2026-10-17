@props([
    // Name of the acun:modal (or acun:confirmation-dialog) to open.
    'name',
])

{{--
    Opens a modal from anywhere on the page: wrap the button that opens it.
        <acun:modal.trigger name="musteri-ekle">
            <acun:button>Yeni müşteri</acun:button>
        </acun:modal.trigger>
    It renders a layout-neutral wrapper (display: contents) that sends acun:modal:open on a
    click, so the button keeps its own role, focus and keyboard behaviour. Attributes
    go on the wrapper. The first element, when it is a button or a link, gets
    aria-haspopup="dialog".
--}}
@php
    $html = $slot->toHtml();

    if (preg_match('/^(?:\s+|<!--.*?-->)*<(?:button|a)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/is', $html, $match, PREG_OFFSET_CAPTURE)
        && ! preg_match('/\saria-haspopup\s*=/i', $match[1][0])) {
        $html = substr_replace($html, ' aria-haspopup="dialog"', $match[1][1], 0);
    }
@endphp

<span data-slot="modal-trigger" x-data x-on:click="$dispatch('acun:modal:open', { name: @js($name) })" {{ $attributes->merge(['class' => 'contents']) }}>{!! $html !!}</span>
