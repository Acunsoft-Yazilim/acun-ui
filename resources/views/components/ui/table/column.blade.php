@aware(['sortField' => null, 'sortDirection' => null, 'sortMethod' => 'sortBy'])

@props([
    // start · center · end: the text alignment of the column heading.
    'align' => null,
    // A sort button (acun:sortable-th): wire:click="sortMethod(field)", the arrow and aria-sort.
    'sortable' => false,
    'field' => null,
    // The sort state; read from the acun:table when left out.
    'sortField' => null,
    'sortDirection' => null,
    // The Livewire method the click calls (sortBy); read from the acun:table when left out.
    'sortMethod' => 'sortBy',
])

{{--
    A heading cell (<th scope="col">) inside acun:table.columns. With sortable and a
    field it is an acun:sortable-th: put :sort-field and :sort-direction on the acun:table
    (or on the column) and give the Livewire component a sortBy($field) method (or name your own
    with :sort-method on the acun:table).
        <acun:table :sort-field="$sortField" :sort-direction="$sortDirection">
            <acun:table.columns>
                <acun:table.column sortable field="customer">Müşteri</acun:table.column>
                <acun:table.column sortable field="orders" align="end">Sipariş</acun:table.column>
            </acun:table.columns>
            …
        </acun:table>
    Attributes (class, wire:key…) go on the <th>.
--}}
@php
    if ($sortable && blank($field)) {
        throw new InvalidArgumentException('acun:table.column sortable needs a field: the name passed to the sort method.');
    }

    if ($alignClass = ['start' => 'text-start', 'center' => 'text-center', 'end' => 'text-end'][$align] ?? null) {
        $attributes = $attributes->class($alignClass);
    }
@endphp

@if ($sortable)
    <acun:sortable-th :field="$field" :method="$sortMethod" :sort-field="$sortField" :sort-direction="$sortDirection" data-slot="table-column" scope="col" {{ $attributes }}>{{ $slot }}</acun:sortable-th>
@else
    <th data-slot="table-column" scope="col" {{ $attributes }}>{{ $slot }}</th>
@endif
