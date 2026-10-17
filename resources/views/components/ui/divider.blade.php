@props([
    // Text alignment: start · center · end
    'align' => 'center',
    // solid · dashed · dotted
    'variant' => 'solid',
])

@php
    $borderStyle = ['dashed' => 'border-dashed', 'dotted' => 'border-dotted'][$variant] ?? '';
    $lineClass = "h-px flex-1 border-t {$borderStyle} border-line";
@endphp

@if ($slot->isEmpty())
    <hr {{ $attributes->merge(['class' => "border-t {$borderStyle} border-line"]) }}>
@else
    <div role="separator" {{ $attributes->merge(['class' => 'flex items-center gap-3 text-xs font-medium tracking-wide text-muted uppercase']) }}>
        @if ($align !== 'start')<span class="{{ $lineClass }}"></span>@endif
        <span class="flex-none">{{ $slot }}</span>
        @if ($align !== 'end')<span class="{{ $lineClass }}"></span>@endif
    </div>
@endif
