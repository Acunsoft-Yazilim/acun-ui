@props([
    // Where the panel opens: end (default) or start.
    'align' => 'end',
    // The languages offered: code => its own name (['tr' => 'Türkçe', 'en' => 'English']).
    'locales' => [],
    // Where the choice is posted (POST, field "locale"); the application saves it and redirects back.
    // Without it the switcher is not drawn.
    'action' => null,
    // A flag per language when its country code differs from the language code (['en' => 'gb']);
    // false: no flags. Flags are inline SVGs, so they look the same on every system.
    'flags' => [],
    // The current language (app()->getLocale()).
    'current' => null,
])

{{--
    The interface language, for the top bar (next to acun:theme-toggle). The kit only draws it:
    the application lists its languages, receives the choice and saves it (cookie, account):
        <acun:locale-switcher :action="route('locale')" :locales="['tr' => 'Türkçe', 'en' => 'English']" :flags="['en' => 'gb']" />
--}}
@php
    $names = (array) $locales;
    $current ??= app()->getLocale();
    $flagFor = fn (string $locale): ?string => $flags === false ? null : ($flags[$locale] ?? $locale);
    $flagClass = 'h-3.5 w-5 flex-none overflow-hidden rounded-[2px] ring-1 ring-ink/10';
@endphp

@if ($action && count($names) > 1)
    <acun:dropdown :align="$align" {{ $attributes->only('class') }}>
        <acun:dropdown.trigger>
            <button
                type="button"
                title="{{ __('Language') }}"
                class="flex h-9 flex-none cursor-pointer items-center justify-center gap-1 rounded-sm px-2 text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            >
                @if (($currentFlag = $flagFor($current)) && \Acun\Ui\Support\Flags::exists($currentFlag))
                    {{ \Acun\Ui\Support\Flags::svg($currentFlag, $flagClass) }}
                @else
                    <acun:icon name="language" size="5" />
                @endif
                <span class="text-xs font-semibold uppercase">{{ $current }}</span>
                <span class="sr-only">{{ __('Language') }}: {{ $names[$current] ?? $current }}</span>
            </button>
        </acun:dropdown.trigger>

        <acun:dropdown.content class="w-44">
            <form method="POST" action="{{ $action }}">
                @csrf
                @foreach ($names as $locale => $name)
                    <acun:dropdown.item type="submit" name="locale" :value="$locale" :lang="$locale" :aria-current="$locale === $current ? 'true' : null">
                        @if (($flag = $flagFor($locale)) && \Acun\Ui\Support\Flags::exists($flag))
                            {{ \Acun\Ui\Support\Flags::svg($flag, $flagClass) }}
                        @endif
                        <span class="flex-1">{{ $name }}</span>
                        @if ($locale === $current)
                            <acun:icon name="check" size="4" class="text-primary-ink" />
                        @endif
                    </acun:dropdown.item>
                @endforeach
            </form>
        </acun:dropdown.content>
    </acun:dropdown>
@endif
