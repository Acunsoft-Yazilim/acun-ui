@props([
    'variant' => 'block',
])

@php
    $shape = $variant === 'circle' ? 'rounded-full' : ($variant === 'text' ? 'rounded-sm h-3' : 'rounded-sm');
@endphp

<div {{ $attributes->merge(['class' => "animate-pulse bg-gray-200 dark:bg-zinc-700 {$shape}"]) }}></div>
