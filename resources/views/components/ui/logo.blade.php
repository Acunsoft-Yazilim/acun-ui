@props([
    // The logo for the light theme.
    'src',
    // The logo for the dark theme (also under a dark menu in the light theme); null: src everywhere.
    'darkSrc' => null,
    // Default: the application's name.
    'alt' => null,
])

{{--
    A logo with a light and a dark version, for the layouts' logo slot:
        <acun:slot:logo><acun:logo src="/img/logo.svg" dark-src="/img/logo-white.svg" class="h-8" /></acun:slot:logo>
    The dark one shows wherever the dark: variant applies: the dark theme and a dark menu
    (semi-dark) in the light theme. Attributes (class: the height) go on the wrapper.
--}}
@php($alt ??= config('app.name'))

@if ($darkSrc)
    <span {{ $attributes->class('inline-flex') }}>
        <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-auto dark:hidden">
        <img src="{{ $darkSrc }}" alt="{{ $alt }}" class="hidden h-full w-auto dark:block">
    </span>
@else
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->class('w-auto') }}>
@endif
