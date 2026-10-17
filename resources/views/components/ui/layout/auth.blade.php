@props([
    'title' => null,
    'homeUrl' => null,
    'theme' => null,
    'brandColor' => null,
    // cover: illustration on the left, form on the right · basic: a single centred card
    'variant' => 'cover',
])

{{--
    Login, registration, password reset and two-factor verification pages: acun:auth-panel
    in a page of its own (acun:layout.blank).
    Slots: head, logo, illustration (cover; defaults to the built-in drawing),
    (default) form content.
--}}
<acun:layout.blank :title="$title" :theme="$theme" :brand-color="$brandColor">
    <acun:slot:head>{{ $head ?? '' }}</acun:slot:head>

    <acun:auth-panel :variant="$variant" :home-url="$homeUrl">
        @isset($logo)
            <acun:slot:logo>{{ $logo }}</acun:slot:logo>
        @endisset
        @isset($illustration)
            <acun:slot:illustration>{{ $illustration }}</acun:slot:illustration>
        @endisset

        {{ $slot }}
    </acun:auth-panel>
</acun:layout.blank>
