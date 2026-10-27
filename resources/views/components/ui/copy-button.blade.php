@props([
    // Text to copy...
    'text' => null,
    // ...or a CSS selector: a form field's value, otherwise the element's text.
    'target' => null,
    // Passed through to acun:button; size="icon" shows only the icon.
    'variant' => 'white',
    'size' => 'md',
    // Visible label (the accessible name when size="icon"). Default: "Kopyala".
    'label' => null,
    // How long the copied / failed state stays, in milliseconds.
    'timeout' => 2000,
])

{{--
    Usage:
    <acun:copy-button text="TR33 0006 1005 1978 6457 8413 26" />
    <acun:copy-button target="#invite-link" variant="ghost" size="icon" />
    <acun:copy-button target="#snippet" x-on:copied="$dispatch('toast', …)" />

    Extra attributes land on the <button>; the wrapper is display: contents and only
    holds the state (data-state) and the aria-live message. The behaviour is the copy plugin's
    (js/plugins/copy.js): no Alpine. The text and the selector are
    read at click time, so a Livewire re-render that changes them is picked up.
    After a successful copy the button dispatches a bubbling `copied` event
    ($event.detail.text).
--}}
@php
    $label ??= __('Copy');
    $iconOnly = $size === 'icon';
    $iconSize = ['lg' => 5, 'icon' => 5][$size] ?? 4;
@endphp

<span
    class="group/copy contents"
    data-acun-copy
    data-state="idle"
    data-timeout="{{ (int) $timeout }}"
    data-copied-label="{{ __('Copied') }}"
    data-failed-label="{{ __('Could not copy') }}"
>
    <acun:button
        :variant="$variant"
        :size="$size"
        :data-copy-text="$text"
        :data-copy-target="$text === null ? $target : null"
        :aria-label="$iconOnly ? $label : null"
        :title="$iconOnly ? $label : null" {{ $attributes }}
    >
        {{-- The state's icon and text (data-state on the wrapper: idle, copied, failed). --}}
        <acun:icon name="document-duplicate" :size="$iconSize" class="group-data-[state=copied]/copy:hidden group-data-[state=failed]/copy:hidden" />
        <acun:icon name="check" :size="$iconSize" class="hidden group-data-[state=copied]/copy:inline" />
        <acun:icon name="x-mark" :size="$iconSize" class="hidden group-data-[state=failed]/copy:inline" />
        @unless ($iconOnly)
            <span class="group-data-[state=copied]/copy:hidden group-data-[state=failed]/copy:hidden">{{ $label }}</span>
            <span class="hidden group-data-[state=copied]/copy:inline">{{ __('Copied') }}</span>
            <span class="hidden group-data-[state=failed]/copy:inline">{{ __('Could not copy') }}</span>
        @endunless
    </acun:button>

    <span class="sr-only" aria-live="polite" data-slot="copy-status"></span>
</span>
