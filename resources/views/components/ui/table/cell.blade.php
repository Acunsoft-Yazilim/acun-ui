@props([
    // start · center · end: the text alignment of the cell.
    'align' => null,
    // On a stacked acun:table's cards the cell reads "label … value" (data-label).
    'label' => null,
])

{{--
    A data cell (<td>) inside acun:table.row. Attributes (class, colspan…) go on the <td>.
--}}
@php
    if ($alignClass = ['start' => 'text-start', 'center' => 'text-center', 'end' => 'text-end'][$align] ?? null) {
        $attributes = $attributes->class($alignClass);
    }
@endphp
<td data-slot="table-cell" {{ $attributes->merge(['data-label' => filled($label) ? $label : null]) }}>{{ $slot }}</td>
