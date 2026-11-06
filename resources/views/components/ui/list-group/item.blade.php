@props([
    'href' => null,
    // A button (type="button") instead of text, for a row that acts on a click (wire:click, x-on:click).
    'button' => false,
    'active' => false,
    'disabled' => false,
])

{{--
    One row of acun:list-group (acun:list-group-item is the old name and renders the same):
    a link with href, a button with button, plain text without. Attributes (class, wire:key,
    wire:click…) go on the link / button / text inside the <li>.
        <acun:list-group.item button wire:click="choose(3)" :active="$chosen === 3">Kargo adresi</acun:list-group.item>
    A disabled button is disabled; type="submit" inside a form.
--}}
@php
    $classes = 'flex items-center gap-3 px-4 py-3 '.match (true) {
        $active => 'bg-primary-tint font-medium text-primary-tint-ink',
        $disabled => 'cursor-not-allowed text-faint',
        (bool) $href || $button => 'text-body hover:bg-hover/60',
        default => 'text-body',
    };
@endphp

<li>
    @if ($button)
        <button {{ $attributes->merge(['type' => 'button', 'class' => $classes.' w-full text-start cursor-pointer disabled:cursor-not-allowed']) }} @if ($active) aria-current="true" @endif @disabled($disabled)>{{ $slot }}</button>
    @elseif ($href && ! $disabled)
        <a href="{{ \Acun\Ui\Support\Url::safe((string) $href) ?? '#' }}" @if ($active) aria-current="true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
    @else
        <div @if ($disabled) aria-disabled="true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
    @endif
</li>
