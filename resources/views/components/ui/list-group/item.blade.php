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
        $active => 'bg-primary-tint font-medium text-primary-tint-ink',
        $disabled => 'cursor-not-allowed text-faint',
        (bool) $href => 'text-body hover:bg-hover/60',
        default => 'text-body',
    };
@endphp

<li>
    @if ($href && ! $disabled)
        <a href="{{ \Acun\Ui\Support\Url::safe((string) $href) ?? '#' }}" @if ($active) aria-current="true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
    @else
        <div @if ($disabled) aria-disabled="true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
    @endif
</li>
