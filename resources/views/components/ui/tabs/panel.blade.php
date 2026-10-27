@props([
    // Required: the value of the acun:tabs.trigger that opens it.
    'value' => null,
])
@aware(['variant' => 'underline', 'active' => null])

{{--
    The content of one tab of an acun:tabs written in parts (role="tabpanel"), shown
    while its acun:tabs.trigger is open (hidden otherwise). It is labelled by its trigger
    (aria-labelledby, from the runtime) and can take keyboard focus (tabindex="0"). Attributes (class, wire:key, x-*…) go on the panel.
    With the classic :tabs array, use acun:tab-panel instead.
--}}
@php
    if ($value === null || $value === '') {
        throw new InvalidArgumentException('acun:tabs.panel needs a value: the value of the acun:tabs.trigger that opens it.');
    }

    $value = (string) $value;
    // Without the server knowing the open tab (no active), every panel waits hidden for the runtime.
    $selected = $active !== null && (string) $active === $value;
@endphp

<div
    role="tabpanel"
    data-slot="tabs-panel"
    data-value="{{ $value }}"
    @unless ($selected) hidden @endunless
    {{ $attributes->merge(['tabindex' => '0'])->class($variant === 'cards' ? 'rounded-b-sm border border-t-0 border-line bg-surface p-4' : 'pt-4') }}
>
    {{ $slot }}
</div>
