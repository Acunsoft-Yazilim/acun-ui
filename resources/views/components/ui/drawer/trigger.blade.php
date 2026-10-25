@props([
    // Name of the acun:drawer to open.
    'name',
    // true: a second click closes it again (toggle-drawer), e.g. for a docked filter panel.
    'toggle' => false,
])

{{--
    Opens a drawer from anywhere on the page: wrap the button that opens it.
        <acun:drawer.trigger name="siparis-detay">
            <acun:button variant="white">Detay</acun:button>
        </acun:drawer.trigger>
    It renders a layout-neutral wrapper (display: contents) with data-acun-open (with
    toggle: data-acun-toggle): a click opens the drawer (the plugin of the runtime, no
    Alpine), and the button keeps its own role, focus and keyboard behaviour. Attributes go
    on the wrapper. The first element, when it is a button or a link, gets
    aria-haspopup="dialog" and an aria-expanded that follows the drawer (the plugin sets it).
--}}
@php
    $html = $slot->toHtml();

    if (preg_match('/^(?:\s+|<!--.*?-->)*<(?:button|a)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/is', $html, $match, PREG_OFFSET_CAPTURE)) {
        $aria = '';

        if (! preg_match('/\saria-haspopup\s*=/i', $match[1][0])) {
            $aria .= ' aria-haspopup="dialog"';
        }

        if (! preg_match('/\s(?:x-bind)?:?aria-expanded\s*=/i', $match[1][0])) {
            $aria .= ' aria-expanded="false"';
        }

        $html = substr_replace($html, $aria, $match[1][1], 0);
    }
@endphp

<span data-slot="drawer-trigger" {{ $toggle ? 'data-acun-toggle' : 'data-acun-open' }}="{{ $name }}" {{ $attributes->merge(['class' => 'contents']) }}>{!! $html !!}</span>
