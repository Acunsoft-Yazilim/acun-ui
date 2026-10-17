@props([
    // true: more than one section can be open at the same time.
    'multiple' => false,
    // Name(s) of the section(s) open initially.
    'open' => null,
    // The open/closed sign in the headings: chevron · plus (plus/minus) · none.
    'indicator' => 'chevron',
    // Where the sign sits in the heading: end · start.
    'indicatorPosition' => 'end',
    // The headings' level (2–6), to fit the page's outline.
    'headingLevel' => 3,
])

{{--
    <acun:accordion open="general">
        <acun:accordion.item name="general" title="Genel bilgiler">…</acun:accordion.item>
        <acun:accordion.item name="address" title="Adres">…</acun:accordion.item>
    </acun:accordion>
    Items may be hidden (x-show, a search filter): each item draws its own bottom line and
    the list sits 1px lower than the box, so the last visible line is always clipped.
--}}
<div
    x-data="{
        multiple: @js((bool) $multiple),
        open: @js(array_values(array_filter((array) $open))),
        // Sections opened at least once; lazy content renders from then on.
        seen: @js(array_values(array_filter((array) $open))),
        isOpen(name) { return this.open.includes(name) },
        wasOpened(name) { return this.seen.includes(name) },
        toggle(name) {
            if (this.isOpen(name)) { this.open = this.open.filter(n => n !== name); return }
            const show = () => { this.open = this.multiple ? [...this.open, name] : [name] };
            if (this.seen.includes(name)) { show(); return }
            // First opening: lazy content is added first, so the opening animation measures it.
            this.seen.push(name);
            this.$nextTick(show);
        },
    }"
    {{-- Ids of the parts are unique on the page: two accordions may both have a "genel" item. --}}
    x-id="['accordion']"
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-sm bg-surface shadow-sm']) }}
>
    <div class="-mb-px">{{ $slot }}</div>
</div>
