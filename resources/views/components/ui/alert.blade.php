@props([
    // A palette colour: primary · secondary · success · danger · warning · info · dark (see Acun\Ui\Support\Tone).
    'variant' => 'info',
    // The same as variant.
    'color' => null,
    'title' => null,
    'dismissible' => false,
    'dismissAction' => null,
])

@php
    $variant = \Acun\Ui\Support\Tone::name($color ?? $variant, 'info');
    $variants = [
        'primary' => ['wrap' => 'bg-primary-tint text-primary-tint-ink', 'icon' => 'text-primary-ink'],
        'secondary' => ['wrap' => 'bg-secondary-tint text-secondary-tint-ink', 'icon' => 'text-secondary-line'],
        'success' => ['wrap' => 'bg-success-tint text-success-tint-ink', 'icon' => 'text-success-line'],
        'danger' => ['wrap' => 'bg-danger-tint text-danger-tint-ink', 'icon' => 'text-danger-line'],
        'warning' => ['wrap' => 'bg-warning-tint text-warning-tint-ink', 'icon' => 'text-warning-line'],
        'info' => ['wrap' => 'bg-info-tint text-info-tint-ink', 'icon' => 'text-info-line'],
        'dark' => ['wrap' => 'bg-dark-tint text-dark-tint-ink', 'icon' => 'text-dark-ink'],
    ];
    $style = $variants[$variant];
    // Success: check mark, warning/danger: exclamation mark, the others: "i".
    $iconPath = match ($variant) {
        'success' => 'M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z',
        'warning', 'danger' => 'M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 10-1.5 0v6a.75.75 0 001.5 0V7zM12 16.5a1 1 0 100 2 1 1 0 000-2z',
        default => 'M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z',
    };
    // dismiss-action: this Livewire method is also called on dismiss (so the notice is deleted on the server and does not come back on reload).
    $dismissible = $dismissible || filled($dismissAction);
@endphp
{{--
    Classic: the title prop (or <acun:slot:title>) and the slot as the text.
    In parts, for richer text (a list, a link, buttons under it): when the slot starts with
    acun:alert.title or acun:alert.description, it is rendered as it is next to the icon.
        <acun:alert variant="warning" dismissible>
            <acun:alert.title>3 sipariş gecikmede</acun:alert.title>
            <acun:alert.description>Kadıköy deposunda <strong>3 sipariş</strong> kargo bekliyor.</acun:alert.description>
        </acun:alert>
    Attributes (class, id, wire:key…) go on the alert box in both modes.
--}}
<div
    @if ($dismissible) data-acun-dismissible @endif
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-sm p-4 text-sm ' . $style['wrap']]) }}
>
    <svg class="size-5 flex-none {{ $style['icon'] }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="{{ $iconPath }}"/></svg>

    <div class="flex-1">
        @if (\Acun\Ui\Support\Parts::used($slot, 'alert'))
            {{ $slot }}
        @else
            @if ($title)
                <p class="font-medium">{{ $title }}</p>
            @endif
            <div class="{{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
        @endif
    </div>

    @if ($dismissible)
        <button
            type="button"
            data-acun-close
            @if ($dismissAction) wire:click="{{ $dismissAction }}" @endif
            aria-label="{{ __('Close') }}"
            class="flex-none cursor-pointer text-current opacity-60 hover:opacity-100"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>
