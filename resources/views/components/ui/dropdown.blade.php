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
    panel and move between the items, Escape closes it and returns to the trigger.
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
<div
    x-data="{
        open: false,
        // The panel's links and enabled buttons, in order.
        items() {
            return [...(this.$refs.dropdownContent?.querySelectorAll('a[href], button:not(:disabled)') ?? [])];
        },
        focusItem(to) {
            const items = this.items();
            if (! items.length) return;
            let i = items.indexOf(document.activeElement);
            if (to === 'first' || (i < 0 && to === 1)) i = 0;
            else if (to === 'last' || (i < 0 && to === -1)) i = items.length - 1;
            else i = (i + to + items.length) % items.length;
            items[i].focus();
        },
        openMenu(to) {
            this.open = true;
            this.$nextTick(() => this.focusItem(to));
        },
        closeMenu(refocus) {
            this.open = false;
            if (refocus) this.$refs.dropdownTrigger?.querySelector('button, a[href]')?.focus();
        },
        menuKey(event) {
            if (event.target.matches('input, textarea, select')) return;
            const to = { ArrowDown: 1, ArrowUp: -1, Home: 'first', End: 'last' }[event.key];
            if (to === undefined) return;
            event.preventDefault();
            this.focusItem(to);
        },
    }"
    x-id="['dropdown-content']"
    x-on:click.outside="open = false"
    {{-- Escape inside an open menu closes only the menu, not a drawer it sits in. --}}
    x-on:keydown.escape="if (open) { $event.stopPropagation(); closeMenu(true) }"
    x-on:keydown.escape.window="if (open) closeMenu($el.contains(document.activeElement))"
    x-on:focusout="if ($event.relatedTarget && ! $el.contains($event.relatedTarget)) open = false"
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
