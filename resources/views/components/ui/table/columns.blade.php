{{--
    The header row of an acun:table written in parts: a <thead> with one <tr> around
    the acun:table.column parts. Attributes (class, id…) go on the <thead>.
--}}
<thead data-slot="table-columns" {{ $attributes }}>
    <tr>
        {{ $slot }}
    </tr>
</thead>
