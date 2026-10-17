@props([
    // The account's passkeys as the application stores them (arrays or models):
    // id, name, created_at, last_used_at. Dates may be DateTime objects or ready text.
    'passkeys' => [],
    // The Livewire method that removes one (called with its id); null: no remove button.
    'remove' => null,
    // Default: "Remove this passkey? You will no longer be able to sign in with it."
    'removeConfirmation' => null,
])

{{--
    The passkeys of the signed-in account, for a security settings page. The list only draws
    them; removing is the application's own method, which checks the user on the server:

        <acun:passkey-list :passkeys="$user->passkeys" remove="removePasskey" />
--}}
@php
    $removeConfirmation ??= __('Remove this passkey? You will no longer be able to sign in with it.');
    $day = fn ($value) => $value instanceof \DateTimeInterface
        ? \Illuminate\Support\Carbon::instance($value)->locale(app()->getLocale())->translatedFormat('j M Y')
        : ($value ?: null);
    $passkeys = collect($passkeys);
@endphp

<ul role="list" {{ $attributes->class('divide-y divide-gray-200 rounded-sm border border-gray-200 bg-white dark:divide-zinc-800 dark:border-zinc-700 dark:bg-zinc-900') }}>
    @forelse ($passkeys as $passkey)
        @php
            $id = data_get($passkey, 'id');
            $added = $day(data_get($passkey, 'created_at'));
            $used = $day(data_get($passkey, 'last_used_at'));
        @endphp
        <li class="flex items-center gap-3 px-4 py-3" wire:key="passkey-{{ $id }}">
            <span class="flex size-9 flex-none items-center justify-center rounded-full bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-300" aria-hidden="true">
                <acun:icon name="finger-print" size="4.5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-zinc-100">{{ data_get($passkey, 'name') ?: __('Passkey') }}</p>
                <p class="text-xs text-gray-500 dark:text-zinc-400">
                    @if ($added){{ __('Added :date', ['date' => $added]) }} · @endif{{ $used ? __('Last used :date', ['date' => $used]) : __('Never used') }}
                </p>
            </div>
            @if ($remove)
                <acun:button variant="danger" appearance="outline" size="sm"
                    wire:click="{{ $remove }}({{ \Illuminate\Support\Js::from($id) }})"
                    wire:confirm="{{ $removeConfirmation }}"
                    :aria-label="__('Remove :name', ['name' => data_get($passkey, 'name') ?: __('Passkey')])">
                    {{ __('Remove') }}
                </acun:button>
            @endif
        </li>
    @empty
        <li class="px-4 py-6 text-center text-sm text-gray-500 dark:text-zinc-400">
            {{ $slot->isNotEmpty() ? $slot : __('No passkeys yet.') }}
        </li>
    @endforelse
</ul>
