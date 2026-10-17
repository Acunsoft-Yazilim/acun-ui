@aware(['align' => 'end', 'position' => 'bottom', 'fullWidth' => false])

{{--
    The panel of an acun:dropdown written in parts: acun:dropdown.item rows, with
    acun:dropdown.heading and acun:dropdown.separator between them. Placement (align,
    position, full-width) comes from the acun:dropdown. A click inside closes it; Arrow
    Up / Down, Home and End move between the items. Attributes (class="w-64", id…) go on
    the panel.
--}}
<div
    data-slot="dropdown-content"
    x-ref="dropdownContent"
    x-show="open"
    x-cloak
    x-transition:enter="ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-on:click="closeMenu($el.contains(document.activeElement))"
    x-on:keydown="menuKey($event)"
    :id="$id('dropdown-content')"
    {{ $attributes->class([
        'absolute z-40 max-h-64 overflow-auto rounded-sm bg-white py-1 shadow-lg dark:bg-zinc-900',
        'left-0 right-0' => $fullWidth,
        'min-w-[12rem]' => ! $fullWidth,
        'left-0' => ! $fullWidth && $align === 'start',
        'right-0' => ! $fullWidth && $align !== 'start',
        'bottom-full mb-2' => $position === 'top',
        'top-full mt-2' => $position !== 'top',
    ]) }}
>
    {{ $slot }}
</div>
