@props([
    // Required: the value of the acun:tabs.panel it opens.
    'value' => null,
    // A count or a short label after the text.
    'badge' => null,
    // An acun:icon name before the text.
    'icon' => null,
    'disabled' => false,
])
@aware(['variant' => 'underline', 'size' => 'md', 'active' => null])

{{--
    One tab of an acun:tabs.list (role="tab"): a button that opens the acun:tabs.panel
    with the same value.
        <acun:tabs.trigger value="teslimat" icon="truck" badge="8">Teslimat</acun:tabs.trigger>
    Its id and aria-controls (the panel's id) are unique per acun:tabs (from the runtime).
    The open tab carries aria-selected="true"; its look is the aria-selected: classes.
    Attributes (class, wire:*, x-*…) go on the button.
--}}
@php
    if ($value === null || $value === '') {
        throw new InvalidArgumentException('acun:tabs.trigger needs a value: the value of the acun:tabs.panel it opens.');
    }

    $value = (string) $value;
    $pills = $variant === 'pills';

    // md is the classic look (acun:tabs with :tabs); keep the two in sync.
    $sizes = $pills
        ? ['sm' => 'px-3 py-1.5 text-xs gap-1.5', 'md' => 'px-4 py-2 text-sm gap-2', 'lg' => 'px-5 py-2.5 text-base gap-2']
        : ['sm' => 'px-2.5 py-1.5 text-xs gap-1.5', 'md' => 'px-3 py-2 text-sm gap-2', 'lg' => 'px-4 py-2.5 text-base gap-2'];
    $size = array_key_exists($size, $sizes) ? $size : 'md';
    $iconSize = ['sm' => '3.5', 'md' => '4', 'lg' => '5'][$size];

    // cards: the open tab is a card tab joined to its panel below.
    $cards = $variant === 'cards';
    // The open tab's look follows aria-selected (the tabs plugin sets it): no script swaps classes. Written out
    // in full so Tailwind finds them (the selected, idle and badge looks of each variant).
    $selectedClass = match (true) {
        $pills => 'aria-selected:bg-primary aria-selected:text-on-primary',
        $cards => 'aria-selected:border-line aria-selected:bg-surface aria-selected:text-ink',
        default => 'aria-selected:border-primary-ink aria-selected:text-primary-tint-ink',
    };
    $idleClass = $pills
        ? 'not-aria-selected:text-dim not-aria-selected:hover:bg-hover'
        : 'not-aria-selected:border-transparent not-aria-selected:text-muted not-aria-selected:hover:text-body';

    $badgeSelectedClass = $pills ? 'group-aria-selected:bg-on-primary/20 group-aria-selected:text-on-primary' : 'group-aria-selected:bg-primary-tint group-aria-selected:text-primary-tint-ink';
    $badgeIdleClass = $pills
        ? 'bg-track/70 text-body'
        : 'bg-sunken text-dim';

    $selected = $active !== null && (string) $active === $value;
@endphp

<button
    type="button"
    role="tab"
    data-slot="tabs-trigger"
    data-value="{{ $value }}"
    aria-selected="{{ $selected ? 'true' : 'false' }}"
    tabindex="{{ $selected ? '0' : '-1' }}"
    @disabled($disabled)
    {{ $attributes->class([
        'group inline-flex cursor-pointer items-center font-medium whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-focus disabled:pointer-events-none disabled:opacity-50',
        $sizes[$size],
        $selectedClass,
        $idleClass,
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
        <span @class(['rounded-full px-1.5 font-mono text-2xs font-medium tabular-nums', $badgeIdleClass, $badgeSelectedClass])>{{ $badge }}</span>
    @endif
</button>
