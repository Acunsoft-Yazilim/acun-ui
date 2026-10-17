@props([
    // Auto-advance interval (ms); off when null.
    'interval' => null,
    // Accessible name of the region; default "Images" in the current language.
    'label' => null,
])

{{--
    Each slide is a direct child element:
        <acun:carousel :interval="5000">
            <img src="…" alt="…">
            <div>…</div>
        </acun:carousel>
    acun:carousel.item is a slide that also names itself for screen readers:
        <acun:carousel.item label="Depo girişi"><img src="…" alt="…"></acun:carousel.item>
    With interval it stops under the pointer and the keyboard focus, and has a pause
    button (WCAG 2.2.2). Slides added or removed later (a Livewire update) are counted.
--}}
<div
    x-data="{
        index: 0,
        count: 0,
        timer: null,
        paused: false,
        init() {
            this.count = this.$refs.track.children.length;
            // Kept out of the reactive state: a proxied observer cannot be called.
            const slides = new MutationObserver(() => {
                this.count = this.$refs.track.children.length;
                this.index = Math.min(this.index, Math.max(0, this.count - 1));
            });
            slides.observe(this.$refs.track, { childList: true });
            this.unwatch = () => slides.disconnect();
            this.start();
        },
        go(i) { this.index = this.count ? (i + this.count) % this.count : 0 },
        // On load and when the pointer / focus leaves; never while paused or when the OS asks for reduced motion.
        start() {
            @if ($interval)
                this.stop();
                if (this.paused || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                this.timer = setInterval(() => this.go(this.index + 1), {{ (int) $interval }});
            @endif
        },
        stop() { clearInterval(this.timer); this.timer = null },
        togglePause() { this.paused = ! this.paused; this.paused ? this.stop() : this.start() },
        destroy() { this.stop(); this.unwatch?.() },
    }"
    x-on:mouseenter="stop()"
    x-on:mouseleave="if (! $el.contains(document.activeElement)) start()"
    x-on:focusin="stop()"
    x-on:focusout="if (! $el.contains($event.relatedTarget)) start()"
    x-on:keydown.arrow-left="go(index - 1)"
    x-on:keydown.arrow-right="go(index + 1)"
    role="region"
    aria-roledescription="carousel"
    aria-label="{{ $label ?? __('Images') }}"
    {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-sm bg-inverse']) }}
>
    {{-- Announced when the user changes the slide, not on every automatic turn. --}}
    <div x-ref="track" :aria-live="timer ? 'off' : 'polite'" class="flex transition-transform duration-500 ease-out motion-reduce:transition-none [&>*]:w-full [&>*]:flex-none" :style="`transform: translateX(-${index * 100}%)`">
        {{ $slot }}
    </div>

    <button type="button" x-on:click="go(index - 1)" aria-label="{{ __('Previous') }}" data-theme="light" class="absolute top-1/2 left-3 flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink shadow hover:bg-surface focus-visible:outline-2 focus-visible:outline-surface">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button type="button" x-on:click="go(index + 1)" aria-label="{{ __('Next') }}" data-theme="light" class="absolute top-1/2 right-3 flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink shadow hover:bg-surface focus-visible:outline-2 focus-visible:outline-surface">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    @if ($interval)
        <button type="button" x-on:click="togglePause()" :aria-label="paused ? @js(__('Play')) : @js(__('Pause'))" :aria-pressed="paused.toString()" data-theme="light" class="absolute top-3 right-3 flex size-8 cursor-pointer items-center justify-center rounded-full bg-surface/80 text-ink shadow hover:bg-surface focus-visible:outline-2 focus-visible:outline-surface">
            <svg x-show="! paused" class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>
            <svg x-show="paused" x-cloak class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
        </button>
    @endif

    <div data-theme="light" class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5">
        <template x-for="i in count" :key="i">
            <button type="button" x-on:click="go(i - 1)" :aria-label="@js(__('Slide :number')).replace(':number', i)" :aria-current="(index === i - 1).toString()" class="h-1.5 cursor-pointer rounded-full bg-surface/60 transition-all aria-[current=true]:w-5 aria-[current=true]:bg-surface" :class="index === i - 1 ? 'w-5' : 'w-1.5'"></button>
        </template>
    </div>
</div>
