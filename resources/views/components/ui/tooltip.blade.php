@props([
    'text' => '',
    'position' => 'top',
])

{{--
    A short label for the element inside, on hover and on keyboard focus. The behaviour is the tooltip
    plugin's (js/plugins/tooltip.js): no Alpine. The bubble is shown in the browser's top layer (the
    Popover API), so a scrollable (overflow-auto) list or panel does not clip it and it sits above
    dialogs; it stays where it is in the DOM (a Livewire morph finds it there) and is placed fixed at
    the element's screen position, above it (position="top") or below.

    The control inside (the first link, button or field, else the first focusable element) is
    described by the tooltip (aria-describedby, from the runtime).

    The pointer can move onto the tooltip without it closing (a short delay covers the gap),
    and Escape hides it also when it was opened by the pointer (WCAG 1.4.13).
--}}
<span data-acun-tooltip {{ $attributes->merge(['class' => 'relative']) }}>
    {{ $slot }}

    <span
        role="tooltip"
        data-slot="tooltip-content"
        data-position="{{ $position === 'top' ? 'top' : 'bottom' }}"
        popover="manual"
        hidden
        @class([
            'fixed inset-auto z-[90] m-0 -translate-x-1/2 overflow-visible whitespace-nowrap rounded-sm bg-inverse px-2 py-1 text-xs font-medium text-on-inverse shadow-lg',
            'opacity-0 transition-opacity duration-100 ease-out data-open:opacity-100 motion-reduce:transition-none',
            '-translate-y-full' => $position === 'top',
        ])
    >{{ $text }}</span>
</span>
