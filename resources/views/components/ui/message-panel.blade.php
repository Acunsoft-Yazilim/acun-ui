@props([
    // Large code/label (e.g. 404, 403); not shown when omitted.
    'code' => null,
    'heading',
    'description' => null,
])

{{--
    A full-screen message without its document: error (404, 403, 500), maintenance, coming soon.
    For a page that already has one (acun:layout.blank, or the application's own layout);
    acun:layout.message is this panel in a page.
        <acun:message-panel code="404" heading="Sayfa bulunamadı" description="Aradığınız sayfa taşınmış olabilir.">
            <acun:slot:action><acun:button :href="route('dashboard')">Anasayfaya dön</acun:button></acun:slot:action>
        </acun:message-panel>
    Slots: media (a drawing above the code), action (buttons), illustration (below the buttons),
    (default) extra content.
--}}
<main {{ $attributes->class('flex min-h-dvh flex-col items-center justify-center bg-gray-50 px-4 py-12 text-center dark:bg-zinc-950') }}>
    @isset($media)
        <div class="mb-6 w-full max-w-xs">{{ $media }}</div>
    @endisset
    @if ($code)
        <p @class(['font-brand leading-none text-brand-600 dark:text-brand-400', 'text-5xl sm:text-6xl' => isset($media), 'text-7xl sm:text-8xl' => ! isset($media)])>{{ $code }}</p>
    @endif
    <h1 class="mt-4 text-2xl font-semibold text-balance text-gray-900 dark:text-zinc-100">{{ $heading }}</h1>
    @if ($description)
        <p class="mt-2 max-w-md text-sm leading-6 text-gray-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    {{ $slot }}

    @isset($action)
        <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $action }}</div>
    @endisset

    @isset($illustration)
        <div class="mt-10 w-full max-w-md">{{ $illustration }}</div>
    @endisset
</main>
