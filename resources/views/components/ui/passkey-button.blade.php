@props([
    // register: adds a passkey to the signed-in account · login: signs in with one.
    'mode' => 'login',
    // The application's two steps: the WebAuthn options (JSON) and the check of the answer.
    // Either URLs (POST with JSON and the CSRF token) …
    'optionsUrl' => null,
    'actionUrl' => null,
    // … or methods of the Livewire component around the button.
    'optionsMethod' => null,
    'actionMethod' => null,
    // GET instead of POST for the options URL.
    'optionsGet' => false,
    // register: a field whose value is sent as the passkey's name, e.g. '#passkey-name'.
    'nameField' => null,
    // Defaults: "Add a passkey" / "Sign in with a passkey" in the current language.
    'label' => null,
    // acun:button's variant and size.
    'variant' => 'secondary',
    'size' => 'md',
])

{{--
    A passkey button. The kit runs the browser's part (navigator.credentials, the core runtime's
    acunPasskey); the options and the check of the answer are the application's WebAuthn code:

        <acun:passkey-button mode="login" options-url="/passkeys/login/options" action-url="/passkeys/login" />
        <acun:passkey-button mode="register" options-method="passkeyOptions" action-method="addPasskey" />

    It sends acun:passkey:done ({ mode, result }) and acun:passkey:failed ({ mode, error, message }).
--}}
@php
    $label ??= $mode === 'register' ? __('Add a passkey') : __('Sign in with a passkey');
    $config = [
        'mode' => $mode === 'register' ? 'register' : 'login',
        'optionsUrl' => $optionsUrl,
        'actionUrl' => $actionUrl,
        'optionsMethod' => $optionsMethod,
        'actionMethod' => $actionMethod,
        'optionsGet' => (bool) $optionsGet,
        'nameField' => $nameField,
    ];
@endphp

<div
    x-data="acunPasskey(@js($config))"
    data-acun-component="passkey"
    data-error-text="{{ __('The passkey could not be used. Please try again.') }}"
    {{ $attributes->only('class')->class('space-y-2') }}
>
    <acun:button
        :variant="$variant"
        :size="$size"
        x-on:click="run()"
        x-bind:disabled="busy || ! supported"
        x-bind:aria-busy="busy ? 'true' : 'false'"
        {{ $attributes->except('class') }}
    >
        <acun:icon name="finger-print" size="4" />
        <span>{{ $label }}</span>
    </acun:button>

    <p x-show="! supported" x-cloak class="text-xs text-gray-500 dark:text-zinc-400">{{ __('This browser does not support passkeys.') }}</p>
    <p x-show="error" x-text="error" x-cloak role="alert" class="text-xs text-red-600 dark:text-red-400"></p>
</div>
