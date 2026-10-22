@props([
    // How it shows: button (the navbar icon: light ↔ dark) · drawer (a panel from the right with the
    // sections) · inline (the sections in place).
    'as' => 'button',
    // The sections, in order (an array or a comma-separated string): the switcher's own (mode, color,
    // theme) and the ones a package adds (Acun\Ui\Support\Appearance::section(): acunsoft/acun-pro's
    // panel adds skin, width, sidebar and navbar, its storefront header, footer, language and links).
    // A section with nothing to offer, or that nobody added, is skipped. null: mode, color, theme.
    'sections' => null,
    // The mode choices (light, dark, system), or false. A theme without a dark member offers none.
    'modes' => ['light', 'dark', 'system'],
    // The preset colours: true (Appearance::presets()), false, or key => [#rrggbb, name].
    'colors' => true,
    // The free colour input (#rrggbb).
    'custom' => true,
    // The themes a visitor may switch to: null (what a package offers on this request,
    // Appearance::switchable(); none in the free package), false, or items (key, name, url, …).
    'themes' => null,
    // drawer: the panel's title (null: "Appearance"); inline: a label above the sections.
    'title' => null,
    // A line under the drawer's title (null: "Changes apply right away…"), or false.
    'description' => null,
    // drawer: the trigger's accessible name (null: "Appearance settings").
    'label' => null,
    // drawer: its name (for acun:drawer.trigger and AcunUI.drawer.open()).
    'name' => 'theme-switcher',
    // drawer: what opens it: edge (a gear on the right edge) · button (an icon button in place) · false (a trigger of your own).
    'trigger' => 'edge',
    // A Reset button: forgets the scope's saved choices (localStorage 'acun-ui:{scope}') and applies the defaults.
    'reset' => true,
    // With reset: a link that also returns a server-side choice to its default (the storefront's theme).
    'resetUrl' => null,
    // The sections' headings; false: for screen readers only (a colour picker under a label of its own).
    'headings' => true,
])

{{--
    The theme switcher: the mode (light, dark, system), the primary colour (presets and a colour of the
    user's own, measured for contrast by the runtime) and, where a package offers them, the themes and
    its own sections. Choices apply at once, are kept in the browser per scope (the panel and the
    storefront never share them) and, with the layout's preference-url, saved to the account
    (acun:layout.head).

        <acun:theme-switcher />                                    the navbar button
        <acun:theme-switcher as="drawer" />                        a gear on the right edge and a panel
        <acun:theme-switcher as="drawer" trigger="button" />       an icon button in place opens the panel
        <acun:theme-switcher as="inline" :sections="['color']" />  in a settings page

    Sections of your own go in the slot (acun:theme-switcher.section). A choice that reloads the page
    (a theme, a header, the language: links and forms that call keepOpen()) opens the panel again at
    once on the next page. 1.x tags, wrappers of this one until 3.0: acun:theme-toggle,
    acun:brand-color-picker, acun:theme-customizer and acun:shop.theme-switcher.
--}}
@php
    $list = fn ($value): array => is_string($value)
        ? array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'))
        : array_values(array_filter((array) $value, 'is_string'));
    $as = in_array($as, ['button', 'drawer', 'inline'], true) ? $as : 'button';
    $pair = \Acun\Ui\Support\Appearance::pair();
    // A theme without a dark member has no mode to choose.
    $modes = $modes === false || $pair['dark'] === null ? [] : array_values(array_intersect($list($modes), \Acun\Ui\Support\Appearance::MODES));
@endphp

@if ($as === 'button')
@if (in_array('light', $modes, true) && in_array('dark', $modes, true))
{{--
    One click switches to the dark mode, another back to light ($store.appearance.toggle(): <html
    data-theme> changes at once and the choice is kept). The icon and the screen reader text follow
    the dark: variant, not Alpine: data-theme is written before the first paint, so the right icon
    shows on the first frame. The status next to it says the new mode to a screen reader.
--}}
<button
    type="button"
    x-data
    x-on:click="$store.appearance.toggle(); $el.nextElementSibling.textContent = $store.appearance.value === 'dark' ? @js(__('The dark theme is on.')) : @js(__('The light theme is on.'))"
    title="{{ __('Change theme') }}"
    data-test="theme-toggle"
    {{ $attributes->merge(['class' => 'flex size-9 flex-none cursor-pointer items-center justify-center rounded-sm text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus']) }}
>
    <span class="sr-only dark:hidden">{{ __('Switch to dark mode') }}</span>
    <span class="sr-only hidden dark:inline">{{ __('Switch to light mode') }}</span>

    {{-- Moon in the light theme (switch to dark), sun in the dark theme (switch to light). --}}
    <svg class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
    </svg>
    <svg class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
    </svg>
</button><span class="sr-only" role="status" data-theme-switcher-status></span>
@endif
@else
@php
    $drawer = $as === 'drawer';
    $id = \Acun\Ui\Support\Field::id('theme-switcher', $attributes, $drawer ? $name.'-switcher' : null, $as, $sections);
    $sections = array_values(array_unique($sections === null ? \Acun\Ui\Support\Appearance::SWITCHER_SECTIONS : $list($sections)));
    $registered = \Acun\Ui\Support\Appearance::sections();
    // Only #rrggbb presets reach the page (a tenant's or a user's included); \z, not $: $ also matches before a trailing newline.
    $presets = $colors === false ? [] : collect($colors === true || $colors === null ? \Acun\Ui\Support\Appearance::presets() : (array) $colors)
        ->filter(fn ($preset) => is_array($preset) && is_string($preset[0] ?? null) && preg_match('/^#[0-9a-f]{6}\z/i', $preset[0]))
        ->map(fn (array $preset) => [strtolower($preset[0]), is_scalar($preset[1] ?? null) ? (string) $preset[1] : $preset[0]])
        ->all();
    $items = $themes === false ? [] : ($themes === null ? \Acun\Ui\Support\Appearance::switchable() : \Acun\Ui\Support\Appearance::themeItems((array) $themes));
    $shown = array_values(array_filter($sections, fn (string $section): bool => match ($section) {
        'mode' => $modes !== [],
        'color' => $presets !== [] || $custom,
        'theme' => $items !== [],
        default => isset($registered[$section]),
    }));
    $modeLabels = ['light' => ['sun', __('Light')], 'dark' => ['moon', __('Dark')], 'system' => ['computer-desktop', __('System')]];
    $description = $description === false ? null : ($description ?? ($drawer ? __('Changes apply right away and are kept in this browser.') : null));
    $resetUrl = $resetUrl === null ? null : (\Acun\Ui\Support\Url::safe((string) $resetUrl) ?? '#');
@endphp
{{-- The sections, drawn once and put in the drawer or in place. --}}
@php(ob_start())
@foreach ($shown as $section)
@switch ($section)
@case ('mode')
    <acun:theme-switcher.section name="mode" :title="__('Color mode')" :options="collect($modes)->mapWithKeys(fn ($mode) => [$mode => ['label' => $modeLabels[$mode][1], 'icon' => $modeLabels[$mode][0]]])->all()" value="$store.appearance.value" change="$store.appearance.set" />
    @break
@case ('color')
    {{--
        Presets and a colour of the user's own, as one radio group plus the colour input. The choice goes
        to $store.brand: the runtime measures its shades on the enabled themes' backgrounds (--ac-user-*).
        "Default" is the page's own colour: the theme's --ac-tone-primary, which the user's colour never changes.
    --}}
    <acun:theme-switcher.section name="color" :id="$id.'-color'" :title="__('Primary color')" :group="false">
        <div
            x-data="{
                presets: @js(collect($presets)->map(fn ($preset) => $preset[0])),
                themeColor: null,
                get selected() { return $store.brand.color || 'default' },
                get isCustom() { return /^#[0-9a-f]{6}$/i.test(this.selected) },
                get themeHex() { return /^#[0-9a-f]{6}$/i.test(this.themeColor ?? '') ? this.themeColor.toLowerCase() : null },
                get customColor() {
                    if (this.isCustom) return this.selected;
                    if (this.selected === 'default' && this.themeHex) return this.themeHex;
                    return this.presets[this.selected] ?? this.themeHex ?? '#000000';
                },
                init() { this.themeColor = getComputedStyle(document.documentElement).getPropertyValue('--ac-tone-primary').trim() || null },
                select(value) { $store.brand.set(value) },
            }"
            class="flex flex-wrap items-center gap-2"
            role="radiogroup"
            aria-labelledby="{{ $id }}-color"
        >
            @foreach ($presets as $key => [$hex, $presetName])
                <label class="relative size-9 cursor-pointer" title="{{ $presetName }}">
                    <input data-acun-control type="radio" name="{{ $id }}-color" value="{{ $key }}" aria-label="{{ $presetName }}" class="peer sr-only" x-bind:checked="selected === @js((string) $key)" x-on:change="select(@js((string) $key))">
                    {{-- The default is the page's own colour; it carries white text (theming guide), so its tick stays white. --}}
                    <span
                        class="absolute inset-0 rounded-sm ring-offset-2 ring-offset-surface transition-shadow peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:outline-2 peer-focus-visible:outline-offset-4 peer-focus-visible:outline-ink"
                        style="background: {{ $hex }}"
                        @if ($key === 'default') x-bind:style="themeColor ? { background: themeColor } : {}" @endif
                    ></span>
                    {{-- The tick is a graphic: white while it stands out 3:1 on the swatch, else the light theme's ink. --}}
                    <svg x-show="selected === @js((string) $key)" x-cloak data-theme="light" class="pointer-events-none absolute inset-0 m-auto size-4 {{ \Acun\Ui\Support\Contrast::whiteReads($hex) ? 'text-surface' : 'text-ink' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </label>
            @endforeach

            @if ($custom)
                <label
                    class="relative flex h-9 cursor-pointer items-center gap-2 rounded-sm px-3 text-sm ring-1 ring-line ring-inset hover:ring-line-strong has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus"
                    x-bind:class="isCustom && 'ring-2 ring-ink'"
                    title="{{ __('Pick your own color') }}"
                >
                    <span class="size-5 rounded-sm ring-1 ring-ink/10" x-bind:style="`background: ${customColor}`"></span>
                    <span class="font-medium text-body">{{ __('Custom') }}</span>
                    <span x-show="isCustom" x-cloak class="font-mono text-xs text-muted uppercase" x-text="selected"></span>
                    <input
                        data-acun-control
                        type="color"
                        x-bind:value="customColor"
                        x-on:input.debounce.150ms="select($event.target.value.toLowerCase())"
                        class="absolute inset-0 size-full cursor-pointer opacity-0"
                        aria-label="{{ __('Pick your own color') }}"
                    />
                </label>
            @endif
        </div>
    </acun:theme-switcher.section>
    @break
@case ('theme')
    {{--
        The themes, each a link that keeps the choice on the server (a cookie) and opens this page in it.
        The miniature is drawn in the theme's own tokens (its light member) with its own primary colour,
        not the user's; it is decorative.
    --}}
    <acun:theme-switcher.section name="theme" :title="__('Theme')">
        <ul class="space-y-2">
            @foreach ($items as $item)
                <li>
                    <a href="{{ $item['url'] }}" x-on:click="keepOpen()" @if ($item['active']) aria-current="true" @endif @class([
                        'flex items-center gap-3 rounded-sm p-2 ring-inset transition-colors focus-visible:outline-2 focus-visible:outline-focus',
                        'bg-primary-tint ring-2 ring-primary-ink' => $item['active'],
                        'ring-1 ring-line hover:bg-hover hover:ring-line-strong' => ! $item['active'],
                    ])>
                        <span data-theme="{{ $item['light'] }}" class="flex h-12 w-16 flex-none flex-col gap-1 overflow-hidden bg-page p-1.5 ring-1 ring-ink/5 [--ac-user-fill:initial] [--ac-user-tone:initial] {{ ['modern' => 'rounded-[12px]', 'classic' => 'rounded-none'][$item['look']] ?? 'rounded-[6px]' }}" aria-hidden="true">
                            @if ($item['look'] === 'classic')
                                <span class="h-1 w-full bg-primary"></span>
                            @endif
                            <span class="flex items-center justify-between">
                                <span class="h-1.5 w-4 rounded-full bg-ink"></span>
                                <span @class(['h-1.5 w-6', 'rounded-full bg-primary' => $item['look'] === 'modern', 'bg-line-strong' => $item['look'] !== 'modern'])></span>
                            </span>
                            <span class="flex flex-1 gap-1">
                                <span @class(['flex-1 bg-primary opacity-85', 'rounded-[2px]' => $item['look'] !== 'classic'])></span>
                                <span @class(['w-3 bg-surface', 'rounded-[2px]' => $item['look'] !== 'classic'])></span>
                            </span>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                                {{ $item['name'] }}
                                @if ($item['style'] !== '')
                                    <span @class(['text-xs font-normal', 'text-dim' => $item['active'], 'text-muted' => ! $item['active']])>· {{ $item['style'] }}</span>
                                @endif
                            </span>
                            {{-- On the current one's tint the dim grey: the muted one read 4.48:1 there with a navy colour. --}}
                            @if ($item['description'] !== '')
                                <span @class(['block truncate text-xs', 'text-dim' => $item['active'], 'text-muted' => ! $item['active']])>{{ $item['description'] }}</span>
                            @endif
                        </span>

                        @if ($item['active'])
                            <acun:icon name="check-circle" solid size="5" class="flex-none text-primary-ink" />
                            <span class="sr-only">{{ __('(current theme)') }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </acun:theme-switcher.section>
    @break
@default
    @include($registered[$section], ['section' => $section])
@endswitch
@endforeach
{{ $slot }}
@php($body = ob_get_clean())
<div
    x-data="{
        // The page reloads after a choice the server keeps (a theme, a header, the language): the
        // panel opens again at once there (sessionStorage 'acun-ui:{scope}:open' holds its name).
        openKey() { return 'acun-ui:' + ($store.appearance?.scope ?? 'app') + ':open' },
        keepOpen() {
            if (! @js($drawer)) return
            try { sessionStorage.setItem(this.openKey(), @js($name)) } catch {}
        },
        // Back to the defaults: the scope's saved choices are forgotten (the other scopes keep theirs).
        reset() {
            $store.customizer?.reset()
            const status = document.getElementById(@js($id.'-status'))
            if (! status) return
            status.textContent = ''
            setTimeout(() => (status.textContent = @js(__('The appearance is back to its defaults.'))), 100)
        },
        init() {
            if (! @js($drawer)) return
            try {
                if (sessionStorage.getItem(this.openKey()) !== @js($name)) return
                sessionStorage.removeItem(this.openKey())
            } catch { return }
            // Open at once, without sliding in: it looks as if the panel never closed. The drawer slides
            // in a couple of frames after it opens (later while the page is still loading), so sliding
            // stays off until its panel is in place.
            this.$el.dataset.instant = ''
            const panel = this.$el.querySelector('[data-acun-drawer] [x-ref=panel]')
            const settle = () => { watch.disconnect(); requestAnimationFrame(() => delete this.$el.dataset.instant) }
            const watch = new MutationObserver(() => panel.classList.contains('translate-x-0') && settle())
            watch.observe(panel, { attributes: true, attributeFilter: ['class'] })
            setTimeout(settle, 3000)
            this.$nextTick(() => this.$dispatch('acun:drawer:open', { name: @js($name) }))
        },
    }"
    data-theme-switcher="{{ $as }}"
    {{ $attributes->class(['data-instant:**:transition-none!' => $drawer, 'space-y-7' => ! $drawer]) }}
>
@if ($drawer)
    @if ($trigger === 'edge')
        <button
            type="button"
            x-on:click="$dispatch('acun:drawer:open', { name: @js($name) })"
            class="group fixed top-1/2 right-0 z-40 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-l-sm bg-primary text-on-primary shadow-lg transition-[width,background-color] hover:w-11 hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            aria-label="{{ $label ?? __('Appearance settings') }}"
            title="{{ $label ?? __('Appearance settings') }}"
            aria-haspopup="dialog"
            aria-controls="{{ $name }}"
        >
            <acun:icon name="cog-6-tooth" size="5" class="animate-[spin_6s_linear_infinite] motion-reduce:animate-none" />
        </button>
    @elseif ($trigger === 'button')
        <button
            type="button"
            x-on:click="$dispatch('acun:drawer:open', { name: @js($name) })"
            class="flex size-9 flex-none cursor-pointer items-center justify-center rounded-sm text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            aria-label="{{ $label ?? __('Appearance settings') }}"
            title="{{ $label ?? __('Appearance settings') }}"
            aria-haspopup="dialog"
            aria-controls="{{ $name }}"
        >
            <acun:icon name="swatch" size="5" />
        </button>
    @endif

    <acun:drawer :name="$name" side="right" size="md" :title="$title ?? __('Appearance')" :lock-scroll="false" :backdrop="false">
        {{-- data-drawer-keep-open: a link that reloads the page does not close the panel first. --}}
        <div class="space-y-7" data-drawer-keep-open>
            {{-- What Reset did, for a screen reader; first, so it adds no space (it is out of the flow). --}}
            <p id="{{ $id }}-status" class="sr-only" role="status"></p>
            @if ($description)
                <p class="text-sm text-muted">{{ $description }}</p>
            @endif
            {!! $body !!}
        </div>

        <acun:slot:footer>
            @if ($reset && $resetUrl !== null)
                <acun:button variant="ghost" size="sm" :href="$resetUrl" x-on:click="reset(); keepOpen()" data-drawer-keep-open>
                    <acun:icon name="arrow-path" size="4" /> {{ __('Reset to defaults') }}
                </acun:button>
            @elseif ($reset)
                <acun:button variant="ghost" size="sm" x-on:click="reset()">
                    <acun:icon name="arrow-path" size="4" /> {{ __('Reset to defaults') }}
                </acun:button>
            @endif
            <acun:button size="sm" x-on:click="close()">{{ __('Done') }}</acun:button>
        </acun:slot:footer>
    </acun:drawer>
@else
    <p id="{{ $id }}-status" class="sr-only" role="status"></p>
    @if ($title)
        <p class="mb-3 text-sm font-medium text-ink">{{ $title }}</p>
    @endif
    @if ($description)
        <p class="text-sm text-muted">{{ $description }}</p>
    @endif
    {!! $body !!}
    @if ($reset)
        <div>
            <acun:button variant="ghost" size="sm" :href="$resetUrl" x-on:click="reset()">
                <acun:icon name="arrow-path" size="4" /> {{ __('Reset to defaults') }}
            </acun:button>
        </div>
    @endif
@endif
</div>
@endif
