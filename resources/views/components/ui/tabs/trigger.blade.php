@props([
    // Required: the value of the acun:tabs.panel it opens.
    'value' => null,
    // A count or a short label after the text.
    'badge' => null,
    // An acun:icon name before the text.
    'icon' => null,
    'disabled' => false,
])
@aware(['variant' => 'underline', 'size' => 'md'])

{{--
    One tab of an acun:tabs.list (role="tab"): a button that opens the acun:tabs.panel
    with the same value.
        <acun:tabs.trigger value="teslimat" icon="truck" badge="8">Teslimat</acun:tabs.trigger>
    Its id and aria-controls (the panel's id) are unique per acun:tabs (Alpine x-id).
    Attributes (class, wire:*, x-*…) go on the button.
--}}
@php
    if ($value === null || $value === '') {
        throw new InvalidArgumentException('acun:tabs.trigger needs a value: the value of the acun:tabs.panel it opens.');
    }

    $value = (string) $value;
    // An id cannot contain spaces.
    $key = preg_replace('/\s+/', '-', $value);
    $pills = $variant === 'pills';

    // md is the classic look (acun:tabs with :tabs); keep the two in sync.
    $sizes = $pills
        ? ['sm' => 'px-3 py-1.5 text-xs gap-1.5', 'md' => 'px-4 py-2 text-sm gap-2', 'lg' => 'px-5 py-2.5 text-base gap-2']
        : ['sm' => 'px-2.5 py-1.5 text-xs gap-1.5', 'md' => 'px-3 py-2 text-sm gap-2', 'lg' => 'px-4 py-2.5 text-base gap-2'];
    $size = array_key_exists($size, $sizes) ? $size : 'md';
    $iconSize = ['sm' => '3.5', 'md' => '4', 'lg' => '5'][$size];

    // cards: the open tab is a card tab joined to its panel below.
    $cards = $variant === 'cards';
    $selectedClass = match (true) {
        $pills => 'bg-brand-600 text-white',
        $cards => 'border-gray-200 bg-white text-gray-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100',
        default => 'border-brand-600 text-brand-700 dark:text-brand-400',
    };
    $idleClass = $pills
        ? 'text-gray-600 hover:bg-gray-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200';

    $badgeSelectedClass = $pills ? 'bg-white/20 text-white' : 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300';
    $badgeIdleClass = $pills
        ? 'bg-gray-200/70 text-gray-700 dark:bg-zinc-700/70 dark:text-zinc-300'
        : 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-300';
@endphp

<button
    type="button"
    role="tab"
    data-slot="tabs-trigger"
    data-value="{{ $value }}"
    :id="$id('tab', @js($key))"
    :aria-controls="$id('tabpanel', @js($key))"
    :aria-selected="selected(@js($value)).toString()"
    :tabindex="selected(@js($value)) ? 0 : -1"
    :class="selected(@js($value)) ? @js($selectedClass) : @js($idleClass)"
    x-on:click="tab = @js($value); $el.scrollIntoView({ block: 'nearest', inline: 'nearest' })"
    @disabled($disabled)
    {{ $attributes->class([
        'inline-flex cursor-pointer items-center font-medium whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-focus disabled:pointer-events-none disabled:opacity-50',
        $sizes[$size],
        'rounded-sm focus-visible:outline-offset-2' => $pills,
        'shrink-0 -mb-px rounded-t-sm border focus-visible:-outline-offset-2' => $cards,
        'shrink-0 border-b-2 focus-visible:-outline-offset-2' => ! $pills && ! $cards,
    ]) }}
>
    @if ($icon)
        <acun:icon :name="$icon" :size="$iconSize" />
    @endif
    {{ $slot }}
    @if ($badge !== null && $badge !== '')
        <span class="rounded-full px-1.5 font-mono text-2xs font-medium tabular-nums" :class="selected(@js($value)) ? @js($badgeSelectedClass) : @js($badgeIdleClass)">{{ $badge }}</span>
    @endif
</button>
