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
    tab into view.
--}}
@php
    $modelable = $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();
@endphp
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
<div
    x-data="{
        tab: @js($active === null ? null : (string) $active),
        initial: @js($active === null ? null : (string) $active),
        selected(value) { return this.tab !== null && this.tab !== undefined && String(this.tab) === value },
        // An empty tab (no active prop, or an empty model): the active prop, else the
        // first enabled trigger of this instance (a nested acun:tabs has its own).
        resolveTab() {
            if (this.tab !== null && this.tab !== undefined && this.tab !== '') return;
            const first = [...this.$root.querySelectorAll('[role=tab]')].find(el => ! el.disabled && el.closest('[data-slot=tabs]') === this.$root);
            this.tab = this.initial ?? first?.dataset.value ?? null;
        },
        init() {
            this.resolveTab();
            this.$watch('tab', () => this.resolveTab());
        },
        // Arrow keys, Home and End on a tablist: focus that tab and open it.
        focusTab(list, to) {
            const tabs = [...list.querySelectorAll('[role=tab]')].filter(el => ! el.disabled && el.closest('[role=tablist]') === list);
            if (! tabs.length) return;
            const i = tabs.indexOf(document.activeElement);
            const next = to === 'first' ? tabs[0] : to === 'last' ? tabs[tabs.length - 1] : tabs[(Math.max(i, 0) + to + tabs.length) % tabs.length];
            next.focus();
            next.click();
        },
    }"
    x-id="['tab', 'tabpanel']"
    @if ($modelable) x-modelable="tab" @endif
    data-slot="tabs" {{ $attributes }}
>
    {{ $slot }}
</div>
@endif
