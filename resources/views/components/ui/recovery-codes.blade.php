@props([
    // The account's recovery codes (shown once, after two-factor sign-in is turned on).
    'codes' => [],
    // The name of the downloaded text file.
    'filename' => 'recovery-codes.txt',
])

{{--
    The recovery codes of two-factor sign-in, with Copy and Download (a .txt file):
        <acun:recovery-codes :codes="$user->recoveryCodes()" />
    The codes are the application's (e.g. Fortify); the kit only shows them. Copy is
    acun:copy-button (the result is announced; a fallback without the Clipboard API);
    Download saves them as a text file in the browser.
--}}
@php($text = implode("\n", array_values(array_map('strval', (array) $codes))))
<div {{ $attributes->class('space-y-3') }}>
    <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-1.5 rounded-sm bg-sunken/60 p-4 font-mono text-sm tracking-wider text-ink sm:grid-cols-2">
        @foreach ($codes as $code)
            <li class="select-all">{{ $code }}</li>
        @endforeach
    </ul>

    <div class="flex flex-wrap gap-2">
        {{-- The copy button announces the result and falls back to a selection where the Clipboard API is missing. --}}
        <acun:copy-button :text="$text" variant="white" size="sm" />
        <acun:button variant="white" size="sm" data-acun-download-text :data-text="$text" :data-filename="$filename">
            <acun:icon name="arrow-down-tray" size="4" />
            {{ __('Download') }}
        </acun:button>
    </div>
</div>
