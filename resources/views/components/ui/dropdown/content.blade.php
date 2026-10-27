@aware(['align' => 'end', 'position' => 'bottom', 'fullWidth' => false])

{{--
    The panel of an acun:dropdown written in parts: acun:dropdown.item rows, with
    acun:dropdown.heading and acun:dropdown.separator between them. Placement (align,
    position, full-width) comes from the acun:dropdown. A click inside closes it; Arrow
    Up / Down, Home and End move between the items. Attributes (class="w-64", id…) go on
    the panel. Closed it is hidden; open it carries data-open.
--}}
<div
    data-slot="dropdown-content"
    data-side="{{ $position === 'top' ? 'top' : 'bottom' }}"
    hidden
    {{ $attributes->class([
        'absolute z-40 max-h-64 overflow-auto rounded-sm bg-surface py-1 shadow-lg',
        // Closed: faded and slightly smaller; open (data-open): the full panel (js/core/transition.js).
        'scale-95 opacity-0 transition duration-100 ease-out data-open:scale-100 data-open:opacity-100 motion-reduce:transition-none',
        'left-0 right-0' => $fullWidth,
        'min-w-[12rem]' => ! $fullWidth,
        'left-0' => ! $fullWidth && $align === 'start',
        'right-0' => ! $fullWidth && $align !== 'start',
        // The side it opens on (data-side): the plugin flips it when the window has no room there.
        'data-[side=top]:bottom-full data-[side=top]:mb-2 data-[side=bottom]:top-full data-[side=bottom]:mt-2',
    ]) }}
>
    {{ $slot }}
</div>
