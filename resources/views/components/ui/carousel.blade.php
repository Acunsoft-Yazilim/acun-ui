@props([
    // Auto-advance interval (ms); off when null. Stops under the pointer and the focus, has a pause button.
    'interval' => null,
    // Accessible name of the region; default "Images" in the current language.
    'label' => null,
    // Slides in view: a number (1–6), or one per breakpoint: ['base' => 2, 'sm' => 3, 'lg' => 4] (a breakpoint
    // left out takes the one before it). More than one makes a strip (product rows).
    'perView' => 1,
    // Space between the slides, a spacing step: 0, 2, 3, 4, 6 or 8 (default 0 with one slide in view, else 4).
    'gap' => null,
    // true: after the last slide comes the first again (and back); 'infinite': a seamless loop (Swiper moves the
    // slides around; not for slides a Livewire update changes); false: stops at the ends.
    'loop' => true,
    // The shown slide in the middle (with several in view, the neighbours peek in at both sides).
    'centered' => false,
    // Dragging with the mouse (a finger always swipes); false: only the arrows, the dots and the keys move it.
    'draggable' => true,
    // Stopping at a slide; false: free scrolling with momentum (a strip of many cards).
    'snap' => true,
    // The carousel takes the height of the shown slide.
    'autoHeight' => false,
    // The previous / next buttons, the dots (one per position) and the "3 / 8" counter.
    'arrows' => true,
    'pagination' => true,
    'counter' => false,
    // Where the thumbnails go (<acun:slot:thumbnails> with acun:carousel.thumb): 'horizontal' (default) under the
    // slides, 'vertical' beside them.
    'thumbs' => null,
    // The slides' pictures zoom: a double tap or a pinch, a double click or the zoom button (slides written with
    // acun:carousel.item).
    'zoom' => false,
    // A button (and a click or tap on a slide's picture) opens the slides in a full-screen dialog with zoom, the
    // arrows, the counter and, on wide screens, the thumbnails; it starts at the shown slide and, closed, leaves the
    // carousel at the one it showed last. With fullscreen, a double tap on the page's own carousel no longer zooms
    // (a tap opens the dialog, which zooms); a pinch and the zoom button still do.
    'fullscreen' => false,
    // The name of the set (acun:carousel.set) the carousel's own slides are: another set replaces them on
    // showSet(name) or the acun:carousel:set event ({ name }), e.g. a product's other colour.
    'set' => null,
])

{{--
    Each slide is a direct child element:
        <acun:carousel :interval="5000">
            <img src="…" alt="…">
            <div>…</div>
        </acun:carousel>
    acun:carousel.item is a slide that also names itself for screen readers:
        <acun:carousel.item label="Depo girişi"><img src="…" alt="…"></acun:carousel.item>
    A product strip: several slides in view, stops at the ends:
        <acun:carousel :per-view="['base' => 2, 'md' => 3, 'xl' => 4]" :loop="false" :label="__('Related products')">…</acun:carousel>
    A gallery: thumbnails (one per slide, in the same order), zoom and a full-screen view:
        <acun:carousel zoom fullscreen :pagination="false">
            <acun:carousel.item label="Front"><img src="…" alt="…"></acun:carousel.item> …
            <acun:slot:thumbnails><acun:carousel.thumb label="Front"><img src="…" alt=""></acun:carousel.thumb> …</acun:slot:thumbnails>
        </acun:carousel>

    The sliding is the runtime's carousel plugin on Swiper (js/plugins/carousel.js, loaded only on a page with a
    carousel): drag or swipe, the arrows, the dots, the thumbnails, the Left / Right keys. With interval it stops under
    the pointer and the keyboard focus, has a pause button (WCAG 2.2.2) and never runs when the OS asks for reduced
    motion. Slides added or removed later (a Livewire update) are counted. Without JavaScript the slides scroll sideways.
--}}
@php
    $breakpoints = ['base', 'sm', 'md', 'lg', 'xl'];
    $given = is_array($perView) ? $perView : ['base' => $perView];
    $views = [];
    $previous = 1;
    foreach ($breakpoints as $breakpoint) {
        $value = $given[$breakpoint] ?? ($breakpoint === 'base' ? ($given[0] ?? null) : null);
        $previous = $value === null ? $previous : max(1, min(6, (int) $value));
        $views[$breakpoint] = $previous;
    }
    $strip = max($views) > 1;
    $gaps = [
        0 => '[--carousel-gap:0px]',
        2 => '[--carousel-gap:--spacing(2)]',
        3 => '[--carousel-gap:--spacing(3)]',
        4 => '[--carousel-gap:--spacing(4)]',
        6 => '[--carousel-gap:--spacing(6)]',
        8 => '[--carousel-gap:--spacing(8)]',
    ];
    $gapClass = $gaps[(int) ($gap ?? ($strip ? 4 : 0))] ?? $gaps[4];
    $style = $strip ? implode('; ', array_map(fn (string $breakpoint): string => "--carousel-per-view-{$breakpoint}: {$views[$breakpoint]}", $breakpoints)) : null;
    $loopMode = $loop === 'infinite' ? 'infinite' : ($loop ? 'rewind' : 'off');
    $thumbsMode = isset($thumbnails) && trim((string) $thumbnails) !== '' ? ($thumbs === 'vertical' ? 'vertical' : 'horizontal') : null;
    // A slider on its own sits on a dark frame; a strip and a gallery with thumbnails sit on the page.
    $framed = ! $strip && ! $thumbsMode;
    $overlay = 'absolute z-10 hidden size-9 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink shadow group-data-controls/carousel:flex hover:bg-surface focus-visible:outline-2 focus-visible:outline-surface aria-disabled:cursor-default aria-disabled:opacity-40';
    $edge = 'absolute z-10 hidden size-10 cursor-pointer items-center justify-center rounded-full bg-surface text-ink shadow sm:group-data-controls/carousel:flex hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus aria-disabled:cursor-default aria-disabled:opacity-0';
    $button = $framed ? $overlay : $edge;
    // The full-screen dialog's name (the modal plugin's): from the carousel's id, else one made from its content.
    $carouselId = $fullscreen ? \Acun\Ui\Support\Field::id('carousel', $attributes, null, $label, $thumbsMode) : null;
    $hasSets = str_contains($slot->toHtml(), 'data-slot="carousel-set"');
@endphp
<div
    data-acun-carousel
    @if ($interval) data-interval="{{ (int) $interval }}" @endif
    data-loop="{{ $loopMode }}"
    @if ($strip) data-strip @endif
    @if ($centered) data-centered @endif
    @if (! $draggable) data-draggable="false" @endif
    @if (! $snap) data-snap="false" @endif
    @if ($autoHeight) data-auto-height @endif
    @if ($zoom) data-zoom data-label-zoom-in="{{ __('Zoom in') }}" data-label-zoom-out="{{ __('Zoom out') }}" @endif
    @if ($thumbsMode) data-thumbs="{{ $thumbsMode }}" @endif
    @if ($fullscreen) data-fullscreen="{{ $carouselId }}-fullscreen" @endif
    @if ($set !== null) data-set="{{ $set }}" @endif
    data-slide-label="{{ __(':number of :count') }}"
    data-slide-role="{{ __('slide') }}"
    role="region"
    aria-roledescription="{{ __('carousel') }}"
    aria-label="{{ $label ?? __('Images') }}"
    @if ($style) style="{{ $style }}" @endif
    {{ $attributes->class([
        'group/carousel relative',
        $gapClass,
        'overflow-hidden rounded-sm bg-inverse' => $framed,
        'grid grid-cols-[4.5rem_minmax(0,1fr)] gap-3 sm:grid-cols-[5.5rem_minmax(0,1fr)]' => $thumbsMode === 'vertical',
        '[--carousel-per-view:var(--carousel-per-view-base)] sm:[--carousel-per-view:var(--carousel-per-view-sm)] md:[--carousel-per-view:var(--carousel-per-view-md)] lg:[--carousel-per-view:var(--carousel-per-view-lg)] xl:[--carousel-per-view:var(--carousel-per-view-xl)]' => $strip,
    ]) }}
>
    <div data-slot="carousel-viewport" @class([
        'relative min-w-0 overflow-hidden group-data-ready/carousel:touch-pan-y',
        'rounded-sm' => $thumbsMode,
        'col-start-2 row-start-1' => $thumbsMode === 'vertical',
    ])>
        {{-- Announced when the user changes the slide, not on every automatic turn. --}}
        <div data-slot="carousel-track" aria-live="{{ $interval ? 'off' : 'polite' }}" @class([
            'acun-carousel-track flex gap-(--carousel-gap) overflow-x-auto [scrollbar-width:none] ease-out motion-reduce:transition-none group-data-ready/carousel:gap-0 group-data-ready/carousel:overflow-visible',
            '[&>*]:w-[calc((100%_-_(var(--carousel-per-view,1)_-_1)_*_var(--carousel-gap))_/_var(--carousel-per-view,1))] [&>*]:flex-none',
            'snap-x snap-mandatory group-data-ready/carousel:snap-none' => $snap,
            '[&>*]:snap-start' => $snap && ! $centered,
            '[&>*]:snap-center' => $snap && $centered,
            'items-start transition-[transform,height]' => $autoHeight,
            'transition-transform' => ! $autoHeight,
        ])>
            {{ $slot }}
        </div>

        @if ($arrows)
            <button type="button" data-slot="carousel-prev" aria-label="{{ __('Previous') }}" @if ($framed) data-theme="light" @endif @class([$button, 'top-1/2 -translate-y-1/2', 'left-3' => $framed, 'left-2' => ! $framed])>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" data-slot="carousel-next" aria-label="{{ __('Next') }}" @if ($framed) data-theme="light" @endif @class([$button, 'top-1/2 -translate-y-1/2', 'right-3' => $framed, 'right-2' => ! $framed])>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        @endif

        @if ($interval)
            {{-- The name says what a press does (Pause / Play), so the button has no pressed state. --}}
            <button type="button" data-slot="carousel-pause" aria-label="{{ __('Pause') }}" data-label-pause="{{ __('Pause') }}" data-label-play="{{ __('Play') }}" @if ($framed) data-theme="light" @endif @class([$button, 'top-3 size-8!', 'right-3' => ! $fullscreen, 'right-14' => $fullscreen])>
                <svg class="size-3.5 group-data-paused/carousel:hidden" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>
                <svg class="hidden size-3.5 group-data-paused/carousel:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
            </button>
        @endif

        @if ($zoom)
            <button type="button" data-slot="carousel-zoom" aria-label="{{ __('Zoom in') }}" @if ($framed) data-theme="light" @endif class="absolute right-3 bottom-3 z-10 hidden size-9 cursor-pointer items-center justify-center rounded-full bg-surface/90 text-ink shadow group-data-ready/carousel:flex hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                <svg class="size-5 group-data-zoomed/carousel:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/></svg>
                <svg class="hidden size-5 group-data-zoomed/carousel:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM13.5 10.5h-6"/></svg>
            </button>
        @endif

        @if ($fullscreen)
            <button type="button" data-slot="carousel-fullscreen" aria-haspopup="dialog" aria-label="{{ __('Full screen') }}" @if ($framed) data-theme="light" @endif class="absolute top-3 right-3 z-10 hidden size-9 cursor-pointer items-center justify-center rounded-full bg-surface/90 text-ink shadow group-data-ready/carousel:flex hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
            </button>
        @endif

        @if ($counter)
            {{-- Screen readers hear the slide's own "3 of 8" name; the counter is for the eyes. --}}
            <p data-slot="carousel-counter" aria-hidden="true" @if ($framed) data-theme="light" @endif class="pointer-events-none absolute top-3 left-3 z-10 hidden rounded-full bg-surface/80 px-2.5 py-0.5 text-xs font-medium text-ink tabular-nums shadow group-data-controls/carousel:block"></p>
        @endif

        @if ($pagination && ! $strip)
            <div data-slot="carousel-dots" wire:ignore @if ($framed) data-theme="light" @endif class="absolute inset-x-0 bottom-3 z-10 hidden justify-center gap-1.5 group-data-controls/carousel:flex">
                <template data-slot="carousel-dot"><button type="button" aria-label="{{ __('Slide :number') }}" aria-current="false" class="h-1.5 w-1.5 cursor-pointer rounded-full bg-surface/60 shadow transition-all motion-reduce:transition-none aria-[current=true]:w-5 aria-[current=true]:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-surface"></button></template>
            </div>
        @endif
    </div>

    @if ($pagination && $strip)
        <div data-slot="carousel-dots" wire:ignore class="mt-4 hidden justify-center gap-1.5 group-data-controls/carousel:flex">
            <template data-slot="carousel-dot"><button type="button" aria-label="{{ __('Slide :number') }}" aria-current="false" class="h-1.5 w-1.5 cursor-pointer rounded-full bg-line-strong transition-all motion-reduce:transition-none aria-[current=true]:w-5 aria-[current=true]:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"></button></template>
        </div>
    @endif

    @if ($thumbsMode)
        <div data-slot="carousel-thumbs" role="group" aria-label="{{ __('Thumbnails') }}" @class([
            '[--carousel-thumb-gap:--spacing(3)]',
            '-m-1 mt-2 overflow-x-auto p-1 [scrollbar-width:none] group-data-ready/carousel:overflow-hidden' => $thumbsMode === 'horizontal',
            'relative col-start-1 row-start-1 min-h-0 overflow-hidden p-1' => $thumbsMode === 'vertical',
        ])>
            <div @class([
                'acun-carousel-thumbs flex gap-(--carousel-thumb-gap) group-data-ready/carousel:gap-0 [&>*]:flex-none',
                '[&>*]:w-[calc((100%_-_3*var(--carousel-thumb-gap))/4)]' => $thumbsMode === 'horizontal',
                'absolute inset-1 flex-col overflow-y-auto [scrollbar-width:none] group-data-ready/carousel:overflow-visible [&>*]:w-full' => $thumbsMode === 'vertical',
            ])>
                {{ $thumbnails }}
            </div>
        </div>
    @endif
    @if ($hasSets)
        {{-- Says which set is shown (a product's colour), politely. --}}
        <p data-slot="carousel-status" role="status" wire:ignore class="sr-only"></p>
    @endif

    @if ($fullscreen)
        {{--
            The full-screen view: the modal plugin's dialog (focus kept inside, Escape, the page's scroll locked, the
            focus back to what opened it). Its carousel is empty here: the plugin copies the slides (and thumbnails)
            in when it opens, so it shows what the page shows (another set too).
        --}}
        <div data-acun-modal="{{ $carouselId }}-fullscreen" data-slot="carousel-lightbox" data-theme="dark" hidden wire:ignore class="pointer-events-none fixed inset-0 z-50 flex data-open:pointer-events-auto">
            <div data-slot="modal-backdrop" class="fixed inset-0 bg-page opacity-0 transition-opacity duration-150 data-open:opacity-100 motion-reduce:transition-none" aria-hidden="true"></div>
            <div data-slot="modal-panel" role="dialog" aria-modal="true" aria-label="{{ $label ?? __('Images') }}" tabindex="-1" class="relative z-10 flex h-dvh w-full flex-col gap-3 p-3 opacity-0 transition-opacity duration-150 data-open:opacity-100 sm:p-6 motion-reduce:transition-none">
                <div class="flex flex-none justify-end">
                    <button type="button" data-acun-close aria-label="{{ __('Close') }}" class="flex size-10 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <acun:carousel :label="$label ?? __('Images')" zoom counter :pagination="false" :loop="$loop" :thumbs="$thumbsMode ? 'horizontal' : null" data-carousel-lightbox class="flex min-h-0 flex-1 flex-col bg-transparent! [&>[data-slot=carousel-viewport]]:min-h-0 [&>[data-slot=carousel-viewport]]:flex-1 [&_[data-slot=carousel-track]]:h-full [&_[data-slot=carousel-track]>*]:h-full [&_.swiper-zoom-target]:h-full [&_.swiper-zoom-target]:w-auto [&_.swiper-zoom-target]:max-w-full [&_img]:object-contain [&>[data-slot=carousel-thumbs]]:mx-auto [&>[data-slot=carousel-thumbs]]:w-full [&>[data-slot=carousel-thumbs]]:max-w-md max-md:[&>[data-slot=carousel-thumbs]]:hidden">
                    @if ($thumbsMode)
                        <acun:slot:thumbnails><!-- the thumbnails are copied in when it opens --></acun:slot:thumbnails>
                    @endif
                </acun:carousel>
            </div>
        </div>
    @endif
</div>
