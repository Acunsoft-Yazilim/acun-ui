@props([
    // Heading above the content; it also names the dialog for screen readers.
    'title' => null,
])
@aware(['position' => 'bottom', 'align' => 'start'])

{{--
    The bubble of an acun:popover written in parts (role="dialog"), with an optional
    title and the slot in a padded box. Placement (position, align) comes from the
    acun:popover. Attributes go on the bubble; a width utility in class (w-96…)
    replaces the default w-72. The title names the dialog (aria-labelledby, from the runtime).
--}}
@php
    $customWidth = (bool) preg_match('/(?:^|\s)!?w-/', (string) $attributes->get('class'));
@endphp

<div
    role="dialog"
    data-slot="popover-content"
    data-side="{{ $position === 'top' ? 'top' : 'bottom' }}"
    hidden
    {{ $attributes->class([
        'absolute z-40 rounded-sm bg-surface text-sm shadow-lg ring-1 ring-line',
        'opacity-0 transition-opacity duration-100 data-open:opacity-100 motion-reduce:transition-none',
        'w-72' => ! $customWidth,
        'data-[side=top]:bottom-full data-[side=top]:mb-2 data-[side=bottom]:top-full data-[side=bottom]:mt-2',
        'left-0' => $align === 'start',
        'right-0' => $align === 'end',
    ]) }}
>
    @if ($title)
        <div data-slot="popover-title" class="border-b border-line px-4 py-2.5 font-semibold text-ink">{{ $title }}</div>
    @endif
    <div class="px-4 py-3 text-dim">{{ $slot }}</div>
</div>
