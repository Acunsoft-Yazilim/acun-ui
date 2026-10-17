@props([
    'title' => null,
    'homeUrl' => null,
    'brandName' => null,
    'drawerTitle' => null,
    // Theme button in the top bar; null: config('acun-ui.layout.theme_toggle'), else true.
    'themeToggle' => null,
    'theme' => null,
    'brandColor' => null,
    'preferenceUrl' => null,
    'skin' => null,
    // full | boxed (at most 1440px, centred); null: the browser's saved choice, else config.
    'contentWidth' => null,
    // Appearance customizer button on the right edge (acun:theme-customizer); null: config, else false.
    'customizer' => null,
    // true: the menu gets its own row under the logo, search and user menu (for menus too long for one row).
    'stacked' => false,
])

{{--
    Horizontal (top menu) panel layout. Slots:
      head         extra <head> contents
      logo         logo on the left of the top bar (defaults to the brand name)
      wordmark     next to the logo from `sm` up: give `logo` a square mark and this the name, and
                   phones show the mark alone (a full logo leaves the top bar no room there)
      nav          top menu (acun:nav.horizontal-item / acun:nav.horizontal-group) - lg and up
      mobileNav    menu in the mobile drawer (acun:nav.item / group); defaults to nav
      search       search to the right of the menu (xl and up; e.g. acun:command-palette)
      actions      before the theme toggle
      userMenu     right side of the top bar
      drawerFooter bottom of the mobile drawer
      footer       below the page content (e.g. acun:footer: copyright and links)
--}}
@php
    $homeUrl = \Acun\Ui\Support\Url::safe((string) ($homeUrl ?? url('/'))) ?? '#';
    $brandName ??= config('app.name');
    // A value the page forces (Layout::page()) wins; see acun:layout.head for the order.
    $theme = \Acun\Ui\Support\Layout::forced('theme') ?? $theme;
    $skin = \Acun\Ui\Support\Layout::forced('skin') ?? $skin;
    $contentWidth = \Acun\Ui\Support\Layout::forced('content_width') ?? $contentWidth;
    $customizer = \Acun\Ui\Support\Layout::forced('customizer') ?? $customizer ?? \Acun\Ui\Support\Layout::default('customizer') ?? false;
    $themeToggle = \Acun\Ui\Support\Layout::forced('theme_toggle') ?? $themeToggle ?? \Acun\Ui\Support\Layout::default('theme_toggle') ?? true;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="panel-layout"@if ($skin) data-skin="{{ $skin }}"@endif{!! $contentWidth === 'boxed' ? ' data-content-width="boxed"' : '' !!}>
<head>
    <acun:layout.head :title="$title" :theme="$theme" :brand-color="$brandColor" :preference-url="$preferenceUrl" :skin="$skin" :content-width="$contentWidth">
        {{ $head ?? '' }}
    </acun:layout.head>
</head>
<body class="layout-horizontal min-h-dvh bg-gray-50 dark:bg-zinc-950" x-data="{ mobileMenuOpen: false }" x-on:acun:drawer:opened.window="if ($event.detail.name === 'panel-sidebar-horizontal') mobileMenuOpen = true" x-on:acun:drawer:closed.window="if ($event.detail.name === 'panel-sidebar-horizontal') mobileMenuOpen = false">
    {{-- The first Tab stop: past the menu and the top bar to the page's content. --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] focus:rounded-sm focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-brand-700 focus:shadow-lg focus:outline-2 focus:outline-focus dark:focus:bg-zinc-900 dark:focus:text-brand-300">{{ __('Skip to content') }}</a>
    <div class="flex min-h-dvh flex-col">
        {{-- Floating top bar: a white card with a hairline, in the same container as the page (panel.css .panel-container). --}}
        <div class="panel-topbar panel-gutter relative isolate z-50 shrink-0 pt-4">
        <header class="panel-container rounded-sm bg-white shadow-sm dark:bg-zinc-900">
        <div class="flex h-16 min-w-0 items-center gap-2 px-3 sm:gap-4 sm:px-4">
            <a href="{{ $homeUrl }}" wire:navigate aria-label="{{ __('Dashboard home') }}" class="flex flex-none items-center gap-2.5 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                @isset($logo)
                    {{ $logo }}
                    @isset($wordmark)
                        <span class="hidden min-w-0 items-center sm:flex">{{ $wordmark }}</span>
                    @endisset
                @else
                    <span class="font-brand text-[15px] font-bold tracking-tight text-gray-900 dark:text-zinc-100">{{ $brandName }}</span>
                @endisset
            </a>

            @if ($stacked)
                {{-- The search takes the free space and narrows with it: a fixed width pushed the bar past the screen on tablets. --}}
                <div class="flex min-w-0 flex-1 justify-end">
                    @isset($search)
                        <div class="hidden w-full max-w-64 min-w-0 md:block">{{ $search }}</div>
                    @endisset
                </div>
            @else
                <span class="hidden h-6 w-px bg-gray-200 lg:block dark:bg-zinc-700" aria-hidden="true"></span>

                <nav aria-label="{{ __('Main menu') }}" class="hidden min-w-0 flex-1 items-center self-stretch lg:flex">
                    {{ $nav ?? '' }}
                </nav>

                <div class="flex-1 lg:hidden"></div>
            @endif

            <button type="button" x-on:click="$dispatch('acun:drawer:open', { name: 'panel-sidebar-horizontal' })" class="flex size-9 items-center justify-center rounded-sm text-gray-500 hover:bg-gray-100 lg:hidden dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="{{ __('Open menu') }}" aria-controls="panel-sidebar-horizontal" :aria-expanded="mobileMenuOpen.toString()">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            @if (! $stacked && isset($search))
                <div class="hidden w-56 flex-none xl:block">{{ $search }}</div>
            @endif

            {{ $actions ?? '' }}

            @if ($themeToggle)
                <acun:theme-toggle />
            @endif

            {{ $userMenu ?? '' }}
        </div>

        @if ($stacked)
            {{-- The menu's own row: the dropdowns open below it. --}}
            <nav aria-label="{{ __('Main menu') }}" class="hidden h-12 min-w-0 items-stretch border-t border-gray-100 px-2 lg:flex dark:border-zinc-800">
                {{ $nav ?? '' }}
            </nav>
        @endif
        </header>
        </div>

        {{--
            The page itself scrolls; the top bar above sticks to the top (panel.css .panel-topbar).
            `content-area`: left padding while the docked Filtreler (filters) panel is open (panel.css).
            The page sits in the panel container: its edges line up with the top bar's on every screen.
        --}}
        <main id="main-content" tabindex="-1" class="focus:outline-none content-area panel-gutter w-full min-w-0 flex-1 py-6 transition-[padding] duration-200">
            <div class="panel-container">
                {{ $slot }}
            </div>
        </main>

        @isset($footer)
            <div class="panel-gutter w-full min-w-0">
                <div class="panel-container">{{ $footer }}</div>
            </div>
        @endisset
    </div>

    <acun:drawer name="panel-sidebar-horizontal" side="left" :title="$drawerTitle ?? $brandName">
        <nav class="flex flex-col gap-0.5 ps-3">
            {{ $mobileNav ?? $nav ?? '' }}
        </nav>

        @isset($drawerFooter)
            <acun:slot:footer>
                {{ $drawerFooter }}
            </acun:slot:footer>
        @endisset
    </acun:drawer>

    @if ($customizer)
        <acun:theme-customizer :sidebar="false" />
    @endif

    <acun:toast-container />
    {{-- Acun Pro (acunsoft/acun-pro) adds the unsaved-changes dialog here; skipped without it. --}}
    @includeIf('acun-pro::layout-extras')

    @livewireScripts
    {{-- Code for this page only: @push('page-scripts') in the page (e.g. @vite('resources/js/pages/rapor.js')). --}}
    @stack('page-scripts')
</body>

</html>
