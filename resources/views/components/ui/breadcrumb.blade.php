@props([
    'items' => [],
    // Root (home icon) link: a route name or a direct URL. Default:
    // the 'dashboard' route if it is defined, otherwise the site root.
    'homeRoute' => 'dashboard',
    'homeUrl' => null,
    'homeLabel' => null,
])

{{--
    :items="[['label' => 'Siparişler', 'route' => 'orders.index'], ['label' => 'Düzenle']]"
    Items may also take 'url' instead of 'route'. An item without a link, and
    the last item, are always shown as plain text (the current page). The
    "Anasayfa" (home) root is added automatically; there is no need to pass it.

    Without items, the slot is written in parts, for items with more than a label
    (an icon, a badge) or with attributes of their own (wire:key, x-show…):
        <acun:breadcrumb>
            <acun:breadcrumb.item href="/siparisler">Siparişler</acun:breadcrumb.item>
            <acun:breadcrumb.item>Sipariş #1042</acun:breadcrumb.item>
        </acun:breadcrumb>
    The home link still comes first and each item draws the separator before itself,
    so both forms look the same. With items, the slot is ignored.
--}}
@php
    $homeHref = $homeUrl ?? (\Illuminate\Support\Facades\Route::has($homeRoute) ? route($homeRoute) : url('/'));
    $allItems = [['label' => $homeLabel ?? __('Home'), 'url' => $homeHref], ...$items];
    $lastIndex = count($allItems) - 1;
    // Items may come from data: javascript: and other schemes become "#" (Acun\Ui\Support\Url).
    $hrefFor = fn (array $item) => isset($item['url']) ? (\Acun\Ui\Support\Url::safe((string) $item['url']) ?? '#') : (! empty($item['route']) ? route($item['route']) : null);
    $parts = blank($items) && $slot->isNotEmpty();
@endphp

<nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-zinc-400']) }}>
    @foreach ($allItems as $i => $item)
        @if ($i > 0)
            <svg class="size-3.5 flex-none text-gray-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
        @endif

        @if ($i === 0)
            <a href="{{ $hrefFor($item) }}" wire:navigate class="flex-none hover:text-gray-700 dark:hover:text-zinc-200" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            </a>
        @elseif ($hrefFor($item) !== null && $i !== $lastIndex)
            <a href="{{ $hrefFor($item) }}" wire:navigate class="whitespace-nowrap hover:text-gray-700 dark:hover:text-zinc-200">{{ $item['label'] }}</a>
        @else
            <span class="whitespace-nowrap {{ $i === $lastIndex ? 'font-medium text-gray-700 dark:text-zinc-200' : '' }}" @if ($i === $lastIndex) aria-current="page" @endif>{{ $item['label'] }}</span>
        @endif
    @endforeach
{{-- At the start of the line, so the output of the items stays exactly as it was. --}}@if ($parts)
    {{ $slot }}
@endif
</nav>
