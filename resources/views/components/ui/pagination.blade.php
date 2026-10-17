{{--
    The bar is ALWAYS shown, with no results too ("0 sonuç", buttons disabled):
    a filter that empties the list must not also remove the footer, or the page
    height jumps. With one page the previous/next buttons are disabled and
    page "1" is highlighted.

    Changing the page scrolls the list (the Livewire component) back to its top
    when that top is above the screen, so the new page is read from its first
    row instead of wherever the old page left the scroll position.

    Works with any page name ($query->paginate(10, pageName: 'ordersPage')): the
    Livewire calls pass the paginator's own name, so two lists on one page page apart.

    paginate() gets the summary, the page numbers and the arrows; simplePaginate() the
    summary without the total and the arrows; cursorPaginate() the arrows only (a cursor
    has no page numbers).
--}}
@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator || $paginator instanceof \Illuminate\Contracts\Pagination\CursorPaginator)
    @php
        $lengthAware = $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
        $cursor = $paginator instanceof \Illuminate\Contracts\Pagination\CursorPaginator;
        $pageName = \Illuminate\Support\Js::from($cursor ? $paginator->getCursorName() : $paginator->getPageName());
        // The summary is one sentence per kind, translated as a whole; its numbers are put in after.
        $summary = fn (string $sentence, array $numbers) => strtr(e(__($sentence)), collect($numbers)->mapWithKeys(
            fn ($number, $name) => [':'.$name => '<span class="font-medium tabular-nums">'.e(\Acun\Ui\Support\Format::number((int) $number)).'</span>'],
        )->all());
        // Livewire: the page methods for paginate()/simplePaginate(), setPage(cursor) for cursorPaginate().
        $previousCall = $cursor
            ? 'setPage('.\Illuminate\Support\Js::from(($paginator->previousCursor() ?? $paginator->cursor())?->encode()).', '.$pageName.')'
            : 'previousPage('.$pageName.')';
        $nextCall = $cursor
            ? 'setPage('.\Illuminate\Support\Js::from(($paginator->nextCursor() ?? $paginator->cursor())?->encode()).', '.$pageName.')'
            : 'nextPage('.$pageName.')';
    @endphp
    {{-- On phones only previous / current / next are shown, so the bar never widens the page. --}}
    <nav
        class="flex flex-wrap items-center justify-between gap-3 p-3 bg-white rounded-sm border border-gray-200 dark:bg-zinc-900 dark:border-zinc-700"
        role="navigation"
        aria-label="{{ __('Pagination Navigation') }}"
        x-data
        x-on:click="if ($event.target.closest('a, button:not([disabled])')) { const root = $el.closest('[wire\\:id]'); if (root && root.getBoundingClientRect().top < 0) root.scrollIntoView({ block: 'start' }); }"
    >
        <div class="text-sm text-gray-600 dark:text-zinc-400">
            @if ($paginator->isEmpty() && $paginator->onFirstPage())
                {{ __('No results') }}
            @elseif ($lengthAware)
                {!! $summary('Showing :first to :last of :total results', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem(), 'total' => $paginator->total()]) !!}
            @elseif (! $cursor && $paginator->isNotEmpty())
                {!! $summary('Showing :first to :last', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem()]) !!}
            @endif
        </div>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <acun:button variant="white" size="icon" type="button" class="disabled:!opacity-40" disabled aria-label="{{ __('Previous') }}" :title="__('You are on the first page')">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </acun:button>
            @else
                <acun:button variant="white" size="icon" href="{{ $paginator->previousPageUrl() }}" wire:click.prevent="{{ $previousCall }}" dusk="pagination.previous-page" aria-label="{{ __('Previous') }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </acun:button>
            @endif

            {{--
                A fixed number of slots on every page ("1 2 3 4 5 … 200", "1 … 99 100 101 … 200",
                "1 … 196 197 198 199 200") with equal-width buttons, so the bar keeps its width
                and the arrows stay under the pointer while paging.
            --}}
            @foreach ($lengthAware ? \Acun\Ui\Support\PaginationWindow::slots($paginator->currentPage(), $paginator->lastPage(), (int) $paginator->onEachSide) : [] as $slot)
                @if ($slot === null)
                    <span class="hidden min-w-9 py-1.5 text-center text-sm text-gray-400 sm:inline-block dark:text-zinc-500" aria-hidden="true">…</span>
                @elseif ($slot === $paginator->currentPage())
                    <span aria-current="page" class="inline-flex min-w-9 items-center justify-center rounded-sm bg-brand-600 px-2 py-1.5 text-xs font-semibold text-white tabular-nums">{{ $slot }}</span>
                @else
                    <acun:button variant="white" size="sm" class="hidden min-w-9 tabular-nums sm:inline-flex" href="{{ $paginator->url($slot) }}" wire:click.prevent="gotoPage({{ $slot }}, {{ $pageName }})">{{ $slot }}</acun:button>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <acun:button variant="white" size="icon" href="{{ $paginator->nextPageUrl() }}" wire:click.prevent="{{ $nextCall }}" dusk="pagination.next-page" aria-label="{{ __('Next') }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </acun:button>
            @else
                <acun:button variant="white" size="icon" type="button" class="disabled:!opacity-40" disabled aria-label="{{ __('Next') }}" :title="__('You are on the last page')">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </acun:button>
            @endif
        </div>
    </nav>
@endif
