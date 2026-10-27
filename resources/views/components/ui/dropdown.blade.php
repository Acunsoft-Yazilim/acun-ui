@props([
    // Panel edge aligned with the trigger: end · start
    'align' => 'end',
    // bottom · top
    'position' => 'bottom',
    // The trigger and the panel take the full width.
    'fullWidth' => false,
])

{{--
    Classic, with a trigger slot; the slot holds the items:
        <acun:dropdown>
            <acun:slot:trigger><acun:button variant="white">İşlemler</acun:button></acun:slot:trigger>
            <acun:dropdown.item href="/siparisler">Siparişler</acun:dropdown.item>
        </acun:dropdown>

    In parts (no trigger slot; the slot starts with a dropdown part):
        <acun:dropdown align="end">
            <acun:dropdown.trigger><acun:button variant="white">İşlemler</acun:button></acun:dropdown.trigger>
            <acun:dropdown.content>
                <acun:dropdown.heading>Sipariş</acun:dropdown.heading>
                <acun:dropdown.item icon="pencil-square">Düzenle</acun:dropdown.item>
                <acun:dropdown.separator />
                <acun:dropdown.item icon="trash" danger>Sil</acun:dropdown.item>
            </acun:dropdown.content>
        </acun:dropdown>
    The trigger's button gets aria-expanded / aria-controls; Arrow Down / Up open the
    panel and move between the items, Escape closes it and returns to the trigger. The panel
    opens below (or above, position="top") and flips to the other side when the window has no
    room for it there. Works without Alpine (the dropdown plugin of the runtime).
    Placement (align, position, full-width) applies to acun:dropdown.content.
    Attributes go on the root element (the classic form is drawn by the parts too).
--}}
@php
    $parts = ! isset($trigger) && \Acun\Ui\Support\Parts::used($slot, 'dropdown');

    if (! isset($trigger) && ! $parts) {
        throw new InvalidArgumentException('acun:dropdown needs a trigger: an acun:slot:trigger, or the parts acun:dropdown.trigger and acun:dropdown.content.');
    }
@endphp
@if ($parts)
{{-- The behaviour is the dropdown plugin's (js/plugins/dropdown.js): no Alpine; on a page with Alpine the root
     still gives its content the 2.x scope (open, openMenu(), closeMenu()). --}}
<div
    data-acun-dropdown
    data-slot="dropdown"
    {{ $attributes->class(['relative', 'block w-full' => $fullWidth, 'inline-block' => ! $fullWidth]) }}
>
    {{ $slot }}
</div>
@else
{{-- Classic: drawn by the parts, so it gets the same ARIA, keys, placement and attributes. --}}
<acun:dropdown :align="$align" :position="$position" :full-width="$fullWidth" {{ $attributes }}>
    <acun:dropdown.trigger>{{ $trigger }}</acun:dropdown.trigger>
    <acun:dropdown.content>{{ $slot }}</acun:dropdown.content>
</acun:dropdown>
@endif
