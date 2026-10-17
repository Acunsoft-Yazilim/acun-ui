@props([
    'title',
    'label' => null,
    // Boxed look in the horizontal menu's dropdown panel (see sub-item).
    'compact' => false,
])

{{-- A submenu item that is not available yet: not clickable, "Yakında" (coming soon) label on the right. --}}
<span
    aria-disabled="true"
    {{ $attributes->class([
        'flex w-full cursor-not-allowed items-center gap-2 whitespace-nowrap text-gray-400 dark:text-zinc-600',
        'min-h-[38px] py-2 ps-4 pe-3 text-[13.5px]' => ! $compact,
        'h-9 px-3 text-sm' => $compact,
    ]) }}
>
    <span class="flex-1 truncate">{{ $title }}</span>
    <span class="rounded-sm bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] tracking-wide uppercase dark:bg-zinc-800">{{ $label ?? __('Coming soon') }}</span>
</span>
