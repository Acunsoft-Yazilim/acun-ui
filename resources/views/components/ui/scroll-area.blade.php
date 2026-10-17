@props([
    // Scroll axis: vertical · horizontal · both
    'direction' => 'vertical',
    // CSS length ('16rem', '50vh') or a number of pixels. An h-* / max-h-* class works too.
    'maxHeight' => null,
    'height' => null,
    // Fade the edges while more content is hidden in that direction (a tiny Alpine scroll listener).
    'shadows' => false,
    // Accessible name; the area becomes a labelled region.
    'label' => null,
])

{{--
    Usage:
    <acun:scroll-area max-height="16rem" label="Son hareketler">…</acun:scroll-area>
    <acun:scroll-area direction="horizontal"><table class="w-max">…</table></acun:scroll-area>
    <acun:scroll-area class="max-h-80" shadows>…</acun:scroll-area>

    The scrollbar is thin and themed with the standard scrollbar-width / scrollbar-color
    properties; ::-webkit-scrollbar covers browsers that do not support them (Safari).
    tabindex="0" lets keyboard users focus the area and scroll it with the arrow keys.
--}}
@php
    $overflow = [
        'vertical' => 'overflow-x-hidden overflow-y-auto',
        'horizontal' => 'overflow-x-auto overflow-y-hidden',
        'both' => 'overflow-auto',
    ][$direction] ?? 'overflow-x-hidden overflow-y-auto';

    $style = collect(['height' => $height, 'max-height' => $maxHeight])
        ->reject(fn ($value) => $value === null || $value === '')
        ->map(fn ($value, $property) => $property.': '.(is_numeric($value) ? $value.'px' : $value))
        ->implode('; ');

    $defaults = array_filter([
        'tabindex' => '0',
        'style' => $style,
        'class' => $overflow.' [scrollbar-width:thin] [scrollbar-color:var(--ac-line-strong)_transparent] hover:[scrollbar-color:var(--ac-line-hover)_transparent] [&::-webkit-scrollbar]:size-2 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-corner]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-line-strong hover:[&::-webkit-scrollbar-thumb]:bg-line-hover focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus',
    ], fn ($value) => $value !== '');
@endphp

<div
    @if ($label) role="region" aria-label="{{ $label }}" @elseif ($attributes->has('aria-labelledby')) role="region" @endif
    @if ($shadows)
        {{--
            The fade is a mask, so it works on any background. Each edge fades in over
            the first 24px of scrolling instead of switching on at once.
        --}}
        x-data="{
            vertical: @js($direction !== 'horizontal'),
            horizontal: @js($direction !== 'vertical'),
            rtl: false,
            fade: { top: 0, bottom: 0, start: 0, end: 0 },
            resizes: null,
            mutations: null,
            init() {
                this.rtl = getComputedStyle(this.$el).direction === 'rtl';
                {{-- Re-measure when the area or its content changes size, or content is added (e.g. a Livewire update). --}}
                const watch = () => [...this.$el.children].forEach((child) => this.resizes.observe(child));
                this.resizes = new ResizeObserver(() => this.measure());
                this.resizes.observe(this.$el);
                watch();
                this.mutations = new MutationObserver(() => { watch(); this.measure(); });
                this.mutations.observe(this.$el, { childList: true });
                this.measure();
            },
            destroy() {
                this.resizes?.disconnect();
                this.mutations?.disconnect();
            },
            measure() {
                const el = this.$el;
                const x = Math.abs(el.scrollLeft);
                const ramp = (distance) => Math.round(Math.min(Math.max(distance, 0) / 24, 1) * 100) / 100;
                this.fade.top = ramp(el.scrollTop);
                this.fade.bottom = ramp(el.scrollHeight - el.clientHeight - el.scrollTop);
                this.fade.start = ramp(x);
                this.fade.end = ramp(el.scrollWidth - el.clientWidth - x);
            },
            get mask() {
                const edge = (fade) => `rgb(0 0 0 / ${1 - fade})`;
                const layers = [];
                if (this.vertical && (this.fade.top || this.fade.bottom)) {
                    layers.push(`linear-gradient(to bottom, ${edge(this.fade.top)}, #000 1.5rem, #000 calc(100% - 1.5rem), ${edge(this.fade.bottom)})`);
                }
                if (this.horizontal && (this.fade.start || this.fade.end)) {
                    layers.push(`linear-gradient(to ${this.rtl ? 'left' : 'right'}, ${edge(this.fade.start)}, #000 1.5rem, #000 calc(100% - 1.5rem), ${edge(this.fade.end)})`);
                }
                return layers.length ? layers.join(', ') : 'none';
            },
        }"
        x-on:scroll.passive="measure()"
        x-bind:style="{ maskImage: mask, maskComposite: 'intersect' }"
    @endif
    {{ $attributes->merge($defaults) }}
>
    {{ $slot }}
</div>
