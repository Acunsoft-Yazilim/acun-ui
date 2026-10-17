@props([
    'name',
    // Tailwind size-* value (4, 5, 6…).
    'size' => 5,
    // Stroke width, outline style only.
    'stroke' => 1.8,
    // Filled (solid) style.
    'solid' => false,
    // If given, screen readers announce the icon with this name; otherwise it is hidden.
    'label' => null,
])

{{--
    Heroicons: <acun:icon name="trash" /> · <acun:icon name="bell" solid size="6" />
    List: the Icons page in the catalog, or resources/icons/heroicons.php
--}}
@php($paths = \Acun\Ui\Support\Icon::paths($name, $solid ? 'solid' : 'outline'))

<svg
    viewBox="0 0 24 24"
    @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" @endif
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    {{ $attributes->merge(['class' => "size-{$size} flex-none"]) }}
>
    @foreach ($paths as $path)
        @if (is_array($path))
            <path fill-rule="evenodd" clip-rule="evenodd" d="{{ $path['d'] }}" />
        @elseif ($solid)
            <path d="{{ $path }}" />
        @else
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
        @endif
    @endforeach
</svg>
