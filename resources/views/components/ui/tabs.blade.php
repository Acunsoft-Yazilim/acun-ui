@props([
    // Classic API: [value => label]; the triggers are drawn from it and the slot holds an
    // acun:tab-panel per value. Leave it out to write the tabs in parts (see below).
    'tabs' => [],
    // The tab open initially; default: the first one.
    'active' => null,
    // underline · pills (filled) · cards (card tabs joined to a framed panel)
    'variant' => 'underline',
    // sm · md · lg; md is the original look.
    'size' => 'md',
])

{{--
    Classic, from an array:
        <acun:tabs :tabs="['ozet' => 'Özet', 'teslimat' => 'Teslimat']">
            <acun:tab-panel value="ozet">…</acun:tab-panel>
            <acun:tab-panel value="teslimat">…</acun:tab-panel>
        </acun:tabs>

    In parts (without :tabs), for triggers with an icon, a count or a disabled state:
        <acun:tabs wire:model="activeTab" variant="underline" size="md">
            <acun:tabs.list>
                <acun:tabs.trigger value="ozet">Özet</acun:tabs.trigger>
                <acun:tabs.trigger value="teslimat" badge="8">Teslimat</acun:tabs.trigger>
            </acun:tabs.list>
            <acun:tabs.panel value="ozet">…</acun:tabs.panel>
            <acun:tabs.panel value="teslimat">…</acun:tabs.panel>
        </acun:tabs>
    Without active (and with an empty model) the first enabled trigger is open. The
    parts take variant and size from here; a nested acun:tabs should set its own.

    wire:model / x-model bind the open tab both ways, in both APIs. Attributes go on the
    root element.

    Pills wrap onto more lines; the underline row keeps one line and scrolls
    sideways when it does not fit (a phone with four tabs), bringing the chosen
    tab into view. Works without Alpine (the tabs plugin of the runtime); with active,
    the server already draws the open tab.
--}}
@if (! empty($tabs))
{{-- Classic: drawn by the parts, so it gets their ids, aria-controls, arrow keys and focus order. --}}
<acun:tabs :active="$active ?? array_key_first($tabs)" :variant="$variant" :size="$size" {{ $attributes }}>
    <acun:tabs.list>
        @foreach ($tabs as $key => $label)
            <acun:tabs.trigger :value="(string) $key">{{ $label }}</acun:tabs.trigger>
        @endforeach
    </acun:tabs.list>
    {{ $slot }}
</acun:tabs>
@else
{{-- The behaviour is the tabs plugin's (js/plugins/tabs.js): no Alpine; on a page with Alpine the root binds
     wire:model / x-model and gives its content the 2.x scope (tab, selected()). --}}
<div {{ $attributes->merge(['data-acun-tabs' => '', 'data-slot' => 'tabs', 'data-value' => $active === null || $active === '' ? null : (string) $active]) }}>
    {{ $slot }}
</div>
@endif
