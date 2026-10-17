@aware(['variant' => 'underline'])

{{--
    The row of acun:tabs.trigger buttons of an acun:tabs written in parts (role="tablist").
    Underline: one line that scrolls sideways when it does not fit; pills wrap.
    Left / Right move to the previous / next tab and open it, Home / End to the first /
    last one; only the open tab is in the Tab order. Attributes (class, aria-label…) go
    on the tablist element.
--}}
<div
    role="tablist"
    data-slot="tabs-list"
    x-on:keydown.right.prevent="focusTab($el, 1)"
    x-on:keydown.left.prevent="focusTab($el, -1)"
    x-on:keydown.home.prevent="focusTab($el, 'first')"
    x-on:keydown.end.prevent="focusTab($el, 'last')"
    {{ $attributes->class([
        'flex gap-1',
        'overflow-x-auto overscroll-x-contain border-b border-line [scrollbar-width:thin]' => $variant !== 'pills',
        'flex-wrap' => $variant === 'pills',
    ]) }}
>
    {{ $slot }}
</div>
