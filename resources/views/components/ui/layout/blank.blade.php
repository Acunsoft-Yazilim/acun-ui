@props([
    'title' => null,
    // Preferences (see acun:layout.head).
    'theme' => null,
    'brandColor' => null,
    'defaultBrandColor' => null,
    // Spaces before the <head> lines in the page source (see acun:layout.head).
    'indent' => 8,
])

{{--
    A page without the panel: the document, <head> and the closing scripts, nothing else. The page
    draws its own content, e.g. a site with its own top bar and footer, a print page, or the
    chrome-less panels of Acun Pro (acun:auth-panel, acun:message-panel). Attributes go on <body>:
        <acun:layout.blank title="Giriş" class="bg-white">
            <acun:slot:head>@vite(['resources/css/app.css', 'resources/js/app.js'])</acun:slot:head>
            <main class="mx-auto max-w-md p-6">…</main>
        </acun:layout.blank>
    Slots: head (extra <head> contents), (default) the page.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <acun:layout.head :title="$title" :theme="$theme" :brand-color="$brandColor" :default-brand-color="$defaultBrandColor" :indent="$indent">
            {{ $head ?? '' }}
        </acun:layout.head>
    </head>
    <body {{ $attributes->class('min-h-dvh text-gray-700 dark:text-zinc-200') }}>
        {{ $slot }}

        <acun:toast-container />
        {{-- Acun Pro (acunsoft/acun-pro) adds the unsaved-changes dialog here; skipped without it. --}}
        @includeIf('acun-pro::layout-extras')

        @livewireScripts
        {{-- Code for this page only: @push('page-scripts') in the page. --}}
        @stack('page-scripts')
    </body>
</html>
