@props([
    'title' => null,
    // bottom · top
    'position' => 'bottom',
    // Panel edge aligned with the trigger: start · end
    'align' => 'start',
])

{{--
    Content bubble with a title that opens on click. The trigger comes from the `trigger` slot:
        <acun:popover title="İade kuralı">
            <acun:slot:trigger><acun:button variant="white">Bilgi</acun:button></acun:slot:trigger>
            Ürünler teslimattan sonra 14 gün içinde iade edilebilir.
        </acun:popover>
    Class and the other attributes go on the content box.

    In parts (no trigger slot; the slot starts with a popover part):
        <acun:popover align="end">
            <acun:popover.trigger><acun:button variant="white">Bilgi</acun:button></acun:popover.trigger>
            <acun:popover.content title="İade kuralı">Ürünler teslimattan sonra 14 gün içinde iade edilebilir.</acun:popover.content>
        </acun:popover>
    The trigger's button gets aria-expanded / aria-controls; Escape or moving the focus
    out closes it. Placement (position, align) applies to acun:popover.content; the bubble flips
    to the other side when the window has no room for it. Attributes go on the root element in
    parts mode. Works without Alpine (the popover plugin of the runtime).
--}}
@php
    $parts = ! isset($trigger) && \Acun\Ui\Support\Parts::used($slot, 'popover');

    if (! isset($trigger) && ! $parts) {
        throw new InvalidArgumentException('acun:popover needs a trigger: an acun:slot:trigger, or the parts acun:popover.trigger and acun:popover.content.');
    }
@endphp
@if ($parts)
{{-- The behaviour is the popover plugin's (js/plugins/popover.js): no Alpine; on a page with Alpine the root
     still gives its content the 2.x scope (open, closePopover()). --}}
<div data-acun-popover data-slot="popover" {{ $attributes->class('relative inline-block') }}>
    {{ $slot }}
</div>
@else
{{-- Classic: the same plugin markup (ARIA, Escape, focus, placement); the attributes stay on the content box. --}}
<div data-acun-popover data-slot="popover" class="relative inline-block">
    <div data-slot="popover-trigger">{!! \Acun\Ui\Support\Parts::controlAttributes($trigger, 'aria-haspopup="dialog" aria-expanded="false"') !!}</div>

    <div
        role="dialog"
        data-slot="popover-content"
        data-side="{{ $position === 'top' ? 'top' : 'bottom' }}"
        hidden
        @class([
            'absolute z-40 w-72 rounded-sm bg-surface text-sm shadow-lg ring-1 ring-line',
            'opacity-0 transition-opacity duration-100 data-open:opacity-100 motion-reduce:transition-none',
            'data-[side=top]:bottom-full data-[side=top]:mb-2 data-[side=bottom]:top-full data-[side=bottom]:mt-2',
            'left-0' => $align === 'start',
            'right-0' => $align === 'end',
        ])
    >
        @if ($title)
            <div data-slot="popover-title" class="border-b border-line px-4 py-2.5 font-semibold text-ink">{{ $title }}</div>
        @endif
        <div {{ $attributes->merge(['class' => 'px-4 py-3 text-dim']) }}>{{ $slot }}</div>
    </div>
</div>
@endif
