@props([
    // Tab title; falls back to the application name.
    'title' => null,
    // The user's saved preferences (null = value from the browser / default).
    'theme' => null,          // 'light' | 'dark' | 'system'
    // A preset name ('default', 'green', 'red', 'yellow', 'blue', 'gray', 'dark')
    // or a custom colour picked by the user ('#rrggbb').
    'brandColor' => null,
    // The primary colour when the user has none of their own (a preset name or #rrggbb), in place of
    // config('acun-ui.layout.brand_color'): a site theme's colour that a visitor may still change.
    'defaultBrandColor' => null,
    'sidebarMode' => null,    // 'expanded' | 'collapsed'
    'navbarMode' => null,     // 'fixed' | 'static' | 'hidden'
    'skin' => null,           // 'default' | 'bordered'
    'contentWidth' => null,   // 'full' | 'boxed' (at most 1440px, centred)
    'semiDark' => null,       // true | false (dark menu in the light theme)
    // POST URL that saves appearance preferences to the account: { theme } / { sidebar } / { brand } /
    // { navbar } / { skin } / { content } / { semiDark }.
    // When null, preferences are kept only in the browser (localStorage).
    'preferenceUrl' => null,
    // CSP nonce of the boot script (default: Vite::cspNonce()); the same option as @acunUiScripts(['nonce' => …]).
    'nonce' => null,
    // Spaces before every line after the first in the page source: the indentation of the layout's <head> contents.
    'indent' => 4,
])

{{--
    <head> contents: meta tags and the theme/menu state applied BEFORE the first paint
    (the runtime's settings and its boot script). The application's own assets go into the slot:

        <acun:layout.head :theme="$u?->tema_tercihi" preference-url="{{ route('arayuz-tercihi.guncelle') }}">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        </acun:layout.head>

    Order of precedence for every setting: a value the page forces (Acun\Ui\Support\Layout::page()),
    then the user's choice (the props above, else the browser), then config('acun-ui.layout'), then the
    package default.
--}}
@php
    $theme = \Acun\Ui\Support\Layout::forced('theme') ?? $theme;
    $brandColor = \Acun\Ui\Support\Layout::forced('brand_color') ?? $brandColor;
    $sidebarMode = \Acun\Ui\Support\Layout::forced('sidebar_mode') ?? $sidebarMode;
    $navbarMode = \Acun\Ui\Support\Layout::forced('navbar_mode') ?? $navbarMode;
    $skin = \Acun\Ui\Support\Layout::forced('skin') ?? $skin;
    $contentWidth = \Acun\Ui\Support\Layout::forced('content_width') ?? $contentWidth;
    $semiDark = \Acun\Ui\Support\Layout::forced('semi_dark') ?? $semiDark;
    $config = [
        'theme' => $theme,
        'brandColor' => $brandColor,
        'sidebarMode' => $sidebarMode,
        'navbarMode' => $navbarMode,
        'skin' => $skin,
        'contentWidth' => $contentWidth,
        'semiDark' => $semiDark,
        'preferenceUrl' => $preferenceUrl,
        // The application's defaults (config/acun-ui.php); used when neither the server nor the browser has a value.
        'defaults' => array_replace(\Acun\Ui\Support\Layout::clientDefaults(), array_filter(['brand_color' => $defaultBrandColor])),
        'brandPresets' => array_keys(\Acun\Ui\Support\Layout::BRAND_COLORS),
        // A page with forced settings is left with a full page load, so the user's own come back.
        'forced' => \Acun\Ui\Support\Layout::forcesAny(),
        // The select fix (core/js/behavior/select-sync.js) for every box; off unless config says so.
        'selectSync' => (bool) config('acun-ui.select_sync', false),
    ];
@endphp
{{-- Printed through Markup::lines (end of file): one tag per line, no blank lines in the page source. --}}
@php(ob_start())
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<title>{{ $title ?? config('app.name', 'Laravel') }}</title>


{{--
    The settings of the JS runtime (data, not code), then the blocking boot script that
    applies the theme, primary colour, skin and menu width from them before the first paint
    (Acun\Ui\Support\Assets). The runtime itself (theme and menu stores, dialogs, Livewire
    fixes) comes with the application's JS: import '@acunsoft/acun-ui', or @acunUiScripts.
--}}
<script type="application/json" data-acun-ui-config>@json($config)</script>
{!! app(\Acun\Ui\Support\Assets::class)->bootTag(['nonce' => $nonce]) !!}

{{ $slot }}

{{-- Livewire's styles are in panel.css (cached, not inline): marked as printed so Livewire adds none. --}}
@php(app(\Livewire\Mechanisms\FrontendAssets\FrontendAssets::class)->hasRenderedStyles = true)
{!! \Acun\Ui\Support\Markup::lines(ob_get_clean(), str_repeat(' ', (int) $indent)) !!}
