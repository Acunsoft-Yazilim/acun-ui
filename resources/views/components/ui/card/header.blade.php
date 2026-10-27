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
            <{{ $headingTag }} @class(['text-sm font-semibold text-ink', 'flex items-center gap-2' => $row])>{{ $title }}{{ $slot }}</{{ $headingTag }}>
        @endif
        @if ($subtitle)
            <p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex flex-none items-center gap-1">
        {{ $actions ?? '' }}

        @if ($collapsible)
            <button type="button" data-slot="card-toggle" aria-expanded="true" aria-label="{{ __('Collapse / expand') }}" class="group flex size-7 cursor-pointer items-center justify-center rounded-sm text-muted hover:bg-hover hover:text-body">
                <svg class="size-4 rotate-180 transition-transform group-aria-expanded:rotate-0 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
            </button>
        @endif

        @if ($closable)
            <button type="button" data-acun-close aria-label="{{ __('Close') }}" class="flex size-7 cursor-pointer items-center justify-center rounded-sm text-muted hover:bg-hover hover:text-danger-ink">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        @endif
    </div>
</div>
