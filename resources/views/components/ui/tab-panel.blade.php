@props([
    'value',
])

{{--
    A panel of the classic acun:tabs (with the :tabs array). The classic tabs are drawn by the
    parts, so this is acun:tabs.panel: labelled by its tab, shown while it is open.
--}}
<acun:tabs.panel :value="$value" {{ $attributes }}>{{ $slot }}</acun:tabs.panel>
