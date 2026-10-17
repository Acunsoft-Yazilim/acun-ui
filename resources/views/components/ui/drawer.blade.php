@props([
    'name',
    'title' => null,
    'side' => 'right',
    'size' => 'md',
    'width' => null,
    'backdrop' => true,
    'floating' => false,
    'compact' => false,
    'docked' => false,
    'lockScroll' => true,
    // false: a click on the backdrop does not close it (a form that must be finished or cancelled).
    'closableOnBackdrop' => true,
    // Accessible name of the dialog when no title row is rendered (the content draws its own heading).
    'ariaLabel' => null,
    // Classes of the body wrapper. If the content builds its own header/body/footer
    // layout, pass e.g. 'flex min-h-0 flex-1 flex-col'.
    'bodyClass' => 'flex-1 p-5 overflow-y-auto',
])

@php
    $widthClass = $width ?? ($size === 'sm' ? 'max-w-xs' : 'max-w-md');

    // Written in parts (acun:drawer.header, acun:drawer.body, acun:drawer.footer), the
    // slot fills the panel as it is instead of the padded body (and bodyClass).
    // drawer.trigger and drawer.close are not layout parts: a classic body may start
    // with one. (The @if blocks around the slot below sit at column 0 so that the
    // classic output stays byte for byte the same.)
    $slotHtml = $slot->toHtml();
    $startsWithParts = fn (string $html): bool => \Acun\Ui\Support\Parts::used($html, 'drawer')
        && ! preg_match('/^(?:\s+|<!--.*?-->)*<[a-z][\w-]*\b[^>]*\sdata-slot="drawer-(?:trigger|close)"/i', $html);
    // A nested Livewire component first (wire:id): Livewire draws it whole only on the first
    // render and leaves an empty stub on the page's later updates, so the markup around it
    // must not follow what it draws. The classic body stays around it and gives way to its
    // parts in the browser (CSS :has below); its header names the dialog where the server
    // sees it, and acunDrawer keeps that name on the later updates.
    $nested = \Acun\Ui\Support\Parts::nestedComponent($slotHtml);
    $parts = $nested === null && $startsWithParts($slotHtml);
    // A drawer.header takes the place of the header slot / title row, and its title
    // (and description) name the dialog. Their ids are read from the header itself:
    // inside the template of a component with a name prop of its own, @aware hands
    // the header that outer name. Without a header, those still render.
    $headerPart = $parts && str_contains($slotHtml, 'data-slot="drawer-header"');
    $nestedHeader = $nested !== null && $startsWithParts($nested) && str_contains($nested, 'data-slot="drawer-header"');
    $headerKey = ($headerPart || $nestedHeader) && preg_match('/\sdata-slot="drawer-header"(?:[^>"\']|"[^"]*"|\'[^\']*\')*>\s*<div class="min-w-0">\s*<h3 id="([^"]*)-title"/', $slotHtml, $match)
        ? htmlspecialchars_decode($match[1], ENT_QUOTES)
        : null;
    $labelledBy = $headerKey !== null ? $headerKey.'-title' : null;
    $describedBy = $headerKey !== null && str_contains($slotHtml, 'id="'.e($headerKey).'-description"') ? $headerKey.'-description' : null;
    // Bound open state: wire:model / x-model on the drawer (acunDrawer's model).
    $bound = $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();
    $dialogName = fn ($label) => $labelledBy
        ? 'aria-labelledby="'.e($labelledBy).'"'.($describedBy ? ' aria-describedby="'.e($describedBy).'"' : '')
        : 'aria-label="'.e($label).'"';
@endphp

@if ($docked)
    {{--
        Docked side panel (the "Filtreler" (filters) panel of table pages) - the
        SAME markup and SAME behaviour in BOTH page layouts (horizontal/vertical).
        The component does not know which layout it is in: its desktop
        position/height (`.filter-panel`) and pushing the content (`.content-area`)
        are handled in Acun Pro's panel.css (the panel layouts, acunsoft/acun-pro)
        with the CSS variables each layout declares on its own <body>
        (`layout-horizontal`/`layout-vertical`). The
        open/closed state is exposed via the `data-filter-open` attribute and the
        content area reads it with `:has()` - no JS store or event syncing.

        On mobile (below lg) the same element is a classic sliding overlay (with a
        backdrop); on desktop a fixed panel without backdrop and with a width
        transition. The form fields are rendered ONLY ONCE (so that no two form
        elements share the same `id`).
    --}}
    <div
        x-data="acunDrawer(@js(['name' => $name, 'docked' => true]))"
        @if ($bound) x-modelable="model" @endif
        data-acun-drawer="{{ $name }}"
        {{-- lg:z-40: in the vertical layout the panel opens ON TOP of the side menu (z-30), in its position. --}}
        {{ $attributes->merge(['class' => 'relative z-50 lg:z-40']) }}
    >
        {{-- Backdrop: visible ONLY on mobile (below lg) - with `lg:hidden` it behaves on desktop as if never rendered and does not catch clicks either (so the "it closes when I click on empty space" complaint never happens on desktop). --}}
        <div
            x-show="rendered"
            x-cloak
            :class="entered ? 'opacity-100' : 'opacity-0'"
            class="ui-backdrop fixed inset-0 transition-opacity duration-200 lg:hidden"
            aria-hidden="true"
            @if ($closableOnBackdrop) x-on:click="close()" @endif
        ></div>

        {{--
            `x-cloak`: the static classes have no "closed" transform/width
            (both come from `:class`), so the panel is kept hidden until Alpine
            is ready - otherwise it would briefly appear open with `w-[85vw]`
            while loading. Since the panel always starts closed, staying hidden
            is visually the correct state anyway; there is no transition/jump on
            the first paint.
        --}}
        <div
            x-ref="panel"
            x-cloak
            {{-- On desktop closing also animates the width (not a slide) - so the close button can follow the edge in both directions; once fully closed (rendered=false) the panel is invisible so the border line left at 0 width does not show. --}}
            :class="[entered ? 'translate-x-0 lg:w-(--menu-width)' : '-translate-x-full lg:translate-x-0 lg:w-0', rendered ? '' : 'invisible']"
            :data-filter-open="entered ? 'true' : 'false'"
            class="filter-panel fixed inset-y-0 left-0 z-50 flex h-full w-[85vw] max-w-xs flex-col overflow-hidden bg-surface shadow-xl transition-transform duration-200 ease-out lg:max-w-none lg:border lg:border-line lg:shadow-lg lg:transition-[width]"
            tabindex="-1"
            role="dialog"
            {{-- Modal only as the mobile overlay; on desktop the page beside it stays in use. --}}
            :aria-modal="overlay.toString()"
            {!! $dialogName($title ?? __('Filters')) !!}
        >
            <div class="flex w-full flex-1 flex-col overflow-hidden lg:w-(--menu-width){{ $parts ? ' [&>[wire\:id]:has(>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents' : '' }}">
@if ($parts)
                {{ $slot }}
@elseif ($nested !== null)
                <div class="flex-1 overflow-y-auto p-5 [&:has(>[wire\:id]>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents [&>[wire\:id]:has(>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents">
                    {{ $slot }}
                </div>
@else
                <div class="flex-1 overflow-y-auto p-5">
                    {{ $slot }}
                </div>
@endif

                @isset($footer)
                    <div class="flex items-center {{ $compact ? 'gap-1.5 px-3 py-2.5' : 'justify-end gap-2 px-5 py-4' }} bg-sunken/50">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>

        {{--
            Close button - same geometry and alignment as the side menu's
            collapse button (panel's right edge, 2.75rem from the top), red + X.
            It sits next to the panel as a sibling, not inside it: the panel is
            `overflow-hidden` (so it can be clipped to 0 width when closed) and
            a button overflowing the edge would be clipped inside. Its position
            and visibility live in panel.css as `.filter-close-button`: bound to
            the panel's open/closed state (`data-filter-open`), it follows the
            panel's right edge while the width animates (x-show is not used -
            Alpine's transition styles would override the position transition).
        --}}
        <button
            type="button"
            x-cloak
            x-on:click="close()"
            class="menu-toggle menu-toggle-close filter-close-button fixed z-50 hidden -translate-y-1/2 items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-ink lg:flex"
            aria-label="{{ __('Close filters') }}"
            title="{{ __('Close filters') }}"
        >
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@else
    {{--
        The behaviour is acunDrawer of the @acunsoft/acun-ui runtime (js/components/drawer.js);
        Escape, Tab, the page scroll lock and the focus restore come from its overlay manager.
        To open: AcunUI.drawer.open('{{ $name }}'), $dispatch('acun:drawer:open', { name: '{{ $name }}' })
        (the first name open-drawer works too), or wrap the button in <acun:drawer.trigger name="{{ $name }}">.
        To close: AcunUI.drawer.close('{{ $name }}') or acun:drawer:close (close-drawer); without a
        name every open drawer closes. Inside the drawer: <acun:drawer.close>, or close().
        To toggle: AcunUI.drawer.toggle('{{ $name }}') or acun:drawer:toggle (toggle-drawer).
        Bound to a boolean (Livewire or Alpine), it opens and closes with it and sets it
        when the user closes it (Escape, backdrop, X):
            <acun:drawer name="siparis-detay" wire:model="showDetail">…</acun:drawer>
        (wire:model.live sends the closing at once.) x-model binds Alpine data; name the
        property other than the drawer's own state (rendered, entered, opened, model).
        It sends acun:drawer:opened / acun:drawer:closed ({ name }) and drawer-state-changed
        ({ name, open }) when it opens and as soon as it starts closing. Other attributes go
        on the root element.

        Written in parts (the docked panel takes them too):
            <acun:drawer name="siparis-detay">
                <acun:drawer.header title="Sipariş #4821" description="Ayşe Yılmaz" />
                <acun:drawer.body>…</acun:drawer.body>
                <acun:drawer.footer>
                    <acun:drawer.close><acun:button variant="white">Kapat</acun:button></acun:drawer.close>
                </acun:drawer.footer>
            </acun:drawer>
        The header's title names the dialog (aria-labelledby) and its description describes it.
        The parts may also be drawn by a nested Livewire component (<livewire:siparis-detayi />
        as the drawer's content, its root starting with acun:drawer.header or acun:drawer.body).
    --}}
    <div
        x-data="acunDrawer(@js(['name' => $name, 'lockScroll' => (bool) $lockScroll]))"
        @if ($bound) x-modelable="model" @endif
        data-acun-drawer="{{ $name }}"
        x-show="rendered"
        x-cloak
        id="{{ $name }}"
        role="dialog"
        aria-modal="true"
        {!! $dialogName($ariaLabel ?? $title ?? __('Menu')) !!}
        {{ $attributes->merge(['class' => 'relative z-50']) }}
    >
        <div
            @if ($backdrop)
                :class="entered ? 'opacity-100' : 'opacity-0'"
                class="ui-backdrop fixed inset-0 transition-opacity duration-200"
            @else
                class="fixed inset-0"
            @endif
            aria-hidden="true"
            @if ($closableOnBackdrop) x-on:click="close()" @endif
        ></div>

        <div class="fixed {{ $floating ? 'inset-y-4' : 'inset-y-0' }} flex max-w-full {{ $side === 'left' ? 'left-0' : 'right-0' }} {{ $floating ? ($side === 'left' ? 'ml-4' : 'mr-4') : '' }}">
            <div
                x-ref="panel"
                :class="entered ? 'translate-x-0' : '{{ $side === 'left' ? '-translate-x-full' : 'translate-x-full' }}'"
                {{-- Written in parts, a nested Livewire component that starts with a part (body and footer after the header) is layout-neutral, so its parts are the panel's rows. --}}
                class="flex h-full w-[85vw] {{ $widthClass }} flex-col bg-surface shadow-xl outline-none transition-transform duration-200 ease-out {{ $floating ? 'rounded-sm' : '' }}{{ $parts ? ' [&>[wire\:id]:has(>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents' : '' }}"
                tabindex="-1"
                {{-- A followed link closes the panel (a menu; see followLink() for the exceptions); the click still bubbles, so popups inside close on click outside. --}}
                x-on:click="followLink($event)"
            >
                @if (isset($header) && ! $headerPart)
                    <div class="px-5 pt-4 border-b border-line">
                        {{ $header }}
                    </div>
                @elseif ($title && ! $headerPart)
                    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                        <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
                        <button type="button" x-on:click="close()" aria-label="{{ __('Close') }}" title="{{ __('Close') }}" class="cursor-pointer rounded-sm text-muted hover:text-body focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

@if ($parts)
                {{ $slot }}
@elseif ($nested !== null)
                {{-- The same on every update: when the component starts with a part, this body and the component's root give way (display: contents) and the parts are the panel's rows. --}}
                <div class="{{ $bodyClass }} [&:has(>[wire\:id]>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents [&>[wire\:id]:has(>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents">
                    {{ $slot }}
                </div>
@else
                <div class="{{ $bodyClass }}">
                    {{ $slot }}
                </div>
@endif

                @isset($footer)
                    <div class="flex items-center {{ $compact ? 'gap-1.5 px-3 py-2.5' : 'justify-end gap-2 px-5 py-4' }} bg-sunken/50 border-t border-line">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </div>
@endif
