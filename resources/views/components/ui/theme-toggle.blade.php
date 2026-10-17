{{--
    Light/dark theme button in the navbar: one click switches to the dark mode, another
    back to light (see toggle() of the appearance store in acun:layout.head - <html
    data-theme> changes instantly and the choice is saved to the account). The icon
    and screen reader text depend on the `dark:` variant, not on Alpine: data-theme and
    the 'dark' class on <html> are written before the page is painted, so the correct
    icon shows even on the first frame.
--}}
<button
    type="button"
    x-data
    x-on:click="$store.appearance.toggle()"
    title="{{ __('Change theme') }}"
    data-test="theme-toggle"
    {{ $attributes->merge(['class' => 'flex size-9 flex-none cursor-pointer items-center justify-center rounded-sm text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus']) }}
>
    <span class="sr-only dark:hidden">{{ __('Switch to the dark theme') }}</span>
    <span class="sr-only hidden dark:inline">{{ __('Switch to the light theme') }}</span>

    {{-- Moon in the light theme (switch to dark), sun in the dark theme (switch to light). --}}
    <svg class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
    </svg>
    <svg class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
    </svg>
</button>
