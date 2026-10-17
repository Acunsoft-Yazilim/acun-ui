@props([
    // Heading above the content; it also names the dialog for screen readers.
    'title' => null,
])
@aware(['position' => 'bottom', 'align' => 'start'])

{{--
    The bubble of an acun:popover written in parts (role="dialog"), with an optional
    title and the slot in a padded box. Placement (position, align) comes from the
    acun:popover. Attributes go on the bubble; a width utility in class (w-96…)
    replaces the default w-72.
--}}
@php
    $customWidth = (bool) preg_match('/(?:^|\s)!?w-/', (string) $attributes->get('class'));
@endphp

<div
    role="dialog"
    data-slot="popover-content"
    x-show="open"
    x-cloak
    x-transition.opacity.duration.100ms
    :id="$id('popover')"
    @if ($title) :aria-labelledby="$id('popover-title')" @endif
    {{ $attributes->class([
        'absolute z-40 rounded-sm bg-surface text-sm shadow-lg ring-1 ring-line',
        'w-72' => ! $customWidth,
        'top-full mt-2' => $position !== 'top',
        'bottom-full mb-2' => $position === 'top',
        'left-0' => $align === 'start',
        'right-0' => $align === 'end',
    ]) }}
>
    @if ($title)
        <div :id="$id('popover-title')" class="border-b border-line px-4 py-2.5 font-semibold text-ink">{{ $title }}</div>
    @endif
    <div class="px-4 py-3 text-dim">{{ $slot }}</div>
</div>
