@props([
    'href' => null,
    'active' => false,
    'disabled' => false,
])

{{--
    One row of acun:list-group (acun:list-group-item is the old name and renders the same):
    a link with href, plain text without. Attributes (class, wire:key…) go on the
    link / text inside the <li>.
--}}
@php
    $classes = 'flex items-center gap-3 px-4 py-3 '.match (true) {
        $active => 'bg-brand-50 font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300',
        $disabled => 'cursor-not-allowed text-gray-400 dark:text-zinc-600',
        (bool) $href => 'text-gray-700 hover:bg-gray-50 dark:text-zinc-300 dark:hover:bg-zinc-800/60',
        default => 'text-gray-700 dark:text-zinc-300',
    };
@endphp

<li>
    @if ($href && ! $disabled)
        <a href="{{ \Acun\Ui\Support\Url::safe((string) $href) ?? '#' }}" @if ($active) aria-current="true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
    @else
        <div @if ($disabled) aria-disabled="true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
    @endif
</li>
