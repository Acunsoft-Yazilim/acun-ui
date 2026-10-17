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
    holds the Alpine state and the aria-live message. The text and the selector are
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
    class="contents"
    x-data="{
        copyState: 'idle',
        copyTimer: null,
        copySource(button) {
            if (button.dataset.copyText !== undefined) return button.dataset.copyText;
            try {
                const el = document.querySelector(button.dataset.copyTarget);
                if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement) return el.value;
                return el ? el.textContent.trim() : null;
            } catch (error) {
                return null;
            }
        },
        async copy(button) {
            const text = this.copySource(button);
            let copied = false;
            if (text !== null) {
                try {
                    if (! window.isSecureContext || ! navigator.clipboard) throw new Error('Clipboard API unavailable');
                    await navigator.clipboard.writeText(text);
                    copied = true;
                } catch (error) {
                    copied = this.copyFallback(text, button);
                }
            }
            this.copyState = copied ? 'copied' : 'failed';
            clearTimeout(this.copyTimer);
            this.copyTimer = setTimeout(() => this.copyState = 'idle', {{ (int) $timeout }});
            if (copied) button.dispatchEvent(new CustomEvent('copied', { detail: { text }, bubbles: true }));
        },
        {{-- Without the Clipboard API (plain http, some iframes): select a temporary textarea and use execCommand. --}}
        copyFallback(text, button) {
            const area = document.createElement('textarea');
            area.value = text;
            area.readOnly = true;
            area.tabIndex = -1;
            area.setAttribute('aria-hidden', 'true');
            area.style.cssText = 'position: fixed; top: 0; left: 0; width: 1px; height: 1px; padding: 0; border: 0; opacity: 0;';
            {{-- Next to the button, so a focus trap around it (modal, drawer) does not pull focus away. --}}
            button.after(area);
            area.select();
            area.setSelectionRange(0, text.length);
            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (error) {}
            area.remove();
            button.focus();
            return copied;
        },
    }"
    {{-- Listened on the wrapper, so an x-on:click / wire:click given to the button runs as well. --}}
    x-on:click="copy($el.querySelector('button'))"
>
    <acun:button
        :variant="$variant"
        :size="$size"
        :data-copy-text="$text"
        :data-copy-target="$text === null ? $target : null"
        :aria-label="$iconOnly ? $label : null"
        :title="$iconOnly ? $label : null" {{ $attributes }}
    >
        <acun:icon name="document-duplicate" :size="$iconSize" x-show="copyState === 'idle'" />
        <acun:icon name="check" :size="$iconSize" x-show="copyState === 'copied'" x-cloak />
        <acun:icon name="x-mark" :size="$iconSize" x-show="copyState === 'failed'" x-cloak />
        @unless ($iconOnly)
            <span x-show="copyState === 'idle'">{{ $label }}</span>
            <span x-show="copyState === 'copied'" x-cloak>{{ __('Copied') }}</span>
            <span x-show="copyState === 'failed'" x-cloak>{{ __('Could not copy') }}</span>
        @endunless
    </acun:button>

    <span class="sr-only" aria-live="polite" x-text="copyState === 'copied' ? @js(__('Copied')) : (copyState === 'failed' ? @js(__('Could not copy')) : '')"></span>
</span>
