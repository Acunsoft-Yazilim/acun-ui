@props([
    // Number of people left over after the ones shown ("+3").
    'more' => 0,
    // Size of the "+3" bubble; give the same size as the avatars: xs · sm · md · lg · xl.
    'size' => 'md',
])

@php($moreSize = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-xs', 'lg' => 'size-12 text-sm', 'xl' => 'size-16 text-base'][$size] ?? 'size-10 text-xs')

<div {{ $attributes->merge(['class' => 'flex items-center -space-x-2 [&>*]:ring-2 [&>*]:ring-surface [&>*]:rounded-full']) }}>
    {{ $slot }}
    @if ($more > 0)
        <span class="flex {{ $moreSize }} items-center justify-center rounded-full bg-secondary-soft font-semibold text-secondary-tint-ink">+{{ $more }}</span>
    @endif
</div>
