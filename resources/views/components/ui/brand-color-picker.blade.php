@props([
    // Preset colours: key => [hex, name]; the key is the value $store.brand keeps.
    // null: the palette's presets (Acun\Ui\Support\Appearance::presets()).
    'presets' => null,
    // Whether to show the free colour picker.
    'custom' => true,
    'label' => null,
])

{{--
    Primary colour picker: preset colours + the user's own colour (#rrggbb).
    The choice is applied instantly to the whole UI ($store.brand), stored in the
    browser and, when acun:layout.* has a preference-url, saved to the account as { brand }.
    Server-side validation example (\z: $ would also take a trailing newline):
    ['brand' => ['regex:/^(#[0-9a-fA-F]{6}|default|green|red|yellow|blue|gray|dark)\z/']]
--}}
@php
    // Only #rrggbb values go into the page (a tenant's or a user's presets included); others are skipped.
    // \z, not $: $ also matches before a trailing newline ("#aabbcc\n").
    $presets = collect($presets ?? \Acun\Ui\Support\Appearance::presets())
        ->filter(fn ($preset) => is_array($preset) && is_string($preset[0] ?? null) && preg_match('/^#[0-9a-f]{6}\z/i', $preset[0]))
        ->map(fn (array $preset) => [strtolower($preset[0]), is_scalar($preset[1] ?? null) ? (string) $preset[1] : $preset[0]])
        ->all();
    // The tick is a graphic: white while it stands out 3:1 on the swatch (WCAG non-text contrast),
    // otherwise gray-900. The contrast is measured as in js/appearance/theme.js, whose button text
    // of a custom colour is text and needs 4.5:1 instead.
    $whiteTick = function (string $hex): bool {
        [$r, $g, $b] = array_map(function (string $channel) {
            $c = hexdec($channel) / 255;

            return $c <= 0.03928 ? $c / 12.92 : ((($c + 0.055) / 1.055) ** 2.4);
        }, str_split(substr($hex, 1), 2));

        return 1.05 / (0.2126 * $r + 0.7152 * $g + 0.0722 * $b + 0.05) >= 3;
    };
@endphp
<div
    x-data="{
        presets: @js(collect($presets)->map(fn ($p) => $p[0])),
        // The page's own primary colour: what 'default' shows (the theme's --ac-tone-primary, not the kit's).
        themeColor: null,
        get selected() { return $store.brand.color || 'default' },
        get isCustom() { return /^#[0-9a-f]{6}$/i.test(this.selected) },
        get themeHex() { return /^#[0-9a-f]{6}$/i.test(this.themeColor ?? '') ? this.themeColor.toLowerCase() : null },
        get customColor() {
            if (this.isCustom) return this.selected;
            if (this.selected === 'default' && this.themeHex) return this.themeHex;
            return this.presets[this.selected] ?? this.themeHex ?? '#000000';
        },
        init() {
            // The theme's own colour: the user's colour (--ac-user-*) never changes the theme's input.
            this.themeColor = getComputedStyle(document.documentElement).getPropertyValue('--ac-tone-primary').trim() || null;
        },
        select(value) { $store.brand.set(value) },
    }"
    {{ $attributes->merge(['class' => 'space-y-3']) }}
>
    @if ($label)
        <p class="text-sm font-medium text-ink">{{ $label }}</p>
    @endif

    <div class="flex flex-wrap items-center gap-2" role="radiogroup" aria-label="{{ $label ?? __('Primary color') }}">
        @foreach ($presets as $key => [$hex, $name])
            <button
                type="button"
                role="radio"
                x-on:click="select(@js($key))"
                :aria-checked="(selected === @js($key)).toString()"
                title="{{ $name }}"
                aria-label="{{ $name }}"
                class="relative size-9 cursor-pointer rounded-sm ring-offset-2 ring-offset-surface transition-shadow aria-checked:ring-2 aria-checked:ring-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ink"
                style="background: {{ $hex }}"
                {{-- The default is the page's own colour; it carries white text (theming guide), so the tick stays white. --}}
                @if ($key === 'default') x-bind:style="themeColor ? { background: themeColor } : {}" @endif
            >
                <svg x-show="selected === @js($key)" x-cloak data-theme="light" class="absolute inset-0 m-auto size-4 {{ $whiteTick($hex) ? 'text-surface' : 'text-ink' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </button>
        @endforeach

        @if ($custom)
            <label
                class="relative flex h-9 cursor-pointer items-center gap-2 rounded-sm px-3 text-sm ring-1 ring-line ring-inset hover:ring-line-strong"
                :class="isCustom && 'ring-2 ring-ink'"
                title="{{ __('Pick your own color') }}"
            >
                <span class="size-5 rounded-sm ring-1 ring-ink/10" :style="`background: ${customColor}`"></span>
                <span class="font-medium text-body">{{ __('Custom') }}</span>
                <span x-show="isCustom" x-cloak class="font-mono text-xs text-muted uppercase" x-text="selected"></span>
                <input
                    type="color"
                    :value="customColor"
                    x-on:input.debounce.150ms="select($event.target.value.toLowerCase())"
                    class="absolute inset-0 size-full cursor-pointer opacity-0"
                    aria-label="{{ __('Pick your own color') }}"
                />
            </label>
        @endif
    </div>

</div>
