@props([
    // The sort state for sortable acun:table.column parts (the sortable-th API): the
    // column whose field equals sortField shows the arrow and aria-sort.
    'sortField' => null,
    'sortDirection' => null,
    // The Livewire method a sortable acun:table.column calls with its field (sortBy).
    'sortMethod' => 'sortBy',
    // Cards on narrow screens: below sm (true, 'sm': 640px) or md ('md': 768px) of the table's
    // own width every row is a card: the first cell its title, the rest side by side under it
    // (a cell with a label prop / data-label as "Label … value"). false: the table scrolls sideways.
    'stack' => false,
])
{{--
    Usage:
    <acun:table>
        <table class="ui-table">
            <thead><tr><th>...</th></tr></thead>
            <tbody><tr><td>...</td></tr></tbody>
        </table>
    </acun:table>

    Or in parts, without writing the <table>: when the slot starts with an acun:table.*
    part, the table renders <table class="ui-table"> around it.
    <acun:table>
        <acun:table.columns>
            <acun:table.column>Müşteri</acun:table.column>
            <acun:table.column align="end">Sipariş</acun:table.column>
        </acun:table.columns>
        <acun:table.rows>
            <acun:table.row>
                <acun:table.cell>Ayşe Yılmaz</acun:table.cell>
                <acun:table.cell align="end">42</acun:table.cell>
            </acun:table.row>
        </acun:table.rows>
    </acun:table>

    The "ui-table" class (@acunsoft/acun-ui css/panel.css) applies default spacing,
    typography and hover styling to thead/tbody cells.

    "relative" makes the scroll box the containing block of absolutely positioned
    children such as sr-only text, so they stay inside it instead of widening the page.

    Attributes (class, id, wire:*…) go on the scroll box in both modes. A table wider than the
    box scrolls sideways with a shadow on the edge that has more (panel.css .ui-table-box).
--}}
@php
    $stack = $stack === true ? 'sm' : (in_array($stack, ['sm', 'md'], true) ? $stack : null);
@endphp
<div {{ $attributes->merge(['class' => 'ui-table-box relative overflow-x-auto overflow-y-hidden rounded-sm bg-white shadow-sm dark:bg-zinc-900', 'data-table-stack' => $stack]) }}>
@if (\Acun\Ui\Support\Parts::used($slot, 'table'))
    <table class="ui-table">{{ $slot }}</table>
@else
    {{ $slot }}
@endif
</div>
