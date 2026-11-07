@props([
    'title' => null,
    // Preferences (see acun:layout.head).
    'mode' => null,
    'theme' => null,
    'scope' => null,
    'brandColor' => null,
    'defaultBrandColor' => null,
    // The page container's width (ac-container, acun:container): default · wide · wider · full · a CSS length. A
    // value the user chose; defaultContentWidth is the page's own default that a choice kept in the browser may
    // change (see acun:layout.head). Without either: config('acun-ui.layout.content_width'), else full.
    'contentWidth' => null,
    'defaultContentWidth' => null,
    // Spaces before the <head> lines in the page source (see acun:layout.head).
    'indent' => 8,
])

{{--
    A page without the panel: the document, <head> and the closing scripts, nothing else. The page
    draws its own content, e.g. a site with its own top bar and footer, a print page, or the
    chrome-less panels of Acun Pro (acun:auth-panel, acun:message-panel). Attributes go on <body>:
        <acun:layout.blank title="Giriş" class="bg-surface">
            <acun:slot:head>@vite(['resources/css/app.css', 'resources/js/app.js'])</acun:slot:head>
            <main class="mx-auto max-w-md p-6">…</main>
        </acun:layout.blank>
    Slots: head (extra <head> contents), (default) the page.
--}}
@php
    $appearance = \Acun\Ui\Support\Appearance::page($mode, $theme);
    // Without JS the page keeps this theme; the boot script sets the saved one before the first paint.
    $documentTheme = \Acun\Ui\Support\Appearance::documentTheme($appearance);
    // The first frame's width on <html> (the boot script applies a choice kept in the browser before the first paint).
    $width = \Acun\Ui\Support\ContentWidth::resolve(\Acun\Ui\Support\Layout::forced('content_width'), $contentWidth, $defaultContentWidth, \Acun\Ui\Support\Layout::default('content_width'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $documentTheme }}"@if ($documentTheme === $appearance['theme']['dark']) class="dark"@endif{!! \Acun\Ui\Support\ContentWidth::rootAttributes($width) !!}>
    <head>
        <acun:layout.head :title="$title" :mode="$appearance['mode']" :theme="$appearance['theme']['key']" :scope="$scope" :brand-color="$brandColor" :default-brand-color="$defaultBrandColor" :content-width="$contentWidth" :default-content-width="$defaultContentWidth" :indent="$indent">
            {{ $head ?? '' }}
        </acun:layout.head>
    </head>
    <body {{ $attributes->class('min-h-dvh text-body') }}>
        {{ $slot }}

        <acun:toast-container />
        {{-- Acun Pro (acunsoft/acun-pro) adds the unsaved-changes dialog here; skipped without it. --}}
        @includeIf('acun-pro::layout-extras')

        @livewireScripts
        {{-- Code for this page only: @push('page-scripts') in the page. --}}
        @stack('page-scripts')
    </body>
</html>
