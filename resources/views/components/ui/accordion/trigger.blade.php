@aware(['name' => null, 'disabled' => false, 'open' => null])

{{--
    A trigger with free layout, in place of acun:accordion.heading: a plain button with
    no heading styles and no open/closed sign, wired to its item like the heading
    (aria-expanded, aria-controls, the same id the content points to). Put links or
    other buttons next to it, not inside it.
        <acun:accordion.item name="siparis-1042">
            <div class="flex items-center gap-3 px-5 py-3">
                <acun:accordion.trigger class="flex flex-1 items-center gap-3 text-left">Sipariş #1042</acun:accordion.trigger>
                <acun:button size="sm" variant="white">Kargoyu izle</acun:button>
            </div>
            <acun:accordion.content>…</acun:accordion.content>
        </acun:accordion.item>
    Attributes go on the button; it carries group, so its children can use
    group-aria-expanded: for the open state. A disabled item disables it.
--}}
@php
    if ($name === null) {
        throw new InvalidArgumentException('acun:accordion.trigger must be inside an acun:accordion-item with a name.');
    }

    $expanded = in_array($name, array_map('strval', (array) $open), true);
@endphp

<button
    type="button"
    data-slot="accordion-trigger"
    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
    @disabled($disabled)
    {{ $attributes->class('group cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:opacity-50') }}
>{{ $slot }}</button>
