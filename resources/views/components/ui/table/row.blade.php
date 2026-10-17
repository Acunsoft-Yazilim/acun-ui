{{--
    A row (<tr>) of acun:table.cell parts, inside acun:table.rows (or a <tfoot> written by
    hand after it). Attributes (class, wire:key, x-show…) go on the <tr>.
--}}
<tr data-slot="table-row" {{ $attributes }}>
    {{ $slot }}
</tr>
