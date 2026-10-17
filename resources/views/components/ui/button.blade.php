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
    // button is disabled (hover:not-disabled:). Every colour is a token (css/tokens.css), so the
    // theme, the dark theme and a custom primary colour change them all.
    $variants = [
        // Solid: the tone's fill, its hover and its own text colour (a light custom primary colour
        // takes dark text); the border is the fill.
        'primary' => 'border-primary bg-primary text-on-primary hover:not-disabled:border-primary-hover hover:not-disabled:bg-primary-hover focus-visible:outline-focus',
        'success' => 'border-success bg-success text-on-success hover:not-disabled:border-success-hover hover:not-disabled:bg-success-hover focus-visible:outline-success-line',
        'danger' => 'border-danger bg-danger text-on-danger hover:not-disabled:border-danger-hover hover:not-disabled:bg-danger-hover focus-visible:outline-danger-line',
        'warning' => 'border-warning bg-warning text-on-warning hover:not-disabled:border-warning-hover hover:not-disabled:bg-warning-hover focus-visible:outline-warning-line',
        'info' => 'border-info bg-info text-on-info hover:not-disabled:border-info-hover hover:not-disabled:bg-info-hover focus-visible:outline-info-line',
        'secondary' => 'border-secondary bg-secondary text-on-secondary hover:not-disabled:border-secondary-hover hover:not-disabled:bg-secondary-hover focus-visible:outline-secondary-line',
        'dark' => 'border-dark bg-dark text-on-dark hover:not-disabled:border-dark-hover hover:not-disabled:bg-dark-hover focus-visible:outline-dark-line',
        // Light: a sunken grey with a line, darker on hover.
        'light' => 'border-line bg-sunken text-dim hover:not-disabled:border-line-strong hover:not-disabled:bg-track focus-visible:outline-line-hover',

        // Outline: border and text in the colour, filled on hover. The text is the colour's ink,
        // readable on the surface; the hover is the solid fill with its own text colour.
        'outline' => 'border-primary-ink text-primary-ink hover:not-disabled:bg-primary hover:not-disabled:text-on-primary focus-visible:outline-focus',
        'outline-success' => 'border-success-line text-success-ink hover:not-disabled:bg-success hover:not-disabled:text-on-success focus-visible:outline-success-line',
        'outline-danger' => 'border-danger-line text-danger-ink hover:not-disabled:bg-danger hover:not-disabled:text-on-danger focus-visible:outline-danger-line',
        'outline-warning' => 'border-warning-line text-warning-ink hover:not-disabled:bg-warning hover:not-disabled:text-on-warning focus-visible:outline-warning-line',
        'outline-info' => 'border-info-line text-info-ink hover:not-disabled:bg-info hover:not-disabled:text-on-info focus-visible:outline-info-line',
        'outline-secondary' => 'border-secondary-line text-secondary-ink hover:not-disabled:bg-secondary hover:not-disabled:text-on-secondary focus-visible:outline-secondary-line',
        'outline-dark' => 'border-dark-line text-dark-ink hover:not-disabled:bg-dark hover:not-disabled:text-on-dark focus-visible:outline-dark-line',

        // Soft: a light tint of the colour that fills in on hover.
        'soft' => 'border-transparent bg-primary-soft text-primary-tint-ink hover:not-disabled:bg-primary hover:not-disabled:text-on-primary focus-visible:outline-focus',
        'soft-success' => 'border-transparent bg-success-soft text-success-tint-ink hover:not-disabled:bg-success hover:not-disabled:text-on-success focus-visible:outline-success-line',
        'soft-danger' => 'border-transparent bg-danger-soft text-danger-tint-ink hover:not-disabled:bg-danger hover:not-disabled:text-on-danger focus-visible:outline-danger-line',
        'soft-warning' => 'border-transparent bg-warning-soft text-warning-tint-ink hover:not-disabled:bg-warning hover:not-disabled:text-on-warning focus-visible:outline-warning-line',
        'soft-info' => 'border-transparent bg-info-soft text-info-tint-ink hover:not-disabled:bg-info hover:not-disabled:text-on-info focus-visible:outline-info-line',
        'soft-secondary' => 'border-transparent bg-secondary-soft text-secondary-tint-ink hover:not-disabled:bg-secondary hover:not-disabled:text-on-secondary focus-visible:outline-secondary-line',
        'soft-dark' => 'border-transparent bg-dark-soft text-dark-tint-ink hover:not-disabled:bg-dark hover:not-disabled:text-on-dark focus-visible:outline-dark-line',

        // Neutral button (a field with a frame): Cancel, toolbars, pagination.
        'white' => 'border-line-strong bg-field text-body hover:not-disabled:border-line-hover hover:not-disabled:bg-field-hover focus-visible:outline-line-hover',
        // Ghost: no background and no visible border in any state; only the text darkens on hover.
        'ghost' => 'border-transparent bg-transparent text-dim hover:not-disabled:text-ink focus-visible:outline-line-hover',
        // Link: looks like a link (the primary ink, underlined on hover) but keeps the button's size.
        'link' => 'border-transparent bg-transparent text-primary-ink underline-offset-4 hover:not-disabled:underline focus-visible:outline-focus',
    ];

    // shadow: a shadow in the solid fill's colour, gone on hover, press and while disabled.
    $shadows = [
        'primary' => 'shadow-primary/90', 'success' => 'shadow-success/90', 'danger' => 'shadow-danger/90', 'warning' => 'shadow-warning/90',
        'info' => 'shadow-info/90', 'secondary' => 'shadow-secondary/90', 'dark' => 'shadow-dark/75', 'light' => 'shadow-secondary/18',
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
    $variant = isset($variants[$variant]) ? $variant : 'primary';
    $variantClass = $variants[$variant];

    // A link cannot be disabled: it gets aria-disabled, leaves the tab order and ignores the pointer.
    $disabledLink = $href && $disabled;

    // A display utility in class (hidden, block…) replaces inline-flex, so class="hidden sm:inline-flex" works.
    $display = preg_match('/(?:^|\s)(?:hidden|block|inline-block|inline|flex|grid|inline-grid|contents)(?:\s|$)/', (string) $attributes->get('class')) ? '' : 'inline-flex ';

    $classes = $display.'items-center justify-center rounded-sm border font-semibold transition-[color,background-color,border-color,box-shadow,transform] duration-180 ease-out cursor-pointer select-none active:not-disabled:translate-y-px focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 aria-disabled:pointer-events-none aria-disabled:opacity-60 motion-reduce:transition-none motion-reduce:active:translate-y-0 '
        . ($sizes[$size] ?? $sizes['md']) . ' '
        . $variantClass
        . ($shadow && isset($shadows[$variant]) ? ' shadow-[0_0.5rem_1.125rem_-0.5rem] '.$shadows[$variant].' hover:shadow-none active:shadow-none disabled:shadow-none aria-disabled:shadow-none' : '');
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
