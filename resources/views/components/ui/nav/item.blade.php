@props([
    // With '_blank' it opens in a new tab (external link; wire:navigate is not used).
    'target' => null,
    'href',
    'title',
    'active' => false,
    // Number or short label on the right (e.g. 6, 'Yeni').
    'badge' => null,
    // Opens in a new tab (same as target="_blank"), with an icon and a screen-reader note.
    'external' => false,
])

@php($target = $external ? '_blank' : $target)
{{-- A link from data (e.g. a menu stored in the database) may hold javascript:; it becomes "#". --}}
@php($href = \Acun\Ui\Support\Url::safe((string) $href) ?? '#')

{{--
    Top-level link without a submenu. When active: neutral background, dark text
    and a 2px accent line on the menu's left edge (in the mobile drawer too). Icon from the `icon` slot.
--}}
<a
    href="{{ $href }}"
    @if ($target) target="{{ $target }}" rel="noopener noreferrer" @else wire:navigate @endif
    title="{{ $title }}"
    {{ $attributes->class([
        'relative flex h-[42px] items-center gap-3 rounded-sm px-3.5 text-sm transition-colors',
        'bg-brand-50 font-semibold text-brand-700 before:absolute before:top-2.5 before:bottom-2.5 before:-left-3 before:w-0.5 before:rounded-full before:bg-brand-600 dark:bg-brand-500/10 dark:text-brand-300 dark:before:bg-brand-400' => $active,
        'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-zinc-300 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-100' => ! $active,
    ]) }}
    @if ($active) aria-current="page" @endif
>
    {{ $icon ?? '' }}
    <span class="collapsed-hidden flex-1 truncate whitespace-nowrap">{{ $title }}</span>
    @if ($target === '_blank')
        <acun:icon name="arrow-top-right-on-square" size="3.5" class="collapsed-hidden flex-none opacity-50" />
        <span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
    @endif
    @if ($badge !== null)
        <span class="collapsed-hidden rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
    @endif
</a>
