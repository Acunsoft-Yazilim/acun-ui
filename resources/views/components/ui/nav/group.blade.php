@props([
    'title',
    'active' => false,
    // Number or short label on the right (e.g. 6, 'Yeni').
    'badge' => null,
])

{{--
    Group with a submenu. Sub-items are listed next to a thin guide line on the
    left; the active sub-item is highlighted on the line. A group containing the
    active page is rendered open on the server (closed ones get display:none) and
    the chevron direction comes from aria-expanded: the menu is correct even on
    the first frame before Alpine loads.

    Accordion: only one group open at a time. An opening group announces its name
    with the `menu-group-opened` event and the others close (since the menu is
    rendered twice - side bar and mobile drawer - same-named copies do not close
    each other).
--}}
<div {{ $attributes }}
    x-data="{ open: @js($active), name: @js($title) }"
    x-on:menu-group-opened.window="if ($event.detail !== name) open = false"
>
    <button
        type="button"
        x-on:click="open = !open; if (open) $dispatch('menu-group-opened', name)" aria-expanded="{{ $active ? 'true' : 'false' }}" :aria-expanded="open.toString()"
        title="{{ $title }}"
        @class([
            'group flex h-[42px] w-full cursor-pointer items-center gap-3 overflow-hidden rounded-sm px-3.5 text-sm transition-colors',
            'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $active,
            'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-zinc-300 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-100' => ! $active,
        ])
    >
        {{ $icon ?? '' }}
        <span class="collapsed-hidden flex-1 truncate text-left whitespace-nowrap">{{ $title }}</span>
        @if ($badge !== null)
            <span class="collapsed-hidden rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
        @endif
        <svg class="collapsed-hidden size-3.5 flex-none opacity-60 transition-transform group-aria-expanded:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
        </svg>
    </button>

    <div
        class="submenu my-1 ms-[1.5625rem] flex flex-col border-s border-gray-200 dark:border-zinc-700"
        x-show="open"
        @unless ($active) style="display: none" @endunless
    >
        {{ $slot }}
    </div>
</div>
