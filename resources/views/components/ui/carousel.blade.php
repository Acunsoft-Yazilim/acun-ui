@props([
    // Auto-advance interval (ms); off when null. Stops under the pointer and the focus, has a pause button.
    'interval' => null,
    // Accessible name of the region; default "Images" in the current language.
    'label' => null,
    // Slides in view: a number (1–6), or one per breakpoint: ['base' => 2, 'sm' => 3, 'lg' => 4] (sm, md, lg, xl, 2xl;
    // a breakpoint left out takes the one before it). More than one makes a strip (product rows).
    'perView' => 1,
    // What the per-view breakpoints measure (Swiper's breakpointsBase): 'screen' (the viewport: sm 40rem … 2xl 96rem),
    // or 'container': the carousel's own width at the same sizes (a container query), so a strip in a wider page
    // container or beside a sidebar holds as many slides as its width does.
    'perViewBase' => 'screen',
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
    // The previous / next buttons: true or 'sides' (on both sides of the slides, in the middle), 'sm' (on both sides from
    // 640px only: on a phone they would cover a slide's heading; a swipe moves it), 'top-end' (side by
    // side above the slides, at the end), 'bottom-center' (under the slides, around a strip's dots) or false (none;
    // acun:carousel.controls puts them elsewhere, e.g. in a section heading). Then the dots (one per position) and
    // the "3 / 8" counter.
    'arrows' => true,
    'pagination' => true,
    'counter' => false,
    // Where the thumbnails go (<acun:slot:thumbnails> with acun:carousel.thumb): 'horizontal' (default) under the
    // slides, 'vertical' beside them, 'vertical-wide' beside them from 1024px and under them below (a product gallery:
    // a side strip would squeeze the pictures on a phone).
    'thumbs' => null,
    // The width of each horizontal thumbnail (a CSS length: '4rem', '64px'): as many as fit stand in a row, the rest
    // slide. null: four to a row, as wide as the strip allows.
    'thumbWidth' => null,
    // The slides' pictures zoom: a double tap or a pinch, a double click or the zoom button (slides written with
    // acun:carousel.item).
    'zoom' => false,
    // A magnifier on a wide screen with a mouse: over a slide's photo a lens follows the pointer and a pane beside the
    // carousel (to the right, over what is there) shows that part of the photo larger. A product gallery's.
    'hoverZoom' => false,
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
    The same, counted by the strip's own width (it follows the page container: more slides in a wider one):
        <acun:carousel per-view-base="container" :per-view="['base' => 2, 'md' => 3, 'lg' => 4, 'xl' => 5, '2xl' => 6]" :loop="false">…</acun:carousel>
    A strip whose arrows sit in its section heading (acun:carousel.controls, linked by the carousel's id):
        <div class="flex items-end justify-between"><h2>Best sellers</h2> <acun:carousel.controls for="best-sellers" /></div>
        <acun:carousel id="best-sellers" :arrows="false" :per-view="['base' => 2, 'lg' => 4]" :loop="false">…</acun:carousel>
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
    $breakpoints = ['base', 'sm', 'md', 'lg', 'xl', '2xl'];
    $given = is_array($perView) ? $perView : ['base' => $perView];
    $views = [];
    $previous = 1;
    foreach ($breakpoints as $breakpoint) {
        $value = $given[$breakpoint] ?? ($breakpoint === 'base' ? ($given[0] ?? null) : null);
        $previous = $value === null ? $previous : max(1, min(6, (int) $value));
        $views[$breakpoint] = $previous;
    }
    $strip = max($views) > 1;
    $byContainer = $strip && $perViewBase === 'container';
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
    if ($thumbWidth !== null && ! preg_match('/^\d+(\.\d+)?(px|rem|em)$/', (string) $thumbWidth)) {
        throw new \InvalidArgumentException('acun:carousel thumb-width "'.$thumbWidth.'" is not a length such as 4rem or 64px.');
    }
    $thumbsMode = isset($thumbnails) && trim((string) $thumbnails) !== '' ? (in_array($thumbs, ['vertical', 'vertical-wide'], true) ? $thumbs : 'horizontal') : null;
    $wideThumbs = $thumbsMode === 'vertical-wide';
    // The side strip's column is as wide as a thumbnail (thumb-width, else 4.5rem) and its padding.
    if ($wideThumbs && $thumbWidth !== null) {
        // Merged with the page's own style (one style attribute on the root).
        $attributes = $attributes->merge(['style' => '--carousel-thumb-width: '.$thumbWidth]);
    }
    // A slider on its own sits on a dark frame; a strip and a gallery with thumbnails sit on the page.
    $framed = ! $strip && ! $thumbsMode;
    $overlay = 'absolute z-10 hidden size-9 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink shadow group-data-controls/carousel:flex hover:bg-surface focus-visible:outline-2 focus-visible:outline-surface aria-disabled:cursor-default aria-disabled:opacity-40';
    $edge = 'absolute z-10 hidden size-10 cursor-pointer items-center justify-center rounded-full bg-surface text-ink shadow sm:group-data-controls/carousel:flex hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus aria-disabled:cursor-default aria-disabled:opacity-0';
    // arrows="sm": the framed slides' arrows from 640px only (the strip's edge buttons are from sm already).
    $overlaySm = 'absolute z-10 hidden size-9 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink shadow sm:group-data-controls/carousel:flex hover:bg-surface focus-visible:outline-2 focus-visible:outline-surface aria-disabled:cursor-default aria-disabled:opacity-40';
    $button = $framed ? ($arrows === 'sm' ? $overlaySm : $overlay) : $edge;
    $arrowsAt = match (true) {
        $arrows === false || $arrows === null || $arrows === 'false' => null,
        in_array($arrows, ['top-end', 'bottom-center'], true) => $arrows,
        default => 'sides',
    };
    // Arrows outside the slides: a slider's dark frame goes round the slides alone, the buttons sit on the page.
    $outside = 'flex size-9 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink shadow-xs hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus aria-disabled:cursor-default aria-disabled:opacity-40 aria-disabled:hover:bg-surface';
    $frameSlides = $framed && in_array($arrowsAt, ['top-end', 'bottom-center'], true);
    // The carousel's id (an id attribute, else one made from its content): the viewport's id (the arrows' aria-controls)
    // and the full-screen dialog's name (the modal plugin's).
    $carouselId = \Acun\Ui\Support\Field::id('carousel', $attributes, null, $label, $thumbsMode);
    $viewportId = $carouselId.'-viewport';
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
    @if ($hoverZoom) data-hover-zoom @endif
    data-slide-label="{{ __(':number of :count') }}"
    data-slide-role="{{ __('slide') }}"
    role="region"
    aria-roledescription="{{ __('carousel') }}"
    aria-label="{{ $label ?? __('Images') }}"
    @if ($style) style="{{ $style }}" @endif
    {{ $attributes->class([
        'group/carousel relative',
        $gapClass,
        // With the magnifier the frame is the viewport's: its pane opens beside the root, which must not cut it.
        'overflow-hidden rounded-sm bg-inverse' => $framed && ! $frameSlides && ! $hoverZoom,
        'grid grid-cols-[4.5rem_minmax(0,1fr)] gap-3 sm:grid-cols-[5.5rem_minmax(0,1fr)]' => $thumbsMode === 'vertical',
        'lg:grid lg:grid-cols-[calc(var(--carousel-thumb-width,4.5rem)_+_0.5rem)_minmax(0,1fr)] lg:gap-3' => $wideThumbs,
        '[--carousel-per-view:var(--carousel-per-view-base)] sm:[--carousel-per-view:var(--carousel-per-view-sm)] md:[--carousel-per-view:var(--carousel-per-view-md)] lg:[--carousel-per-view:var(--carousel-per-view-lg)] xl:[--carousel-per-view:var(--carousel-per-view-xl)] 2xl:[--carousel-per-view:var(--carousel-per-view-2xl)]' => $strip && ! $byContainer,
        // By the carousel's own width: the root is the query container, the track (inside it) takes the count.
        '@container/carousel' => $byContainer,
    ]) }}
>
    @if ($arrowsAt === 'top-end')
        <div @class(['mb-3 hidden justify-end gap-2 group-data-controls/carousel:flex', 'col-span-2' => $thumbsMode === 'vertical', 'lg:col-span-2' => $wideThumbs])>
            <button type="button" data-slot="carousel-prev" aria-label="{{ __('Previous') }}" aria-controls="{{ $viewportId }}" class="{{ $outside }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" data-slot="carousel-next" aria-label="{{ __('Next') }}" aria-controls="{{ $viewportId }}" class="{{ $outside }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    @endif

    <div data-slot="carousel-viewport" id="{{ $viewportId }}" @class([
        'relative min-w-0 overflow-hidden group-data-ready/carousel:touch-pan-y',
        'rounded-sm bg-inverse' => $frameSlides || ($framed && $hoverZoom),
        'rounded-sm' => $thumbsMode,
        'col-start-2 row-start-1' => $thumbsMode === 'vertical',
        'lg:col-start-2 lg:row-start-1' => $wideThumbs,
    ])>
        @if ($hoverZoom)
            {{-- The magnifier's lens: where the pointer is on the photo (the runtime places it). --}}
            <div data-slot="carousel-lens" hidden aria-hidden="true" class="pointer-events-none absolute top-0 left-0 z-10 rounded-sm border border-ink/25 bg-surface/35 shadow-sm"></div>
        @endif
        {{-- Announced when the user changes the slide, not on every automatic turn. --}}
        <div data-slot="carousel-track" aria-live="{{ $interval ? 'off' : 'polite' }}" @class([
            'acun-carousel-track flex gap-(--carousel-gap) overflow-x-auto [scrollbar-width:none] ease-out motion-reduce:transition-none group-data-ready/carousel:gap-0 group-data-ready/carousel:overflow-visible',
            '[&>*]:w-[calc((100%_-_(var(--carousel-per-view,1)_-_1)_*_var(--carousel-gap))_/_var(--carousel-per-view,1))] [&>*]:flex-none',
            '[--carousel-per-view:var(--carousel-per-view-base)] @min-[40rem]/carousel:[--carousel-per-view:var(--carousel-per-view-sm)] @min-[48rem]/carousel:[--carousel-per-view:var(--carousel-per-view-md)] @min-[64rem]/carousel:[--carousel-per-view:var(--carousel-per-view-lg)] @min-[80rem]/carousel:[--carousel-per-view:var(--carousel-per-view-xl)] @min-[96rem]/carousel:[--carousel-per-view:var(--carousel-per-view-2xl)]' => $byContainer,
            'snap-x snap-mandatory group-data-ready/carousel:snap-none' => $snap,
            '[&>*]:snap-start' => $snap && ! $centered,
            '[&>*]:snap-center' => $snap && $centered,
            'items-start transition-[transform,height]' => $autoHeight,
            'transition-transform' => ! $autoHeight,
        ])>
            {{ $slot }}
        </div>

        @if ($arrowsAt === 'sides')
            <button type="button" data-slot="carousel-prev" aria-label="{{ __('Previous') }}" aria-controls="{{ $viewportId }}" @if ($framed) data-theme="light" @endif @class([$button, 'top-1/2 -translate-y-1/2', 'left-3' => $framed, 'left-2' => ! $framed])>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" data-slot="carousel-next" aria-label="{{ __('Next') }}" aria-controls="{{ $viewportId }}" @if ($framed) data-theme="light" @endif @class([$button, 'top-1/2 -translate-y-1/2', 'right-3' => $framed, 'right-2' => ! $framed])>
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

    @if ($arrowsAt === 'bottom-center')
        {{-- The arrows under the slides, a strip's dots between them. --}}
        <div @class(['mt-4 hidden items-center justify-center gap-3 group-data-controls/carousel:flex', 'col-span-2' => $thumbsMode === 'vertical', 'lg:col-span-2' => $wideThumbs])>
            <button type="button" data-slot="carousel-prev" aria-label="{{ __('Previous') }}" aria-controls="{{ $viewportId }}" class="{{ $outside }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            @if ($pagination && $strip)
                <div data-slot="carousel-dots" wire:ignore class="flex justify-center gap-1.5">
                    <template data-slot="carousel-dot"><button type="button" aria-label="{{ __('Slide :number') }}" aria-current="false" class="h-1.5 w-1.5 cursor-pointer rounded-full bg-line-strong transition-all motion-reduce:transition-none aria-[current=true]:w-5 aria-[current=true]:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"></button></template>
                </div>
            @endif
            <button type="button" data-slot="carousel-next" aria-label="{{ __('Next') }}" aria-controls="{{ $viewportId }}" class="{{ $outside }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    @elseif ($pagination && $strip)
        <div data-slot="carousel-dots" wire:ignore class="mt-4 hidden justify-center gap-1.5 group-data-controls/carousel:flex">
            <template data-slot="carousel-dot"><button type="button" aria-label="{{ __('Slide :number') }}" aria-current="false" class="h-1.5 w-1.5 cursor-pointer rounded-full bg-line-strong transition-all motion-reduce:transition-none aria-[current=true]:w-5 aria-[current=true]:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"></button></template>
        </div>
    @endif

    @if ($hoverZoom)
        {{-- The magnifier's pane, beside the carousel and as big as its pictures: the lens's part, larger. --}}
        <div data-slot="carousel-magnifier" hidden aria-hidden="true" class="pointer-events-none absolute top-0 left-[calc(100%+1.5rem)] z-30 overflow-hidden rounded-md border border-line bg-surface bg-no-repeat shadow-2xl"></div>
    @endif
    @if ($thumbsMode)
        <div data-slot="carousel-thumbs" role="group" aria-label="{{ __('Thumbnails') }}" @if ($thumbWidth !== null) style="--carousel-thumb-width: {{ $thumbWidth }}" @endif @class([
            '[--carousel-thumb-gap:--spacing(3)]',
            '-m-1 mt-2 overflow-x-auto p-1 [scrollbar-width:none] group-data-ready/carousel:overflow-hidden' => $thumbsMode === 'horizontal',
            'relative col-start-1 row-start-1 min-h-0 overflow-hidden p-1' => $thumbsMode === 'vertical',
            '-m-1 mt-2 overflow-x-auto p-1 [scrollbar-width:none] group-data-ready/carousel:overflow-hidden lg:relative lg:col-start-1 lg:row-start-1 lg:m-0 lg:min-h-0 lg:overflow-hidden' => $wideThumbs,
        ])>
            <div @class([
                'acun-carousel-thumbs flex gap-(--carousel-thumb-gap) group-data-ready/carousel:gap-0 [&>*]:flex-none',
                '[&>*]:w-[calc((100%_-_3*var(--carousel-thumb-gap))/4)]' => $thumbsMode === 'horizontal' && $thumbWidth === null,
                '[&>*]:w-(--carousel-thumb-width)' => in_array($thumbsMode, ['horizontal', 'vertical-wide'], true) && $thumbWidth !== null,
                'max-lg:[&>*]:w-[calc((100%_-_3*var(--carousel-thumb-gap))/4)] lg:[&>*]:w-full' => $wideThumbs && $thumbWidth === null,
                'lg:absolute lg:inset-1 lg:flex-col lg:overflow-y-auto lg:[scrollbar-width:none] lg:group-data-ready/carousel:overflow-visible' => $wideThumbs,
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
            A photo is shown whole there (contain): its frame's background picture (a tile behind a cropped photo on the
            page) would show round it, so it goes.
        --}}
        <div data-acun-modal="{{ $carouselId }}-fullscreen" data-slot="carousel-lightbox" data-theme="dark" hidden wire:ignore class="pointer-events-none fixed inset-0 z-50 flex data-open:pointer-events-auto">
            <div data-slot="modal-backdrop" class="fixed inset-0 bg-page/95 opacity-0 backdrop-blur-md transition-opacity duration-200 data-open:opacity-100 motion-reduce:transition-none" aria-hidden="true"></div>
            {{-- The dialog itself takes the focus when it opens (no ring on a button the user did not reach with Tab);
                 Tab reaches the close button, the arrows and the thumbnails, Left / Right move the pictures. --}}
            <div data-slot="modal-panel" role="dialog" aria-modal="true" aria-label="{{ $label ?? __('Images') }}" tabindex="-1" autofocus class="relative z-10 flex h-dvh w-full flex-col opacity-0 outline-none transition-opacity duration-200 data-open:opacity-100 motion-reduce:transition-none">
                <button type="button" data-acun-close aria-label="{{ __('Close') }}" class="absolute top-3 right-3 z-20 flex size-10 cursor-pointer items-center justify-center rounded-full bg-ink/10 text-ink ring-1 ring-ink/15 backdrop-blur transition-colors hover:bg-ink/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus sm:top-5 sm:right-5 motion-reduce:transition-none">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <acun:carousel :label="$label ?? __('Images')" zoom counter :pagination="false" :loop="$loop" :thumbs="$thumbsMode ? 'horizontal' : null" thumb-width="3.5rem" data-carousel-lightbox class="flex min-h-0 flex-1 flex-col bg-transparent! px-3 pt-16 pb-4 sm:px-20 sm:pt-8 sm:pb-6 [&>[data-slot=carousel-viewport]]:min-h-0 [&>[data-slot=carousel-viewport]]:flex-1 [&_[data-slot=carousel-track]]:h-full [&_[data-slot=carousel-track]>*]:h-full [&_.swiper-zoom-target]:h-full [&_.swiper-zoom-target]:w-auto [&_.swiper-zoom-target]:max-w-full [&_.swiper-zoom-target]:rounded-sm [&_.swiper-zoom-target:has(>img)]:bg-none! [&_img]:object-contain [&>[data-slot=carousel-thumbs]]:mx-auto [&>[data-slot=carousel-thumbs]]:mt-4 [&>[data-slot=carousel-thumbs]]:w-fit [&>[data-slot=carousel-thumbs]]:max-w-full max-md:[&>[data-slot=carousel-thumbs]]:hidden">
                    @if ($thumbsMode)
                        <acun:slot:thumbnails><!-- the thumbnails are copied in when it opens --></acun:slot:thumbnails>
                    @endif
                </acun:carousel>
            </div>
        </div>
    @endif
</div>
