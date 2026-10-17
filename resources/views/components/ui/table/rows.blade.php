{{--
    The body of an acun:table written in parts: a <tbody> around acun:table.row parts.
    Attributes (class, id, wire:sort…) go on the <tbody>.
--}}
<tbody data-slot="table-rows" {{ $attributes }}>
    {{ $slot }}
</tbody>
