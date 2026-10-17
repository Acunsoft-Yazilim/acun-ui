{{-- A custom sign comes as <acun:slot:indicator>; the @aware below reuses that name. --}}
@php
    $customIndicator = isset($indicator) && $indicator instanceof \Illuminate\Contracts\Support\Htmlable ? $indicator : null;
@endphp
@aware(['name' => null, 'indicator' => 'chevron', 'indicatorPosition' => 'end', 'disabled' => false, 'headingLevel' => 3])

{{--
    The heading of an acun:accordion.item written in parts (an item without title),
    for headings with more than text: an icon, a badge, a count.
        <acun:accordion.item name="iade">
            <acun:accordion.heading>
                <acun:icon name="arrow-uturn-left" size="4" /> İade nasıl yapılır? <acun:badge>Yeni</acun:badge>
            </acun:accordion.heading>
            <acun:accordion.content>…</acun:accordion.content>
        </acun:accordion.item>
    It opens and closes its item (the item's name, read with @aware, so the old name
    acun:accordion-item works too); attributes go on the button.

    The open/closed sign follows the accordion's indicator (chevron · plus · none) and
    indicator-position (end · start); <acun:slot:indicator> replaces it with your own
    (style its open state with group-aria-expanded:). A disabled item disables the button.
--}}
@php
    if ($name === null) {
        throw new InvalidArgumentException('acun:accordion.heading must be inside an acun:accordion-item with a name.');
    }

    if ($indicator instanceof \Illuminate\Contracts\Support\Htmlable) {
        $customIndicator ??= $indicator;
    }

    $key = \Illuminate\Support\Str::slug($name) ?: hash('crc32b', $name);
    $sign = match (true) {
        $customIndicator !== null => '<span class="flex flex-none items-center text-faint" aria-hidden="true">'.$customIndicator->toHtml().'</span>',
        $indicator === 'none' => null,
        // The vertical bar folds away when the section opens: plus becomes minus.
        $indicator === 'plus' => '<svg class="size-4 flex-none text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14"/><path stroke-linecap="round" d="M12 5v14" class="origin-center transition-transform group-aria-expanded:scale-y-0 motion-reduce:transition-none"/></svg>',
        default => '<svg class="size-4 flex-none text-faint transition-transform group-aria-expanded:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>',
    };
    $signAtStart = $sign !== null && $indicatorPosition === 'start';
    // The accordion's heading-level (2–6) fits the headings into the page's outline.
    $headingTag = 'h'.max(2, min(6, (int) $headingLevel));
@endphp

<{{ $headingTag }}>
    <button
        type="button"
        :id="$id('accordion', @js($key)) + '-heading'"
        x-on:click="toggle(@js($name))"
        :aria-expanded="isOpen(@js($name)).toString()"
        :aria-controls="$id('accordion', @js($key))"
        @disabled($disabled)
        {{ $attributes->class('group flex w-full cursor-pointer items-center justify-between gap-3 px-5 py-4 text-left text-sm font-medium text-ink hover:bg-hover/60 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent') }}
    >
        @if ($signAtStart){!! $sign !!}@endif
        <span class="flex min-w-0 flex-1 items-center gap-2" :class="isOpen(@js($name)) && 'text-primary-ink'">{{ $slot }}</span>
        @if ($sign !== null && ! $signAtStart){!! $sign !!}@endif
    </button>
</{{ $headingTag }}>
