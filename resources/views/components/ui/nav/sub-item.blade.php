@props([
    // With '_blank' it opens in a new tab (external link; wire:navigate is not used).
    'target' => null,
    'href',
    'title',
    'active' => false,
    // In the horizontal menu's dropdown panel: no guide line, boxed item.
    'compact' => false,
    'badge' => null,
    // Opens in a new tab (same as target="_blank").
    'external' => false,
])

@php($target = $external ? '_blank' : $target)
{{-- A link from data (e.g. a menu stored in the database) may hold javascript:; it becomes "#". --}}
@php($href = \Acun\Ui\Support\Url::safe((string) $href) ?? '#')

{{--
    Sub-link inside a group. In the vertical menu it sits next to the group's
    guide line; when active it gets a 2px accent segment on the line and dark text.
--}}
<a
    href="{{ $href }}"
    @if ($target) target="{{ $target }}" rel="noopener noreferrer" @else wire:navigate @endif
    title="{{ $title }}"
    {{ $attributes->class([
        'relative flex items-center whitespace-nowrap transition-colors',
        'min-h-[38px] py-2 ps-4 pe-3 text-[13.5px]' => ! $compact,
        'h-9 rounded-sm px-3 text-sm' => $compact,
        'font-semibold text-gray-900 dark:text-zinc-100' => $active,
        'before:absolute before:top-2 before:bottom-2 before:-left-px before:w-0.5 before:rounded-full before:bg-brand-600 dark:before:bg-brand-400' => $active && ! $compact,
        'bg-gray-100 dark:bg-zinc-800' => $active && $compact,
        'text-gray-600 hover:text-gray-900 dark:text-zinc-300 dark:hover:text-zinc-100' => ! $active,
        'hover:bg-gray-50 dark:hover:bg-zinc-800/60' => ! $active && $compact,
    ]) }}
    @if ($active) aria-current="page" @endif
>
    <span class="flex-1 truncate">{{ $title }}</span>
    @if ($badge !== null)
        <span class="ms-2 rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
    @endif
    @if ($target === '_blank')
        <acun:icon name="arrow-top-right-on-square" size="3.5" class="ms-1.5 opacity-50" />
        <span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
    @endif
</a>
