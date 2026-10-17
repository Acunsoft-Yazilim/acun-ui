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
    out closes it. Placement (position, align) applies to acun:popover.content.
    Attributes go on the root element in parts mode.
--}}
@php
    $parts = ! isset($trigger) && \Acun\Ui\Support\Parts::used($slot, 'popover');

    if (! isset($trigger) && ! $parts) {
        throw new InvalidArgumentException('acun:popover needs a trigger: an acun:slot:trigger, or the parts acun:popover.trigger and acun:popover.content.');
    }
@endphp
@if ($parts)
<div
    x-data="{
        open: false,
        closePopover(refocus) {
            this.open = false;
            if (refocus) this.$refs.popoverTrigger?.querySelector('button, a[href]')?.focus();
        },
    }"
    x-id="['popover', 'popover-title']"
    x-on:click.outside="open = false"
    {{-- Escape inside an open popover closes only the popover, not a drawer it sits in. --}}
    x-on:keydown.escape="if (open) { $event.stopPropagation(); closePopover(true) }"
    x-on:keydown.escape.window="if (open) closePopover($el.contains(document.activeElement))"
    x-on:focusout="if ($event.relatedTarget && ! $el.contains($event.relatedTarget)) open = false"
    data-slot="popover"
    {{ $attributes->class('relative inline-block') }}
>
    {{ $slot }}
</div>
@else
<div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="if (open) { $event.stopPropagation(); open = false }" x-on:keydown.escape.window="open = false" class="relative inline-block">
    <div x-on:click="open = ! open">{{ $trigger }}</div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        role="dialog"
        @class([
            'absolute z-40 w-72 rounded-sm bg-surface text-sm shadow-lg ring-1 ring-line',
            'top-full mt-2' => $position !== 'top',
            'bottom-full mb-2' => $position === 'top',
            'left-0' => $align === 'start',
            'right-0' => $align === 'end',
        ])
    >
        @if ($title)
            <div class="border-b border-line px-4 py-2.5 font-semibold text-ink">{{ $title }}</div>
        @endif
        <div {{ $attributes->merge(['class' => 'px-4 py-3 text-dim']) }}>{{ $slot }}</div>
    </div>
</div>
@endif
