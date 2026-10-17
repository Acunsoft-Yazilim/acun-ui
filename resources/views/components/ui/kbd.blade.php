@props([
    // Keys of a combination, e.g. ['⌘', 'K']; without it the slot is a single key.
    'keys' => null,
    // Shown between the keys of a combination; '' for none.
    'separator' => '+',
    // sm · md
    'size' => 'md',
])

{{--
    Usage:
    <acun:kbd>Esc</acun:kbd>
    <acun:kbd :keys="['⌘', 'K']" />
    <acun:kbd :keys="['Ctrl', 'Shift', 'P']" size="sm" />

    A combination is a <kbd> wrapping one <kbd> per key (the HTML convention).
    Symbol keys (⌘ ⌥ ⇧ ⌃ ↵ ⌫ ⎋ ⇥ and the arrows) get a spoken name for screen readers and
    are drawn in the sans font, whose symbol glyphs are larger than the mono fallback.
--}}
@php
    $keyClass = ([
        'sm' => 'h-5 min-w-5 px-1 text-2xs',
        'md' => 'h-6 min-w-6 px-1.5 text-xs',
    ][$size] ?? 'h-6 min-w-6 px-1.5 text-xs')
        .' inline-flex items-center justify-center rounded-sm border border-gray-200 border-b-gray-300 bg-white font-mono font-medium text-gray-600 shadow-xs dark:border-zinc-700 dark:border-b-zinc-600 dark:bg-zinc-800 dark:text-zinc-300';

    $names = [
        '⌘' => __('Command'),
        // Not translated: the key is labelled Option on Apple keyboards, and the storefront's
        // translation of "Option" (a product option, "Seçenek") would rename it.
        '⌥' => 'Option',
        '⇧' => __('Shift'),
        '⌃' => __('Control'),
        '↵' => __('Enter'),
        '⏎' => __('Enter'),
        '⌫' => __('Backspace'),
        '⎋' => __('Esc'),
        '⇥' => __('Tab'),
        '↑' => __('Up arrow'),
        '↓' => __('Down arrow'),
        '←' => __('Left arrow'),
        '→' => __('Right arrow'),
    ];

    $single = trim((string) $slot);
@endphp

@if ($keys !== null)
    <kbd {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 font-sans']) }}>
        @foreach ((array) $keys as $key)
            @if (! $loop->first && $separator !== '')
                <span class="text-xs text-gray-500 dark:text-zinc-400">{{ $separator }}</span>
            @endif
            <kbd class="{{ $keyClass }}">@isset($names[$key])<span class="sr-only">{{ $names[$key] }}</span><span aria-hidden="true" class="font-sans">{{ $key }}</span>@else{{ $key }}@endisset</kbd>
        @endforeach
    </kbd>
@else
    <kbd {{ $attributes->merge(['class' => $keyClass]) }}>@isset($names[$single])<span class="sr-only">{{ $names[$single] }}</span><span aria-hidden="true" class="font-sans">{{ $single }}</span>@else{{ $slot }}@endisset</kbd>
@endif
