@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
    // Compatibility with the form modal API: appearance="outline" → outline-* variant,
    // appearance="text" → ghost. In new code, pass the variant directly.
    'appearance' => null,
    'loading' => false,
    // With href: opens in a new tab (target="_blank" rel="noopener"), with an icon and a screen-reader note.
    'external' => false,
    // A shadow in the button's colour under a solid button (primary, secondary, success,
    // danger, warning, info, dark, light); it goes away on hover.
    'shadow' => false,
    // A count after the text (unread messages, items in the cart), framed with the button's text colour.
    // It has no fill of its own: a tint of the text colour took its contrast below 4.5:1 (3.51-3.79 on
    // the solid buttons), so the number keeps the button's own text contrast, on hover too.
    'badge' => null,
])

@php
    // A link from data may hold javascript: (see Acun\Ui\Support\Url): it becomes "#".
    $href = $href === null ? null : (\Acun\Ui\Support\Url::safe((string) $href) ?? '#');
    // Every button has a 1px border (transparent where the variant draws none), so all
    // variants of a size are the same height; md is 40px, as acun:input.
    $sizes = [
        'sm' => 'px-2.5 py-[5px] text-xs gap-1.5',
        'md' => 'px-3.5 py-2 text-sm gap-2',
        'lg' => 'px-5 py-[11px] text-base gap-2',
        // Icon-only: square, no text label (pair with aria-label + title).
        'icon' => 'p-[5px]',
    ];

    // Every colour has a solid, an outline-* and a soft-* variant: primary, secondary, success,
    // danger, warning, info, dark. Hover, press and the coloured shadow never apply while the
    // button is disabled (hover:not-disabled:, and :not(:disabled) in panel.css).
    $variants = [
        // Solid: fill, border, hover and shadow colours come from panel.css (.ui-btn-*,
        // --btn-* variables), so a theme changes them in one place.
        'primary' => 'ui-btn ui-btn-primary focus-visible:outline-focus',
        'success' => 'ui-btn ui-btn-success focus-visible:outline-emerald-500',
        'danger' => 'ui-btn ui-btn-danger focus-visible:outline-red-500',
        'warning' => 'ui-btn ui-btn-warning focus-visible:outline-amber-500',
        'info' => 'ui-btn ui-btn-info focus-visible:outline-sky-500',
        'secondary' => 'ui-btn ui-btn-secondary focus-visible:outline-secondary-500',
        'dark' => 'ui-btn ui-btn-dark focus-visible:outline-dark dark:focus-visible:outline-zinc-400',
        'light' => 'ui-btn ui-btn-light focus-visible:outline-secondary-400',

        // Outline: border and text in the colour, filled on hover. Text uses the darker
        // shade of the colour so it stays readable on white. The brand hover fill is
        // brand-600 in both themes (as for soft): white text reaches 4.5:1 on it for any
        // brand colour, which brand-500 does not (#2563eb: 3.89:1).
        'outline' => 'border-brand-600 text-brand-600 hover:not-disabled:bg-brand-600 hover:not-disabled:text-white focus-visible:outline-focus dark:border-brand-400 dark:text-brand-400 dark:hover:not-disabled:bg-brand-600 dark:hover:not-disabled:text-white',
        'outline-success' => 'border-emerald-500 text-emerald-600 hover:not-disabled:bg-emerald-solid hover:not-disabled:text-white focus-visible:outline-emerald-500 dark:border-emerald-400 dark:text-emerald-400 dark:hover:not-disabled:bg-emerald-solid dark:hover:not-disabled:text-white',
        'outline-danger' => 'border-red-500 text-red-600 hover:not-disabled:bg-red-600 hover:not-disabled:text-white focus-visible:outline-red-500 dark:border-red-400 dark:text-red-400 dark:hover:not-disabled:bg-red-600 dark:hover:not-disabled:text-white',
        'outline-warning' => 'border-amber-500 text-amber-600 hover:not-disabled:bg-amber-solid hover:not-disabled:text-white focus-visible:outline-amber-500 dark:border-amber-400 dark:text-amber-400 dark:hover:not-disabled:bg-amber-solid dark:hover:not-disabled:text-white',
        'outline-info' => 'border-sky-500 text-sky-600 hover:not-disabled:bg-sky-solid hover:not-disabled:text-white focus-visible:outline-sky-500 dark:border-sky-400 dark:text-sky-400 dark:hover:not-disabled:bg-sky-solid dark:hover:not-disabled:text-white',
        'outline-secondary' => 'border-secondary-500 text-secondary-600 hover:not-disabled:bg-secondary-500 hover:not-disabled:text-white focus-visible:outline-secondary-500 dark:border-secondary-400 dark:text-secondary-300 dark:hover:not-disabled:bg-secondary-500 dark:hover:not-disabled:text-white',
        'outline-dark' => 'border-dark text-dark hover:not-disabled:bg-dark hover:not-disabled:text-white focus-visible:outline-dark dark:border-zinc-300 dark:text-zinc-300 dark:hover:not-disabled:bg-zinc-300 dark:hover:not-disabled:text-zinc-900',

        // Soft: a light tint of the colour that fills in on hover.
        'soft' => 'border-transparent bg-brand-100 text-brand-700 hover:not-disabled:bg-brand-600 hover:not-disabled:text-white focus-visible:outline-focus dark:bg-brand-500/15 dark:text-brand-400 dark:hover:not-disabled:bg-brand-600 dark:hover:not-disabled:text-white',
        'soft-success' => 'border-transparent bg-emerald-100 text-emerald-700 hover:not-disabled:bg-emerald-solid hover:not-disabled:text-white focus-visible:outline-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400 dark:hover:not-disabled:bg-emerald-solid dark:hover:not-disabled:text-white',
        'soft-danger' => 'border-transparent bg-red-100 text-red-700 hover:not-disabled:bg-red-600 hover:not-disabled:text-white focus-visible:outline-red-500 dark:bg-red-500/15 dark:text-red-400 dark:hover:not-disabled:bg-red-600 dark:hover:not-disabled:text-white',
        'soft-warning' => 'border-transparent bg-amber-100 text-amber-700 hover:not-disabled:bg-amber-solid hover:not-disabled:text-white focus-visible:outline-amber-500 dark:bg-amber-500/15 dark:text-amber-400 dark:hover:not-disabled:bg-amber-solid dark:hover:not-disabled:text-white',
        'soft-info' => 'border-transparent bg-sky-100 text-sky-700 hover:not-disabled:bg-sky-solid hover:not-disabled:text-white focus-visible:outline-sky-500 dark:bg-sky-500/15 dark:text-sky-400 dark:hover:not-disabled:bg-sky-solid dark:hover:not-disabled:text-white',
        'soft-secondary' => 'border-transparent bg-secondary-100 text-secondary-700 hover:not-disabled:bg-secondary-500 hover:not-disabled:text-white focus-visible:outline-secondary-500 dark:bg-secondary-500/15 dark:text-secondary-300 dark:hover:not-disabled:bg-secondary-500 dark:hover:not-disabled:text-white',
        'soft-dark' => 'border-transparent bg-dark/10 text-dark hover:not-disabled:bg-dark hover:not-disabled:text-white focus-visible:outline-dark dark:bg-zinc-700/60 dark:text-zinc-200 dark:hover:not-disabled:bg-zinc-600 dark:hover:not-disabled:text-white',

        // Neutral button (white with a border): Cancel, toolbars, pagination.
        'white' => 'border-gray-300 bg-white text-gray-700 hover:not-disabled:border-gray-400 hover:not-disabled:bg-gray-50 focus-visible:outline-gray-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:not-disabled:border-zinc-500 dark:hover:not-disabled:bg-zinc-700',
        // Ghost: no background and no visible border in any state; only the text darkens on hover.
        'ghost' => 'border-transparent bg-transparent text-gray-600 hover:not-disabled:text-gray-900 focus-visible:outline-gray-400 dark:text-zinc-300 dark:hover:not-disabled:text-white',
        // Link: looks like a link (brand colour, underlined on hover) but keeps the button's size.
        'link' => 'border-transparent bg-transparent text-brand-600 underline-offset-4 hover:not-disabled:underline focus-visible:outline-focus dark:text-brand-400',
    ];

    // The palette's other names for a colour, alone or after outline-/soft- (error → danger,
    // brand → primary, green → success… as in Acun\Ui\Support\Tone), so every colour reads the same way.
    $colour = function (string $value): string {
        $prefix = preg_match('/^(outline|soft)-/', $value, $match) ? $match[0] : '';
        $tone = \Acun\Ui\Support\Tone::name(substr($value, strlen($prefix)), '');

        return $tone === '' ? $value : $prefix.$tone;
    };
    $variant = $colour((string) $variant);
    $aliases = ['outline-primary' => 'outline', 'soft-primary' => 'soft'];

    $variant = match ($appearance) {
        'outline' => match ($variant) {
            'primary' => 'outline',
            'secondary', 'danger', 'success', 'warning', 'info', 'dark' => 'outline-'.$variant,
            default => $variant,
        },
        'text' => 'ghost',
        default => $variant,
    };
    $variant = $aliases[$variant] ?? $variant;
    $variantClass = $variants[$variant] ?? $variants['primary'];

    // A link cannot be disabled: it gets aria-disabled, leaves the tab order and ignores the pointer.
    $disabledLink = $href && $disabled;

    // A display utility in class (hidden, block…) replaces inline-flex, so class="hidden sm:inline-flex" works.
    $display = preg_match('/(?:^|\s)(?:hidden|block|inline-block|inline|flex|grid|inline-grid|contents)(?:\s|$)/', (string) $attributes->get('class')) ? '' : 'inline-flex ';

    $classes = $display.'items-center justify-center rounded-sm border font-semibold transition-[color,background-color,border-color,box-shadow,transform] duration-180 ease-out cursor-pointer select-none active:not-disabled:translate-y-px focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 aria-disabled:pointer-events-none aria-disabled:opacity-60 motion-reduce:transition-none motion-reduce:active:translate-y-0 '
        . ($sizes[$size] ?? $sizes['md']) . ' '
        . $variantClass
        . ($shadow && str_starts_with($variantClass, 'ui-btn ') ? ' ui-btn-shadow' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($external) target="_blank" rel="noopener" @endif @if ($disabledLink) aria-disabled="true" tabindex="-1" @endif {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
        @if ($badge !== null && $badge !== '')
            <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs leading-5 font-semibold tabular-nums ring-1 ring-current/50 ring-inset">{{ $badge }}</span>
        @endif
        @if ($external)
            @unless ($size === 'icon')
                <acun:icon name="arrow-top-right-on-square" size="4" class="opacity-70" />
            @endunless
            <span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled || $loading) @if ($loading) aria-busy="true" @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        @endif
        {{ $slot }}
        @if ($badge !== null && $badge !== '')
            <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs leading-5 font-semibold tabular-nums ring-1 ring-current/50 ring-inset">{{ $badge }}</span>
        @endif
    </button>
@endif
