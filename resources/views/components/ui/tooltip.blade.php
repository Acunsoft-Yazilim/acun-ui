@props([
    'text' => '',
    'position' => 'top',
])

{{--
    The tooltip content is teleported to body: the component is usually used
    inside a scrollable (overflow-auto) list/panel, so normal absolute
    positioning gets clipped at that panel's edges. Moving it to body and
    positioning it as fixed from the trigger's screen coordinates solves
    the problem. It sits above dialogs (a tooltip inside a modal).

    The control inside (the first element, when it is a link, button or field) is described
    by the tooltip: an Alpine binding, so a Livewire re-render keeps it. Other content gets
    the attribute once, on the first focusable element.

    The pointer can move onto the tooltip without it closing (a short delay covers the gap),
    and Escape hides it also when it was opened by the pointer (WCAG 1.4.13).
--}}
@php
    // Skip whitespace and comments (Livewire's <!--[if BLOCK]--> markers), as dropdown.trigger does.
    $content = preg_replace_callback(
        '/^((?:\s+|<!--.*?-->)*<(?:a|button|input|select|textarea)\b)/is',
        fn (array $match): string => $match[1].' :aria-describedby="$id(\'ui-tooltip\')"',
        $slot->toHtml(),
        1,
        $bound,
    );
@endphp
<span
    x-data="{
        show: false,
        top: 0,
        bottom: 0,
        left: 0,
        hideTimer: null,
        updatePosition() {
            const rect = $el.getBoundingClientRect();
            this.top = rect.top;
            this.bottom = rect.bottom;
            this.left = rect.left + rect.width / 2;
        },
        open() {
            clearTimeout(this.hideTimer);
            this.updatePosition();
            this.show = true;
        },
        close(delay = 0) {
            clearTimeout(this.hideTimer);
            this.hideTimer = setTimeout(() => { this.show = false }, delay);
        },
    }"
    x-id="['ui-tooltip']"
    @unless ($bound) x-init="$el.querySelector('a[href], button, input, select, textarea, [tabindex]')?.setAttribute('aria-describedby', $id('ui-tooltip'))" @endunless
    x-on:mouseenter="open()"
    x-on:mouseleave="close(120)"
    x-on:focusin="open()"
    x-on:focusout="close()"
    x-on:keydown.escape="if (show) { $event.stopPropagation(); show = false }"
    x-on:keydown.escape.window="show = false"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    {!! $content !!}

    <template x-teleport="body">
        <span
            x-show="show"
            x-cloak
            role="tooltip"
            :id="$id('ui-tooltip')"
            x-on:mouseenter="open()"
            x-on:mouseleave="close(120)"
            x-transition:enter="ease-out duration-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-75"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed z-[90] -translate-x-1/2 {{ $position === 'top' ? '-translate-y-full' : '' }} whitespace-nowrap rounded-sm bg-inverse px-2 py-1 text-xs font-medium text-on-inverse shadow-lg"
            @if ($position === 'top')
                :style="`top: ${top - 6}px; left: ${left}px;`"
            @else
                :style="`top: ${bottom + 6}px; left: ${left}px;`"
            @endif
        >{{ $text }}</span>
    </template>
</span>
