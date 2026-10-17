@props([
    'title' => null,
    'theme' => null,
    'brandColor' => null,
    // Large code/label (e.g. 404, 403); not shown when omitted.
    'code' => null,
    'heading',
    'description' => null,
])

{{--
    Full-page message layout: error (404, 403, 500), maintenance, coming soon, unauthorized:
    acun:message-panel in a page of its own (acun:layout.blank).
    Slots: head, media (a drawing above the code), action (buttons), illustration (below the buttons),
    (default) extra content.
        <acun:layout.message code="404" heading="Sayfa bulunamadı" description="Aradığınız sayfa taşınmış olabilir.">
            <acun:slot:action><acun:button :href="route('dashboard')">Anasayfaya dön</acun:button></acun:slot:action>
        </acun:layout.message>
--}}
<acun:layout.blank :title="$title ?? $heading" :theme="$theme" :brand-color="$brandColor">
    <acun:slot:head>{{ $head ?? '' }}</acun:slot:head>

    <acun:message-panel :code="$code" :heading="$heading" :description="$description">
        @isset($media)
            <acun:slot:media>{{ $media }}</acun:slot:media>
        @endisset
        @isset($action)
            <acun:slot:action>{{ $action }}</acun:slot:action>
        @endisset
        @isset($illustration)
            <acun:slot:illustration>{{ $illustration }}</acun:slot:illustration>
        @endisset

        {{ $slot }}
    </acun:message-panel>
</acun:layout.blank>
