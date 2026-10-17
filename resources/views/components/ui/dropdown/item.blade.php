@props([
    'href' => null,
    // With href: opens in a new tab, with an icon and a screen-reader note.
    'external' => false,
    // An acun:icon name before the text.
    'icon' => null,
    // Red text, for a destructive action (Sil…).
    'danger' => false,
])

{{--
    A row of an acun:dropdown panel (classic or acun:dropdown.content); also usable in
    any other menu. With href it is a link, otherwise a button (type="button"; pass
    type="submit" inside a form). Attributes (class, wire:click, disabled…) go on the
    link / button.
--}}
@php
    // A link from data may hold javascript: (see Acun\Ui\Support\Url): it becomes "#".
    $href = $href === null || $href === '' ? null : (\Acun\Ui\Support\Url::safe((string) $href) ?? '#');
    $classes = 'flex w-full items-center gap-2 px-3 py-2 text-sm '.($danger
        ? 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10'
        : 'text-gray-700 hover:bg-gray-100 dark:text-zinc-200 dark:hover:bg-zinc-800');
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($external) target="_blank" rel="noopener" @endif {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<acun:icon :name="$icon" size="4" :class="$danger ? '' : 'text-gray-400 dark:text-zinc-500'" />@endif{{ $slot }}@if ($external)<acun:icon name="arrow-top-right-on-square" size="3.5" class="ms-auto opacity-50" /><span class="sr-only"> {{ __('(opens in a new tab)') }}</span> @endif</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes . ' text-left']) }}>@if ($icon)<acun:icon :name="$icon" size="4" :class="$danger ? '' : 'text-gray-400 dark:text-zinc-500'" />@endif{{ $slot }}</button>
@endif
