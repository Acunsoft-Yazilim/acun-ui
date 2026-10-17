@props([
    // Link of the step; leave it out for the current page.
    'href' => null,
    // The current page: plain text with aria-current="page", even with href.
    // Default: an item without href (usually the last one).
    'current' => null,
    // false: no separator before the item, e.g. to write your own acun:breadcrumb.separator.
    'separator' => true,
])

{{--
    One step of acun:breadcrumb written in parts: the separator (a sibling drawn before
    the step), then a link with href, or plain text. Attributes (class, wire:key, title…)
    go on the link / text.
--}}
@php
    $current ??= $href === null;
    $href = $href === null ? null : (\Acun\Ui\Support\Url::safe((string) $href) ?? '#');
@endphp

@if ($separator)
    <acun:breadcrumb.separator />
@endif
@if ($current)
    <span data-slot="breadcrumb-item" {{ $attributes->class('whitespace-nowrap font-medium text-gray-700 dark:text-zinc-200') }} aria-current="page">{{ $slot }}</span>
@elseif ($href !== null)
    <a data-slot="breadcrumb-item" href="{{ $href }}" wire:navigate {{ $attributes->class('whitespace-nowrap hover:text-gray-700 dark:hover:text-zinc-200') }}>{{ $slot }}</a>
@else
    <span data-slot="breadcrumb-item" {{ $attributes->class('whitespace-nowrap') }}>{{ $slot }}</span>
@endif
