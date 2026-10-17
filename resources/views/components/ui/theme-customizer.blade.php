@props([
    // Menu-related options (collapse, dark menu, top bar mode) - false in the horizontal layout.
    'sidebar' => true,
    'title' => null,
])

{{--
    Appearance customizer: a fixed gear button on the right edge and a panel
    that opens from the right. Theme, primary colour, skin, content width, menu and top bar
    preferences apply instantly, are stored in the browser and, when the layout
    has a preference-url, saved to the account (see acun:layout.head). Enabled in
    the layouts with the `customizer` prop.
--}}
@php
    $optionClass = 'flex-1 cursor-pointer rounded-sm px-3 py-2 text-sm font-medium transition-colors';
    $selectedClass = "'bg-white text-gray-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100'";
    $unselectedClass = "'text-gray-500 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100'";
    $headingClass = 'font-mono text-2xs font-medium tracking-[0.08em] text-gray-500 uppercase dark:text-zinc-400';
@endphp

<div x-data {{ $attributes }}>
    <button
        type="button"
        x-on:click="$dispatch('acun:drawer:open', { name: 'theme-customizer' })"
        class="group fixed top-1/2 right-0 z-40 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-l-sm bg-brand-600 text-white shadow-lg transition-[width,background-color] hover:w-11 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
        aria-label="{{ __('Appearance settings') }}"
        title="{{ __('Appearance settings') }}"
    >
        <acun:icon name="cog-6-tooth" size="5" class="animate-[spin_6s_linear_infinite] motion-reduce:animate-none" />
    </button>

    <acun:drawer name="theme-customizer" side="right" size="md" :title="$title ?? __('Appearance')" :lock-scroll="false" :backdrop="false">
        <div class="space-y-7">
            <p class="text-sm text-gray-500 dark:text-zinc-400">{{ __('Changes apply right away and are kept in this browser.') }}</p>

            <section class="space-y-2.5">
                <h4 class="{{ $headingClass }}">{{ __('Theme') }}</h4>
                <div class="flex gap-1 rounded-sm bg-gray-100 p-1 dark:bg-zinc-800">
                    @foreach (['light' => ['sun', __('Light')], 'dark' => ['moon', __('Dark')], 'system' => ['computer-desktop', __('System')]] as $value => [$icon, $label])
                        <button type="button" x-on:click="$store.appearance.set(@js($value))" :aria-pressed="($store.appearance.value === @js($value)).toString()" :class="$store.appearance.value === @js($value) ? {{ $selectedClass }} : {{ $unselectedClass }}" class="{{ $optionClass }} flex items-center justify-center gap-1.5">
                            <acun:icon :name="$icon" size="4" /> {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="space-y-2.5">
                <h4 class="{{ $headingClass }}">{{ __('Primary color') }}</h4>
                <acun:brand-color-picker />
            </section>

            <section class="space-y-2.5">
                <h4 class="{{ $headingClass }}">{{ __('Appearance') }}</h4>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['default' => [__('Default'), __('Shadowed cards')], 'bordered' => [__('Bordered'), __('No shadow, 1px border')]] as $value => [$label, $description])
                        <button type="button" x-on:click="$store.customizer.setSkin(@js($value))" :aria-pressed="($store.customizer.skin === @js($value)).toString()" :class="$store.customizer.skin === @js($value) ? 'ring-2 ring-brand-600' : 'ring-1 ring-gray-200 hover:ring-gray-300 dark:ring-zinc-700'" class="cursor-pointer rounded-sm p-3 text-left ring-inset">
                            <span class="block text-sm font-medium text-gray-900 dark:text-zinc-100">{{ $label }}</span>
                            <span class="block text-xs text-gray-500 dark:text-zinc-400">{{ $description }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="space-y-2.5">
                <h4 class="{{ $headingClass }}">{{ __('Content width') }}</h4>
                <div class="flex gap-1 rounded-sm bg-gray-100 p-1 dark:bg-zinc-800">
                    @foreach (['full' => __('Wide'), 'boxed' => __('Boxed')] as $value => $label)
                        <button type="button" x-on:click="$store.customizer.setContentWidth(@js($value))" :aria-pressed="($store.customizer.contentWidth === @js($value)).toString()" :class="$store.customizer.contentWidth === @js($value) ? {{ $selectedClass }} : {{ $unselectedClass }}" class="{{ $optionClass }}">{{ $label }}</button>
                    @endforeach
                </div>
            </section>

            @if ($sidebar)
                <section class="space-y-2.5">
                    <h4 class="{{ $headingClass }}">{{ __('Menu') }}</h4>
                    <div class="flex gap-1 rounded-sm bg-gray-100 p-1 dark:bg-zinc-800">
                        <button type="button" x-on:click="$store.sidebar.set(false)" :aria-pressed="(! $store.sidebar.collapsed).toString()" :class="! $store.sidebar.collapsed ? {{ $selectedClass }} : {{ $unselectedClass }}" class="{{ $optionClass }}">{{ __('Expanded') }}</button>
                        <button type="button" x-on:click="$store.sidebar.set(true)" :aria-pressed="$store.sidebar.collapsed.toString()" :class="$store.sidebar.collapsed ? {{ $selectedClass }} : {{ $unselectedClass }}" class="{{ $optionClass }}">{{ __('Collapsed') }}</button>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-sm p-1 text-sm">
                        <span>
                            <span class="block font-medium text-gray-900 dark:text-zinc-100">{{ __('Dark menu') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-zinc-400">{{ __('The menu stays dark in the light theme') }}</span>
                        </span>
                        <span class="relative inline-flex flex-none">
                            <input type="checkbox" role="switch" class="peer sr-only" :checked="$store.customizer.semiDark" x-on:change="$store.customizer.setSemiDark($event.target.checked)">
                            <span class="h-5 w-9 rounded-full bg-gray-300 transition-colors peer-checked:bg-brand-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus dark:bg-zinc-600"></span>
                            <span class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                </section>

                <section class="space-y-2.5">
                    <h4 class="{{ $headingClass }}">{{ __('Top bar') }}</h4>
                    <div class="flex gap-1 rounded-sm bg-gray-100 p-1 dark:bg-zinc-800">
                        @foreach (['fixed' => __('Fixed'), 'static' => __('Scrolling'), 'hidden' => __('Hidden')] as $value => $label)
                            <button type="button" x-on:click="$store.navbar.set(@js($value))" :aria-pressed="($store.navbar.mode === @js($value)).toString()" :class="$store.navbar.mode === @js($value) ? {{ $selectedClass }} : {{ $unselectedClass }}" class="{{ $optionClass }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <acun:slot:footer>
            <acun:button variant="ghost" size="sm" x-on:click="$store.customizer.reset()">
                <acun:icon name="arrow-path" size="4" /> {{ __('Reset to defaults') }}
            </acun:button>
            <acun:button size="sm" x-on:click="close()">{{ __('Done') }}</acun:button>
        </acun:slot:footer>
    </acun:drawer>
</div>
