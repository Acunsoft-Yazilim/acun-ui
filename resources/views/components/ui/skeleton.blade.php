@props([
    'variant' => 'block',
])

@php
    $shape = $variant === 'circle' ? 'rounded-full' : ($variant === 'text' ? 'rounded-sm h-3' : 'rounded-sm');
@endphp

<div {{ $attributes->merge(['class' => "animate-pulse bg-track motion-reduce:animate-none {$shape}"]) }}></div>
