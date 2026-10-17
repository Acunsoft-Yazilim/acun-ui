@props([
    // true: flush list without the outer border.
    'flush' => false,
])

{{--
    A bordered list of rows; each row is an acun:list-group.item.
        <acun:list-group>
            <acun:list-group.item href="/siparisler/1042" active>Sipariş #1042</acun:list-group.item>
            <acun:list-group.item disabled>Sipariş #1043</acun:list-group.item>
        </acun:list-group>
--}}
<ul {{ $attributes->class([
    'divide-y divide-line text-sm',
    'overflow-hidden rounded-sm border border-line bg-surface' => ! $flush,
]) }}>
    {{ $slot }}
</ul>
