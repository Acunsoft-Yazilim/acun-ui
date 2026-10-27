@props([
    'title' => null,
    'subtitle' => null,
    // Collapse/expand the body with a button in the header.
    'collapsible' => false,
    // Hide the card with an X in the header.
    'closable' => false,
    // Body padding; false when a table or list should reach the edges.
    'padding' => true,
    // The title's heading level (2–6), to fit the page's outline; card.header reads it too.
    'headingLevel' => 3,
])
{{--
    Classic: title, subtitle, <acun:slot:actions>, <acun:slot:footer> and padding draw the
    header, body and footer around the slot.

    In parts: when the slot starts with acun:card.header, acun:card.body or acun:card.footer,
    the card draws only its box (and the collapsible / closable state) and renders the slot
    as it is. title, subtitle, actions, footer and padding are then unused; each part takes
    its own. card.header and card.body read collapsible / closable from the card.
        <acun:card collapsible>
            <acun:card.header title="Siparişler" subtitle="Bugün 12 sipariş">
                <acun:slot:actions><acun:button size="sm">Yeni sipariş</acun:button></acun:slot>
            </acun:card.header>
            <acun:card.body :padding="false">…</acun:card.body>
            <acun:card.footer>…</acun:card.footer>
        </acun:card>
    Attributes (class, id, wire:*, x-*…) go on the card box in both modes.
--}}
<div
    data-acun-surface
    @if ($collapsible || $closable) data-acun-card @endif
    {{ $attributes->merge(['class' => 'flex flex-col rounded-sm bg-surface shadow-sm']) }}
>
@if (\Acun\Ui\Support\Parts::used($slot, 'card'))
    {{ $slot }}
@else
    @if ($title || $subtitle || isset($actions) || $collapsible || $closable)
        <div class="flex items-start justify-between gap-3 px-5 py-4">
            <div class="min-w-0">
                @if ($title)
                    @php($headingTag = 'h'.max(2, min(6, (int) $headingLevel)))
                    <{{ $headingTag }} class="text-sm font-semibold text-ink">{{ $title }}</{{ $headingTag }}>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="flex flex-none items-center gap-1">
                {{ $actions ?? '' }}

                @if ($collapsible)
                    {{-- The card's title in its name (every card has one); js/plugins/card.js ties it to the body (aria-controls). --}}
                    <button type="button" data-slot="card-toggle" aria-expanded="true" aria-label="{{ $title ? __('Collapse / expand :title', ['title' => $title]) : __('Collapse / expand') }}" class="group flex size-7 cursor-pointer items-center justify-center rounded-sm text-muted hover:bg-hover hover:text-body">
                        <svg class="size-4 rotate-180 transition-transform group-aria-expanded:rotate-0 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
                    </button>
                @endif

                @if ($closable)
                    <button type="button" data-acun-close aria-label="{{ $title ? __('Close :title', ['title' => $title]) : __('Close') }}" class="flex size-7 cursor-pointer items-center justify-center rounded-sm text-muted hover:bg-hover hover:text-danger-ink">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if ($collapsible)
        {{-- Folds in CSS: a one-row grid whose row closes from 1fr to 0fr (js/plugins/card.js). --}}
        <div data-slot="card-body" data-open class="grid flex-1 grid-rows-[0fr] transition-[grid-template-rows] duration-200 ease-out data-open:grid-rows-[1fr] motion-reduce:transition-none">
            <div class="min-h-0 overflow-hidden"><div @class(['p-5' => $padding])>{{ $slot }}</div></div>
        </div>
    @else
        <div @class(['flex-1', 'p-5' => $padding])>
            {{ $slot }}
        </div>
    @endif

    @isset($footer)
        <div class="flex items-center gap-2 border-t border-line-soft px-5 py-3">{{ $footer }}</div>
    @endisset
@endif
</div>
