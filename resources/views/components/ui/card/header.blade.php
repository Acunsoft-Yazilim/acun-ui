@aware(['collapsible' => false, 'closable' => false, 'headingLevel' => 3])

@props([
    'title' => null,
    'subtitle' => null,
])

{{--
    The header of an acun:card written in parts; the same box as the classic header.
    The heading is the title followed by the slot, in one row: with a title the slot adds
    to it (a badge, a count), without one the slot is the whole heading (an icon, text,
    badges). subtitle is the grey line under it; <acun:slot:actions> sits on the right,
    followed by the collapse / close buttons when the card is collapsible / closable.
        <acun:card.header subtitle="Bugün 12 sipariş">
            <acun:icon name="shopping-bag" size="4" /> Siparişler <acun:badge variant="success">3 yeni</acun:badge>
            <acun:slot:actions><acun:button size="sm">Yeni sipariş</acun:button></acun:slot>
        </acun:card.header>
    Attributes (class, id…) go on the header box.

    collapsible / closable come from the nearest card that sets them: a card nested in a
    collapsible card that leaves them out takes the outer card's, so pass
    :collapsible="false" / :closable="false" on the inner card in that case.
--}}
@php($row = $slot->hasActualContent())

<div data-slot="card-header" {{ $attributes->merge(['class' => 'flex items-start justify-between gap-3 px-5 py-4']) }}>
    <div class="min-w-0">
        @if ($title || $row)
            @php($headingTag = 'h'.max(2, min(6, (int) $headingLevel)))
            <{{ $headingTag }} @class(['text-sm font-semibold text-gray-900 dark:text-zinc-100', 'flex items-center gap-2' => $row])>{{ $title }}{{ $slot }}</{{ $headingTag }}>
        @endif
        @if ($subtitle)
            <p class="mt-0.5 text-xs text-gray-500 dark:text-zinc-400">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex flex-none items-center gap-1">
        {{ $actions ?? '' }}

        @if ($collapsible)
            <button type="button" x-on:click="open = ! open" :aria-expanded="open.toString()" aria-label="{{ __('Collapse / expand') }}" class="flex size-7 cursor-pointer items-center justify-center rounded-sm text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                <svg class="size-4 transition-transform" :class="! open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
            </button>
        @endif

        @if ($closable)
            <button type="button" x-on:click="visible = false" aria-label="{{ __('Close') }}" class="flex size-7 cursor-pointer items-center justify-center rounded-sm text-gray-500 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-zinc-800 dark:hover:text-red-400">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        @endif
    </div>
</div>
