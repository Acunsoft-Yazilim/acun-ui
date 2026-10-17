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
    <div role="img" aria-label="{{ $label ?? __('QR code for the authenticator app') }}" data-theme="light" class="flex-none rounded-sm bg-surface p-3 ring-1 ring-line [&_svg]:block [&_svg]:size-40">
        {{ $slot }}
    </div>

    @if ($secret)
        <div class="min-w-0 text-sm">
            <p class="text-dim">{{ __('Can\'t scan it? Enter this key in the app:') }}</p>
            <code class="mt-2 block rounded-sm bg-sunken px-3 py-2 font-mono text-sm tracking-wider break-all select-all text-ink">{{ trim(chunk_split((string) $secret, 4, ' ')) }}</code>
        </div>
    @endif
</div>
