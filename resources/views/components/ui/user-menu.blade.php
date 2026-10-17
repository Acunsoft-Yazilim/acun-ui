@props([
    'name',
    // Derived from the name when omitted ("Ada Ertepınar" → "AE").
    'initials' => null,
])

@php
    $initials ??= \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

{{--
    User dropdown menu in the navbar. Items come from the slot:
        <acun:user-menu :name="auth()->user()->name">
            <acun:dropdown-item :href="route('profile.edit')" wire:navigate>Ayarlar</acun:dropdown-item>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <acun:dropdown-item type="submit">Çıkış Yap</acun:dropdown-item>
            </form>
        </acun:user-menu>
--}}
<acun:dropdown align="end">
    <acun:slot:trigger>
        {{-- Below `sm` only the initials are visible (the top bar has no room for more), so the name is given explicitly. --}}
        <button type="button" data-test="sidebar-menu-button" {{ $attributes->merge(['aria-label' => __('User menu: :name', ['name' => $name]), 'class' => 'flex items-center gap-2 rounded-sm p-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:pr-2.5 dark:text-zinc-200 dark:hover:bg-zinc-800']) }}>
            <span class="flex size-8 flex-none items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">{{ $initials }}</span>
            <span class="hidden whitespace-nowrap sm:inline-block">{{ $name }}</span>
            <svg class="hidden size-4 text-gray-400 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
            </svg>
        </button>
    </acun:slot:trigger>

    {{ $slot }}
</acun:dropdown>
