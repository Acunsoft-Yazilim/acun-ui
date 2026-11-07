@props([
    // The width: default (1280px) · wide (1440px) · wider (1600px) · full (no limit) · a CSS length ('1520px',
    // '90rem'); boxed is wide. null: the page's (<html data-content-width>, the layouts' content-width). Anything else
    // is default.
    'width' => null,
    // The element: div, section, header, footer, main, nav, article or aside.
    'as' => 'div',
])

{{--
    The page container (the ac-container class): centred, the whole width up to the page's content width, with the
    gutter inside it (16px on phones growing to 32px), so a navbar, the page and a footer line up on every screen. A
    band keeps its full-width background outside it and its content inside:

        <header class="border-b border-line bg-surface">
            <acun:container class="flex h-16 items-center gap-3">…</acun:container>
        </header>
        <acun:container as="section" width="wide" class="py-8">…</acun:container>

    The same without the component: <div class="ac-container">. Text, a form or a card that should stay narrow keeps
    its own max-w-* inside it. A grid inside follows the container with container queries (@container).
--}}
@php
    $tag = in_array($as, ['div', 'section', 'header', 'footer', 'main', 'nav', 'article', 'aside'], true) ? $as : 'div';
    $width = \Acun\Ui\Support\ContentWidth::normalize($width);
    $attributes = $attributes->merge(array_filter([
        'class' => 'ac-container',
        'style' => $width === null ? null : '--ac-container-width: '.\Acun\Ui\Support\ContentWidth::css($width),
    ]));
@endphp
<{{ $tag }} {{ $attributes }}>{{ $slot }}</{{ $tag }}>
