@props([
    // The account's recovery codes (shown once, after two-factor sign-in is turned on).
    'codes' => [],
    // The name of the downloaded text file.
    'filename' => 'recovery-codes.txt',
])

{{--
    The recovery codes of two-factor sign-in, with Copy and Download (a .txt file):
        <acun:recovery-codes :codes="$user->recoveryCodes()" />
    The codes are the application's (e.g. Fortify); the kit only shows them. The Copy result
    is announced to screen readers.
--}}
<div
    x-data="{
        codes: @js(array_values(array_map('strval', (array) $codes))),
        copied: false,
        async copy() {
            try {
                await navigator.clipboard.writeText(this.codes.join('\n'));
                this.copied = true;
                setTimeout(() => { this.copied = false }, 2000);
            } catch {}
        },
        download() {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([this.codes.join('\n') + '\n'], { type: 'text/plain' }));
            link.download = @js($filename);
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 0);
        },
    }"
    {{ $attributes->class('space-y-3') }}
>
    <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-1.5 rounded-sm bg-sunken/60 p-4 font-mono text-sm tracking-wider text-ink sm:grid-cols-2">
        @foreach ($codes as $code)
            <li class="select-all">{{ $code }}</li>
        @endforeach
    </ul>

    <div class="flex flex-wrap gap-2">
        <acun:button variant="white" size="sm" x-on:click="copy()">
            <acun:icon name="clipboard-document" size="4" />
            <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
        </acun:button>
        <acun:button variant="white" size="sm" x-on:click="download()">
            <acun:icon name="arrow-down-tray" size="4" />
            {{ __('Download') }}
        </acun:button>
    </div>

    <p class="sr-only" role="status" x-text="copied ? @js(__('The codes were copied.')) : ''"></p>
</div>
