@props([
    // Number of people left over after the ones shown ("+3").
    'more' => 0,
    // Size of the "+3" bubble; give the same size as the avatars: xs · sm · md · lg · xl.
    'size' => 'md',
])

@php($moreSize = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-xs', 'lg' => 'size-12 text-sm', 'xl' => 'size-16 text-base'][$size] ?? 'size-10 text-xs')

<div {{ $attributes->merge(['class' => 'flex items-center -space-x-2 [&>*]:ring-2 [&>*]:ring-white [&>*]:rounded-full dark:[&>*]:ring-zinc-900']) }}>
    {{ $slot }}
    @if ($more > 0)
        <span class="flex {{ $moreSize }} items-center justify-center rounded-full bg-secondary-100 font-semibold text-secondary-700 dark:bg-secondary-500/15 dark:text-secondary-300">+{{ $more }}</span>
    @endif
</div>
