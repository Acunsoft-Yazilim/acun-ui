@props([
    // The setup key, for typing into the app when the code cannot be scanned.
    'secret' => null,
    // The QR code's name for screen readers.
    'label' => null,
])

{{--
    Setting up two-factor sign-in: the QR code (the slot: the SVG your 2FA library makes,
    e.g. Fortify's twoFactorQrCodeSvg()) in a white frame that stays white in the dark theme,
    so phones can scan it, and the setup key in groups of four beside it.
        <acun:two-factor-qr :secret="decrypt($user->two_factor_secret)">{!! $user->twoFactorQrCodeSvg() !!}</acun:two-factor-qr>
--}}
<div {{ $attributes->class('flex flex-col items-center gap-4 sm:flex-row sm:items-start') }}>
    <div role="img" aria-label="{{ $label ?? __('QR code for the authenticator app') }}" class="flex-none rounded-sm bg-white p-3 ring-1 ring-gray-200 [&_svg]:block [&_svg]:size-40 dark:ring-zinc-700">
        {{ $slot }}
    </div>

    @if ($secret)
        <div class="min-w-0 text-sm">
            <p class="text-gray-600 dark:text-zinc-300">{{ __('Can\'t scan it? Enter this key in the app:') }}</p>
            <code class="mt-2 block rounded-sm bg-gray-100 px-3 py-2 font-mono text-sm tracking-wider break-all select-all text-gray-900 dark:bg-zinc-800 dark:text-zinc-100">{{ trim(chunk_split((string) $secret, 4, ' ')) }}</code>
        </div>
    @endif
</div>
