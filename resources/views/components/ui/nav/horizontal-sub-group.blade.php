@props([
    'title',
    'active' => false,
    // Which side the flyout opens on: 'right' (default) or 'left' for panels near the right edge.
    'side' => 'right',
    'badge' => null,
])

{{--
    Group inside a horizontal-group panel: its links open in a flyout beside the panel on
    mouse hover, when the keyboard focus moves inside (focus-within, also without JS) and on a
    click; Arrow Right (Left when it opens on the left) opens it at its first link, Escape closes
    it and returns to its button. acun:menu uses it when a section of the menu becomes one
    top-level dropdown.
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
    {{ $attributes->class('group/sub relative') }}
>
    <button
        type="button"
        aria-haspopup="true"
        x-ref="button"
        :aria-expanded="shown.toString()"
        :aria-controls="$id('nav-panel')"
        x-on:click="toggle()"
        x-on:keydown.{{ $side === 'right' ? 'right' : 'left' }}.prevent="first()"
        @class([
            'flex h-9 w-full cursor-pointer items-center gap-2 rounded-sm px-3 text-sm whitespace-nowrap transition-colors',
            'bg-gray-100 font-semibold text-gray-900 dark:bg-zinc-800 dark:text-zinc-100' => $active,
            'text-gray-500 group-hover/sub:bg-gray-50 group-hover/sub:text-gray-900 dark:text-zinc-400 dark:group-hover/sub:bg-zinc-800/60 dark:group-hover/sub:text-zinc-100' => ! $active,
        ])
    >
        @isset($icon)
            <span class="flex-none text-gray-400 dark:text-zinc-500">{{ $icon }}</span>
        @endisset
        <span class="flex-1 truncate text-start">{{ $title }}</span>
        @if ($badge !== null)
            <span class="rounded-full bg-accent-50 px-1.5 font-mono text-2xs font-medium tabular-nums text-accent-600 dark:bg-accent-500/15 dark:text-accent-500">{{ $badge }}</span>
        @endif
        <svg @class(['size-3.5 flex-none text-gray-400 dark:text-zinc-500', 'rotate-180' => $side !== 'right']) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
    </button>

    <div
        x-ref="panel"
        :id="$id('nav-panel')"
        :class="{ '!visible !opacity-100': open && ! dismissed, '!invisible !opacity-0': dismissed }"
        @class([
        'invisible absolute -top-1.5 z-50 w-60 opacity-0 transition-opacity duration-100 group-hover/sub:visible group-hover/sub:opacity-100 group-focus-within/sub:visible group-focus-within/sub:opacity-100',
        'left-full ps-2' => $side === 'right',
        'right-full pe-2' => $side !== 'right',
    ])>
        <div class="flex max-h-[calc(100vh-8rem)] flex-col gap-0.5 overflow-y-auto rounded-sm bg-white p-1.5 shadow-lg dark:bg-zinc-900">
            {{ $slot }}
        </div>
    </div>
</div>
