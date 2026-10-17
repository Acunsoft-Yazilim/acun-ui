@props([
    'title',
    'active' => false,
    // Number or short label on the right.
    'badge' => null,
])

{{--
    Level 3 in the vertical menu: a collapsible sub-group inside a group. Its
    sub-items are listed next to their own guide line, one step further in.
--}}
<div {{ $attributes }} x-data="{ open: @js($active) }">
    <button
        type="button"
        x-on:click="open = ! open"
        aria-expanded="{{ $active ? 'true' : 'false' }}"
        :aria-expanded="open.toString()"
        @class([
            'group flex min-h-[38px] w-full cursor-pointer items-center gap-2 py-2 ps-4 pe-3 text-left text-[13.5px] transition-colors',
            'font-semibold text-gray-900 dark:text-zinc-100' => $active,
            'text-gray-600 hover:text-gray-900 dark:text-zinc-300 dark:hover:text-zinc-100' => ! $active,
        ])
    >
        <span class="flex-1 truncate">{{ $title }}</span>
        @if ($badge !== null)
            <span class="rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
        @endif
        <svg class="size-3 flex-none opacity-60 transition-transform group-aria-expanded:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
    </button>

    <div x-show="open" @unless ($active) style="display: none" @endunless class="ms-4 flex flex-col border-s border-gray-200 dark:border-zinc-700">
        {{ $slot }}
    </div>
</div>
