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
    Works without Alpine (the accordion plugin of the runtime); the open sections are drawn
    open by the server and the height animates in CSS.
--}}
{{-- The behaviour is the accordion plugin's (js/plugins/accordion.js): no Alpine; on a page with Alpine the root
     gives its content the 2.x scope (isOpen(), wasOpened(), toggle()). --}}
<div {{ $attributes->merge(['data-acun-accordion' => '', 'data-multiple' => $multiple ? 'true' : 'false', 'class' => 'overflow-hidden rounded-sm bg-surface shadow-sm']) }}>
    <div class="-mb-px">{{ $slot }}</div>
</div>
