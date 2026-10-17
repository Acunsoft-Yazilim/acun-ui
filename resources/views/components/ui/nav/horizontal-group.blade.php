@props([
    'title',
    'active' => false,
    // Alignment of the dropdown panel: 'right' for groups near the right edge (e.g. Ayarlar / settings).
    'align' => 'left',
    // Number or short label after the title.
    'badge' => null,
])

{{--
    Group with a submenu in the horizontal (top) menu. The panel opens on mouse hover, when
    the keyboard focus moves inside (focus-within, also without JS) and on a click; Arrow
    Down opens it at its first link, Escape closes it and returns to the button, which says
    whether it is open (aria-expanded). Use <acun:nav.sub-item compact ... /> for the sub-items.
--}}
<div
    x-data="{
        open: false,
        inside: false,
        dismissed: false,
        get shown() { return (this.open || this.inside) && ! this.dismissed },
        toggle() { this.dismissed = this.open; this.open = ! this.open },
        first() { this.open = true; this.dismissed = false; this.$nextTick(() => this.$refs.panel.querySelector('a[href], button:not(:disabled)')?.focus()) },
        leave(event) { if (! this.$el.contains(event.relatedTarget)) { this.inside = false; this.open = false; this.dismissed = false } },
    }"
    x-id="['nav-panel']"
    x-on:focusin="inside = true"
    x-on:focusout="leave($event)"
    x-on:mouseleave="dismissed = false"
    x-on:click.outside="open = false"
    x-on:keydown.escape="if (shown) { $event.stopPropagation(); open = false; dismissed = true; $refs.button.focus() }"
    x-on:keydown.escape.window="open = false"
    {{ $attributes->class('group relative flex h-full items-center') }}
>
    <button
        type="button"
        aria-haspopup="true"
        x-ref="button"
        :aria-expanded="shown.toString()"
        :aria-controls="$id('nav-panel')"
        x-on:click="toggle()"
        x-on:keydown.down.prevent="first()"
        @class([
            'relative flex h-full cursor-pointer items-center gap-2 px-3 text-sm whitespace-nowrap transition-colors',
            'font-semibold text-gray-900 after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-brand-600 dark:text-zinc-100' => $active,
            'font-medium text-gray-700 hover:text-gray-900 group-hover:text-gray-900 dark:text-zinc-300 dark:hover:text-zinc-100 dark:group-hover:text-zinc-100' => ! $active,
        ])
    >
        {{ $icon ?? '' }}
        {{ $title }}
        @if ($badge !== null)
            <span class="rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
        @endif
        <svg class="size-3.5 flex-none text-gray-400 transition-transform group-hover:rotate-180 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>

    <div
        x-ref="panel"
        :id="$id('nav-panel')"
        :class="{ '!visible !opacity-100': open && ! dismissed, '!invisible !opacity-0': dismissed }"
        @class([
        'invisible absolute top-full z-40 w-64 pt-1 opacity-0 transition-opacity duration-100 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100',
        'left-0' => $align !== 'right',
        'right-0' => $align === 'right',
    ])>
        <div class="flex flex-col gap-0.5 rounded-sm bg-white p-1.5 shadow-lg dark:bg-zinc-900">
            {{ $slot }}
        </div>
    </div>
</div>
