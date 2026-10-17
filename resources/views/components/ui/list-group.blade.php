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
    'divide-y divide-gray-200 text-sm dark:divide-zinc-800',
    'overflow-hidden rounded-sm border border-gray-200 bg-white dark:border-zinc-800 dark:bg-zinc-900' => ! $flush,
]) }}>
    {{ $slot }}
</ul>
