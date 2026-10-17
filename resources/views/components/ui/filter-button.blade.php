@props([
    // Name of the acun:drawer to toggle.
    'drawer',
    // Number of applied filters; shown as a badge when greater than 0.
    'count' => 0,
    'label' => null,
    // true: a funnel icon only; the label becomes the tooltip and accessible name,
    // and the count sits as a small badge on the icon's corner.
    'iconOnly' => false,
    // With iconOnly: also show the label below the sm breakpoint (e.g. inside a stacked phone menu).
    'labelOnPhone' => false,
])

@php($text = $label ?? __('Filters'))

<acun:button
    variant="white"
    type="button"
    x-data
    x-on:click="$dispatch('acun:drawer:toggle', { name: {{ \Illuminate\Support\Js::from($drawer) }} })"
    :title="$iconOnly ? $text : null"
    :aria-label="$iconOnly ? ($count > 0 ? $text.' ('.$count.')' : $text) : null"
    {{ $attributes->class(['relative !px-2.5' => $iconOnly]) }}
>
    @if ($iconOnly)
        <span class="flex h-5 items-center"><acun:icon name="funnel" size="4" /></span>
        @if ($labelOnPhone)<span class="sm:hidden">{{ $text }}</span>@endif
        @if ($count > 0)
            <span class="absolute -end-1.5 -top-1.5 inline-flex size-4.5 items-center justify-center rounded-full bg-brand-600 text-2xs font-semibold text-white tabular-nums" aria-hidden="true">{{ $count }}</span>
        @endif
    @else
        {{ $text }}
        @if ($count > 0)
            <span class="ms-1 inline-flex size-5 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">{{ $count }}</span>
        @endif
    @endif
</acun:button>
