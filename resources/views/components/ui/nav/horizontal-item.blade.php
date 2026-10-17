@props([
    // With '_blank' it opens in a new tab (external link; wire:navigate is not used).
    'target' => null,
    'href',
    'title',
    'active' => false,
    // Number or short label after the title.
    'badge' => null,
    // Opens in a new tab (same as target="_blank"), with an icon and a screen-reader note.
    'external' => false,
])

@php($target = $external ? '_blank' : $target)
{{-- A link from data (e.g. a menu stored in the database) may hold javascript:; it becomes "#". --}}
@php($href = \Acun\Ui\Support\Url::safe((string) $href) ?? '#')

{{-- Link without a submenu in the horizontal (top) menu; when active a 2px accent line below it. Icon from the `icon` slot. --}}
<a
    href="{{ $href }}"
    @if ($target) target="{{ $target }}" rel="noopener noreferrer" @else wire:navigate @endif
    {{ $attributes->class([
        'relative flex h-full items-center gap-2 px-3 text-sm whitespace-nowrap transition-colors',
        'font-semibold text-gray-900 after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-brand-600 dark:text-zinc-100' => $active,
        'font-medium text-gray-700 hover:text-gray-900 dark:text-zinc-300 dark:hover:text-zinc-100' => ! $active,
    ]) }}
    @if ($active) aria-current="page" @endif
>
    {{ $icon ?? '' }}
    {{ $title }}
    @if ($target === '_blank')
        <acun:icon name="arrow-top-right-on-square" size="3.5" class="flex-none opacity-50" />
        <span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
    @endif
    @if ($badge !== null)
        <span class="rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
    @endif
</a>
