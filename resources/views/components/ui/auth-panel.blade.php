@props([
    'homeUrl' => null,
    // cover: illustration on the left, form on the right · basic: a single centred card
    'variant' => 'cover',
])

{{--
    The login / registration screen without its document: for a page that already has one
    (acun:layout.blank, or the application's own layout). acun:layout.auth is this panel in a page.
        <acun:auth-panel variant="basic" :home-url="route('home')">
            <acun:slot:logo><x-logo /></acun:slot:logo>
            …form…
        </acun:auth-panel>
    Slots: logo (basic: defaults to the application name), illustration (cover; defaults to the built-in drawing),
    (default) form content.
--}}
@php
    $homeUrl = \Acun\Ui\Support\Url::safe((string) ($homeUrl ?? url('/'))) ?? '#';
@endphp
{{-- One root element for both looks, so a Livewire page whose view starts with the panel keeps its wire:id on it. --}}
<div {{ $attributes->class([
    'min-h-dvh',
    // basic: a fine dotted grid that fades towards the edges
    'bg-gray-50 bg-[radial-gradient(var(--color-gray-300)_1px,transparent_1px)] [background-size:22px_22px] dark:bg-zinc-950 dark:bg-[radial-gradient(var(--color-zinc-800)_1px,transparent_1px)]' => $variant === 'basic',
    'relative bg-white dark:bg-zinc-950' => $variant !== 'basic',
]) }}>
    @if ($variant === 'basic')
        <main class="flex min-h-dvh items-center justify-center bg-[radial-gradient(ellipse_at_center,transparent_20%,var(--color-gray-50)_75%)] px-4 py-12 dark:bg-[radial-gradient(ellipse_at_center,transparent_20%,var(--color-zinc-950)_75%)]">
            <div class="w-full max-w-[420px]">
                <div class="mb-6 flex justify-center">
                    <a href="{{ $homeUrl }}" wire:navigate aria-label="{{ __('Home') }}">
                        @isset($logo)
                            {{ $logo }}
                        @else
                            <span class="font-brand text-xl font-bold tracking-tight text-gray-900 dark:text-zinc-100">{{ config('app.name') }}</span>
                        @endisset
                    </a>
                </div>

                <div class="rounded-sm bg-white px-6 py-8 shadow-lg sm:px-8 dark:bg-zinc-900">
                    {{ $slot }}
                </div>
            </div>
        </main>
    @else
        @isset($logo)
            <div class="absolute top-6 left-6 z-10 flex justify-start sm:top-8 sm:left-10">
                <a href="{{ $homeUrl }}" wire:navigate aria-label="{{ __('Home') }}">{{ $logo }}</a>
            </div>
        @endisset

        <main class="grid min-h-dvh lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <section class="relative hidden flex-col bg-gray-50 p-10 dark:bg-zinc-900 lg:flex" aria-hidden="true">
                <div class="flex flex-1 items-center justify-center px-8 py-12">
                    @isset($illustration)
                        {{ $illustration }}
                    @else
                        <svg class="h-auto w-full max-w-2xl" viewBox="0 0 520 420" fill="none">
                            <circle cx="118" cy="105" r="54" class="fill-brand-50 dark:fill-brand-500/10"/>
                            <circle cx="417" cy="306" r="68" class="fill-brand-50 dark:fill-brand-500/10"/>
                            <path d="M90 328c53-43 75-20 111-42 45-27 51-82 105-99 53-17 75 14 126-26" class="stroke-brand-200 dark:stroke-brand-500/40" stroke-width="3" stroke-linecap="round" stroke-dasharray="8 12"/>
                            <rect x="104" y="72" width="310" height="240" rx="18" class="fill-white stroke-brand-300 dark:fill-zinc-800 dark:stroke-brand-500/60" stroke-width="4"/>
                            <rect x="122" y="92" width="274" height="178" rx="8" class="fill-gray-50 dark:fill-zinc-900"/>
                            <rect x="143" y="116" width="112" height="12" rx="6" class="fill-brand-200 dark:fill-brand-500/40"/>
                            <rect x="143" y="144" width="222" height="10" rx="5" class="fill-gray-200 dark:fill-zinc-700"/>
                            <rect x="143" y="166" width="185" height="10" rx="5" class="fill-gray-200 dark:fill-zinc-700"/>
                            <rect x="143" y="205" width="78" height="36" rx="8" class="fill-brand-600"/>
                            <path d="M221 312h76l12 34h-100l12-34Z" class="fill-brand-300 dark:fill-brand-500/60"/>
                            <rect x="172" y="344" width="174" height="12" rx="6" class="fill-brand-500"/>
                            <circle cx="364" cy="91" r="42" class="fill-brand-600"/>
                            <path d="m344 91 13 13 27-29" class="stroke-white" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="104" cy="288" r="35" class="fill-brand-100 dark:fill-brand-500/20"/>
                            <path d="M93 289h22M104 278v22" class="stroke-brand-600" stroke-width="5" stroke-linecap="round"/>
                        </svg>
                    @endisset
                </div>
            </section>

            <section class="flex min-w-0 items-center justify-center bg-white px-6 pt-28 pb-12 dark:bg-zinc-900 sm:px-10 xl:px-12">
                <div class="w-full max-w-[400px]">
                    {{ $slot }}
                </div>
            </section>
        </main>
    @endif
</div>
