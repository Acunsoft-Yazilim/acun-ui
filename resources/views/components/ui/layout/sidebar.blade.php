@props([
    'title' => null,
    'homeUrl' => null,
    'brandName' => null,
    'brandTagline' => null,
    // Title of the mobile drawer; falls back to the brand name.
    'drawerTitle' => null,
    // Theme button in the top bar; null: config('acun-ui.layout.theme_toggle'), else true.
    'themeToggle' => null,
    // Preferences (see acun:layout.head).
    'theme' => null,
    'brandColor' => null,
    'sidebarMode' => null,
    // null: the browser's saved choice, else 'fixed'.
    'navbarMode' => null,
    'preferenceUrl' => null,
    // Skin: default · bordered (no shadows, 1px borders)
    'skin' => null,
    // full | boxed (at most 1440px, centred); null: the browser's saved choice, else config.
    'contentWidth' => null,
    // Only the menu is dark in the light theme (semi-dark); null: the browser's saved choice.
    'semiDark' => null,
    // Appearance customizer button on the right edge (acun:theme-customizer); null: config, else false.
    'customizer' => null,
    // Page title on the left of the top bar (or the `heading` slot).
    'pageTitle' => null,
])

{{--
    Vertical (side menu) panel layout. Slots:
      head         extra <head> contents (@vite, favicon, fonts)
      logo         logo at the top of the menu; a square mark (defaults to the brand name's first letter)
      wordmark     drawn next to the logo instead of brand-name/brand-tagline, e.g. a logo's
                   lettering as SVG; hidden while the menu is collapsed (only the mark stays)
      nav          menu contents (acun:nav.*) - in the desktop menu and the mobile drawer
      heading      left side of the top bar (defaults to page-title)
      search       middle of the top bar (e.g. acun:command-palette)
      actions      in the top bar, before the theme toggle (notifications etc.)
      userMenu     right side of the top bar (acun:user-menu)
      drawerFooter bottom of the mobile drawer (e.g. Çıkış Yap / log out)
      footer       below the page content (e.g. acun:footer: copyright and links)
      (default)    page content

    The page itself scrolls (the window); the side menu is fixed. Navbar mode (`data-navbar`):
      - fixed: the top bar sticks to the top, the page scrolls under it (panel.css .panel-topbar)
      - static: the top bar scrolls away with the page
      - hidden: no top bar from lg up; below lg it stays (static), as it holds the menu button,
        the user menu and the theme button
--}}
@php
    $homeUrl = \Acun\Ui\Support\Url::safe((string) ($homeUrl ?? url('/'))) ?? '#';
    $brandName ??= config('app.name');
    // A value the page forces (Layout::page()) wins; the head gets it as the saved preference.
    $theme = \Acun\Ui\Support\Layout::forced('theme') ?? $theme;
    $skin = \Acun\Ui\Support\Layout::forced('skin') ?? $skin;
    $contentWidth = \Acun\Ui\Support\Layout::forced('content_width') ?? $contentWidth;
    $semiDark = \Acun\Ui\Support\Layout::forced('semi_dark') ?? $semiDark;
    $savedNavbarMode = \Acun\Ui\Support\Layout::forced('navbar_mode') ?? $navbarMode;
    $sidebarMode = \Acun\Ui\Support\Layout::forced('sidebar_mode') ?? $sidebarMode;
    // First-frame rendering (the browser's stored choice is applied by the head script).
    $navbarMode = $savedNavbarMode ?? \Acun\Ui\Support\Layout::default('navbar_mode') ?? 'fixed';
    $semiDarkMenu = $semiDark ?? \Acun\Ui\Support\Layout::default('semi_dark') ?? false;
    $customizer = \Acun\Ui\Support\Layout::forced('customizer') ?? $customizer ?? \Acun\Ui\Support\Layout::default('customizer') ?? false;
    $themeToggle = \Acun\Ui\Support\Layout::forced('theme_toggle') ?? $themeToggle ?? \Acun\Ui\Support\Layout::default('theme_toggle') ?? true;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="panel-layout"@if ($skin) data-skin="{{ $skin }}"@endif{!! $contentWidth === 'boxed' ? ' data-content-width="boxed"' : '' !!}>
<head>
    <acun:layout.head :title="$title" :theme="$theme" :brand-color="$brandColor" :sidebar-mode="$sidebarMode" :navbar-mode="$savedNavbarMode" :preference-url="$preferenceUrl" :skin="$skin" :content-width="$contentWidth" :semi-dark="$semiDark">
        {{ $head ?? '' }}
    </acun:layout.head>
</head>
<body class="layout-vertical min-h-dvh bg-gray-50 dark:bg-zinc-950" x-data="{ mobileMenuOpen: false }" x-on:acun:drawer:opened.window="if ($event.detail.name === 'panel-sidebar') mobileMenuOpen = true" x-on:acun:drawer:closed.window="if ($event.detail.name === 'panel-sidebar') mobileMenuOpen = false" :class="{ 'sidebar-collapsed': $store.sidebar.collapsed }">
    {{-- The first Tab stop: past the menu and the top bar to the page's content. --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] focus:rounded-sm focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-brand-700 focus:shadow-lg focus:outline-2 focus:outline-focus dark:focus:bg-zinc-900 dark:focus:text-brand-300">{{ __('Skip to content') }}</a>
    {{--
        NO `x-cloak`: the menu's initial state does not depend on Alpine (width
        and collapsed look come from `data-sidebar-collapsed`, written by the
        synchronous script in <head>, + panel.css) - the menu looks complete and
        correct even on the first frame before Alpine loads.
    --}}
    <aside
        x-on:mouseenter="$store.sidebar.openHover()"
        x-on:mousemove="$store.sidebar.hoverSuppressed = false; $store.sidebar.openHover()"
        x-on:mouseleave="$store.sidebar.closeHover()"
        @class(['panel-sidebar hidden border-r border-gray-200 bg-white transition-[width,box-shadow] duration-200 motion-reduce:transition-none dark:border-zinc-800 dark:bg-zinc-900 lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:flex lg:flex-col', 'dark' => $semiDarkMenu])
        :class="{ 'sidebar-hover': $store.sidebar.hover, 'sidebar-collapsed': $store.sidebar.collapsed, 'lg:shadow-xl': $store.sidebar.collapsed && $store.sidebar.hover }"
        aria-label="{{ __('Panel menu') }}"
    >
        <div class="flex h-24 flex-none items-center overflow-hidden px-[1.3125rem]">
            <a href="{{ $homeUrl }}" wire:navigate aria-label="{{ __('Dashboard home') }}" class="flex min-w-0 flex-none items-center gap-3 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                <span class="flex size-[1.875rem] flex-none items-center justify-center">
                    @isset($logo)
                        {{ $logo }}
                    @else
                        {{-- No logo: the brand name's first letter, so the collapsed menu still shows a mark. --}}
                        <span class="flex size-full items-center justify-center rounded-sm bg-brand-600 font-brand text-sm font-bold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $brandName, 0, 1)) }}</span>
                    @endisset
                </span>
                @isset($wordmark)
                    <span class="collapsed-hidden flex min-w-0 items-center">{{ $wordmark }}</span>
                @else
                    <span class="collapsed-hidden flex min-w-0 flex-col whitespace-nowrap">
                        <span class="truncate font-brand text-[15px] leading-tight font-bold tracking-tight text-gray-900 dark:text-zinc-100">{{ $brandName }}</span>
                        @if ($brandTagline)
                            <span class="truncate text-xs leading-tight text-gray-500 dark:text-zinc-400">{{ $brandTagline }}</span>
                        @endif
                    </span>
                @endisset
            </a>
        </div>

        <button type="button" x-on:click="$store.sidebar.toggle(); $store.sidebar.hover = false; $store.sidebar.hoverSuppressed = true" class="menu-toggle menu-collapse-button absolute top-12 z-10 flex -translate-y-1/2 items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" :aria-label="$store.sidebar.collapsed ? @js(__('Expand menu')) : @js(__('Collapse menu'))" :aria-expanded="(!$store.sidebar.collapsed).toString()" aria-controls="panel-main-menu">
            <svg class="size-3.5 transition-transform motion-reduce:transition-none" :class="$store.sidebar.collapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <nav id="panel-main-menu" aria-label="{{ __('Main menu') }}" x-on:click="if ($event.target.closest('a[href]')) { $store.sidebar.hover = false; $store.sidebar.hoverSuppressed = true }" class="sidebar-scrollbar flex min-h-0 flex-1 flex-col gap-0.5 overflow-x-hidden overflow-y-auto px-3 pt-1 pb-4 [&>*]:shrink-0">
            {{ $nav ?? '' }}
        </nav>
    </aside>

    <acun:drawer name="panel-sidebar" side="left" :title="$drawerTitle ?? $brandName">
        <nav class="flex flex-col gap-0.5 ps-3">
            {{ $nav ?? '' }}
        </nav>

        @isset($drawerFooter)
            <acun:slot:footer>
                {{ $drawerFooter }}
            </acun:slot:footer>
        @endisset
    </acun:drawer>

    <div
        data-navbar="{{ $navbarMode }}"
        :data-navbar="$store.navbar.mode"
        class="group/content panel-content content-area flex min-h-dvh flex-col transition-[padding-left] duration-200 motion-reduce:transition-none"
    >
        {{--
            Floating top bar: a white card with a hairline, in the same container as the page (panel.css .panel-container); sticky in the fixed mode.
            The hidden mode hides it from lg up only: below lg it holds the only way to the menu (the drawer button), the user menu (sign-out) and the theme button.
        --}}
        <div
            @class(['panel-topbar panel-gutter relative z-20 shrink-0 pt-4', 'lg:hidden' => $navbarMode === 'hidden'])
            :class="{ 'lg:hidden': $store.navbar.mode === 'hidden' }"
        >
            <header class="panel-container flex h-16 min-w-0 items-center gap-3 rounded-sm bg-white px-3 shadow-sm sm:px-4 dark:bg-zinc-900">
                <button type="button" x-on:click="$dispatch('acun:drawer:open', { name: 'panel-sidebar' })" class="flex size-9 flex-none items-center justify-center rounded-sm text-gray-500 hover:bg-gray-100 lg:hidden dark:text-zinc-400 dark:hover:bg-zinc-800" aria-label="{{ __('Open menu') }}" aria-controls="panel-sidebar" :aria-expanded="mobileMenuOpen.toString()">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                @php($hasHeading = isset($heading) || filled($pageTitle))

                @if ($hasHeading)
                    <div class="min-w-0 flex-1">
                        @isset($heading)
                            {{ $heading }}
                        @else
                            <p class="truncate text-[15px] font-semibold text-gray-900 dark:text-zinc-100">{{ $pageTitle }}</p>
                        @endisset
                    </div>
                @endif

                {{-- Without a heading the search aligns to the left. --}}
                <div @class(['hidden min-w-0 md:flex', 'flex-[1.4] justify-center' => $hasHeading, 'flex-1 justify-start' => ! $hasHeading])>
                    @isset($search)
                        <div class="w-full max-w-md">{{ $search }}</div>
                    @endisset
                </div>

                @unless ($hasHeading)
                    <div class="flex-1 md:hidden"></div>
                @endunless

                <div class="flex flex-none items-center justify-end gap-1.5 whitespace-nowrap">
                    {{ $actions ?? '' }}

                    @if ($themeToggle)
                        <acun:theme-toggle />
                    @endif

                    {{ $userMenu ?? '' }}
                </div>
            </header>
        </div>

        {{-- The page sits in the panel container: its edges line up with the top bar's on every screen. --}}
        <main id="main-content" tabindex="-1" class="focus:outline-none panel-gutter w-full min-w-0 flex-1 pt-6 pb-8">
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

    @if ($customizer)
        <acun:theme-customizer />
    @endif

    <acun:toast-container />
    {{-- PRO (acunsoft/acun-ui-pro) adds the unsaved-changes dialog here; skipped without it. --}}
    @includeIf('acun-ui-pro::layout-extras')

    @livewireScripts
    {{-- Code for this page only: @push('page-scripts') in the page (e.g. @vite('resources/js/pages/rapor.js')). --}}
    @stack('page-scripts')
</body>

</html>
