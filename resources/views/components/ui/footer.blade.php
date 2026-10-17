{{--
    Page footer: copyright/text on the left (slot), links on the right (the `links` slot).
        <acun:footer>© 2026 Acun Yazılım <acun:slot:links><a href="#">Destek</a></acun:slot:links></acun:footer>
--}}
<footer {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3 py-4 text-sm text-gray-500 dark:text-zinc-400']) }}>
    <div>{{ $slot }}</div>

    @isset($links)
        <nav class="flex flex-wrap gap-4 [&_a]:hover:text-brand-600 dark:[&_a]:hover:text-brand-400">{{ $links }}</nav>
    @endisset
</footer>
