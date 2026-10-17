@props([
    // Required: the value of the acun:tabs.trigger that opens it.
    'value' => null,
])
@aware(['variant' => 'underline'])

{{--
    The content of one tab of an acun:tabs written in parts (role="tabpanel"), shown
    while its acun:tabs.trigger is open. It is labelled by its trigger and can take
    keyboard focus (tabindex="0"). Attributes (class, wire:key, x-*…) go on the panel.
    With the classic :tabs array, use acun:tab-panel instead.
--}}
@php
    if ($value === null || $value === '') {
        throw new InvalidArgumentException('acun:tabs.panel needs a value: the value of the acun:tabs.trigger that opens it.');
    }

    $value = (string) $value;
    // An id cannot contain spaces.
    $key = preg_replace('/\s+/', '-', $value);
@endphp

<div
    role="tabpanel"
    data-slot="tabs-panel"
    x-show="selected(@js($value))"
    x-cloak
    :id="$id('tabpanel', @js($key))"
    :aria-labelledby="$id('tab', @js($key))"
    {{ $attributes->merge(['tabindex' => '0'])->class($variant === 'cards' ? 'rounded-b-sm border border-t-0 border-line bg-surface p-4' : 'pt-4') }}
>
    {{ $slot }}
</div>
