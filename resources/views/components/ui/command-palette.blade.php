@props([
    // [['label' => 'Siparişler', 'href' => '/siparisler', 'group' => 'Sayfalar', 'hint' => 'Tüm siparişler'], …]
    'items' => [],
    // Defaults: "Search…" and "No results found" in the current language.
    'placeholder' => null,
    'empty' => null,
    // Shortcut: ⌘K / Ctrl+K. Disabled when false.
    'shortcut' => true,
])

{{--
    Search box in the top bar + command palette. Opens when the box is clicked
    or with ⌘K / Ctrl+K; filters as you type (Turkish case-insensitive), navigate
    with the arrow keys, Enter goes to the item (through wire:navigate when
    available). Event: it also opens when `command-palette-open` is dispatched on window.
--}}
@php
    $placeholder ??= __('Search…');
    $empty ??= __('No results found');
    $listId = 'command-palette-'.\Illuminate\Support\Str::random(6);
    $entries = collect($items)->values()->map(fn ($entry, $i) => [
        'id' => $i,
        'label' => (string) ($entry['label'] ?? ''),
        // Items may come from a database: javascript:, data: and other schemes become "#".
        'href' => \Acun\Ui\Support\Url::safe((string) ($entry['href'] ?? '#')) ?? '#',
        'group' => (string) ($entry['group'] ?? ''),
        'hint' => (string) ($entry['hint'] ?? ''),
    ]);
@endphp

<div
    x-data="{
        open: false,
        query: '',
        highlighted: 0,
        items: @js($entries),
        results: [],
        listId: @js($listId),
        init() {
            this.items.forEach((item) => { item.search = (item.label + ' ' + item.group + ' ' + item.hint).toLocaleLowerCase(document.documentElement.lang || undefined) });
            this.filter();
            this.$watch('query', () => this.filter());
        },
        // Once per keystroke, not once per row: the rows read the list and its group starts.
        filter() {
            const q = this.query.trim().toLocaleLowerCase(document.documentElement.lang || undefined);
            const found = q ? this.items.filter((item) => item.search.includes(q)) : this.items;
            this.results = found.map((item, i) => ({ ...item, groupStart: !! item.group && (i === 0 || found[i - 1].group !== item.group) }));
        },
        optionId(item) { return item ? this.listId + '-' + item.id : null },
        // Page scroll lock, Escape, Tab and focus restore: the runtime's overlay manager (AcunUI.overlays).
        layer() { return document.getElementById(this.listId + '-layer'); },
        show() {
            this.query = ''; this.highlighted = 0;
            if (this.open) return;
            this.open = true;
            window.AcunUI?.overlays.open(this.layer(), { close: () => this.close(), trap: () => this.layer() });
            // Focus once the teleported content is visible.
            setTimeout(() => document.getElementById(this.listId + '-input')?.focus(), 30);
        },
        close() {
            if (! this.open) return;
            this.open = false;
            window.AcunUI?.overlays.close(this.layer());
        },
        go(item) {
            if (! item) return;
            this.close();
            // wire:navigate only within the application; mailto:, tel: and other sites as a normal link.
            const sameSite = new URL(item.href, location.href).origin === location.origin;
            sameSite && window.Livewire?.navigate ? window.Livewire.navigate(item.href) : (window.location.href = item.href);
        },
        scrollToHighlighted() { this.$nextTick(() => document.getElementById(this.listId)?.querySelector('[data-highlighted]')?.scrollIntoView({ block: 'nearest' })) },
    }"
    @if ($shortcut) x-on:keydown.window.prevent.meta.k="open ? close() : show()" x-on:keydown.window.prevent.ctrl.k="open ? close() : show()" @endif
    x-on:command-palette-open.window="show()"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    <button
        type="button"
        x-on:click="show()"
        class="flex h-9 w-full cursor-pointer items-center gap-2 rounded-sm bg-gray-50 px-3 text-sm text-gray-500 ring-1 ring-gray-200 transition-colors ring-inset hover:bg-white hover:text-gray-500 hover:ring-gray-300 dark:bg-zinc-800/60 dark:text-zinc-400 dark:ring-zinc-700 dark:hover:bg-zinc-800"
        aria-haspopup="dialog"
    >
        <acun:icon name="magnifying-glass" size="4" />
        <span class="flex-1 truncate text-left">{{ $placeholder }}</span>
        @if ($shortcut)
            <kbd class="hidden rounded-sm border border-gray-200 bg-white px-1.5 font-mono text-2xs text-gray-500 sm:inline dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">⌘K</kbd>
        @endif
    </button>

    <template x-teleport="body">
        <div :id="listId + '-layer'" x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-start justify-center overflow-y-auto px-4 pt-[12vh]">
            <div x-show="open" x-transition.opacity.duration.150ms class="ui-backdrop fixed inset-0" x-on:click="close()" aria-hidden="true"></div>

            <div
                x-show="open"
                x-transition:enter="ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                role="dialog"
                aria-modal="true"
                aria-label="{{ $placeholder }}"
                class="relative w-full max-w-xl overflow-hidden rounded-sm bg-white shadow-xl dark:bg-zinc-900"
            >
                <div class="flex items-center gap-3 border-b border-gray-200 px-4 dark:border-zinc-800">
                    <acun:icon name="magnifying-glass" size="5" class="text-gray-400" />
                    <input
                        :id="listId + '-input'"
                        x-model="query"
                        x-on:input="highlighted = 0"
                        x-on:keydown.arrow-down.prevent="highlighted = Math.min(highlighted + 1, results.length - 1); scrollToHighlighted()"
                        x-on:keydown.arrow-up.prevent="highlighted = Math.max(highlighted - 1, 0); scrollToHighlighted()"
                        x-on:keydown.enter.prevent="go(results[highlighted])"
                        type="text"
                        placeholder="{{ $placeholder }}"
                        role="combobox"
                        aria-autocomplete="list"
                        :aria-expanded="(results.length > 0).toString()"
                        {{-- The focus stays here; screen readers follow the highlighted option through this. --}}
                        :aria-activedescendant="optionId(results[highlighted])"
                        {{-- Its own Escape: the overlay manager leaves Escape to an expanded combobox. --}}
                        x-on:keydown.escape.prevent.stop="close()"
                        :aria-controls="listId"
                        class="h-14 flex-1 border-0 bg-transparent p-0 text-base text-gray-900 placeholder:text-gray-500 dark:placeholder:text-zinc-400 focus:ring-0 dark:text-zinc-100"
                    />
                    <kbd class="rounded-sm border border-gray-200 px-1.5 font-mono text-2xs text-gray-500 dark:border-zinc-700 dark:text-zinc-400">Esc</kbd>
                </div>

                <ul :id="listId" role="listbox" class="max-h-[50vh] overflow-y-auto p-2">
                    {{-- A listbox holds options only: the rows and the group titles are presentation, and the
                         first option of a group says the group's name to screen readers. --}}
                    <template x-for="(item, i) in results" :key="item.id">
                        <li role="none">
                            <p x-show="item.groupStart" aria-hidden="true" class="px-2.5 pt-3 pb-1.5 font-mono text-2xs font-medium tracking-[0.08em] text-gray-500 uppercase dark:text-zinc-400" x-text="item.group"></p>
                            <a
                                :id="optionId(item)"
                                :href="item.href"
                                x-on:click.prevent="go(item)"
                                x-on:mousemove="highlighted = i"
                                :data-highlighted="highlighted === i ? '' : null"
                                role="option"
                                :aria-selected="(highlighted === i).toString()"
                                :class="highlighted === i ? 'bg-gray-100 text-gray-900 dark:bg-zinc-800 dark:text-zinc-100' : 'text-gray-600 dark:text-zinc-300'"
                                class="flex items-center gap-3 rounded-sm px-2.5 py-2 text-sm"
                            >
                                <span class="min-w-0 flex-1">
                                    <span x-show="item.groupStart" class="sr-only" x-text="item.group + ': '"></span>
                                    <span class="block truncate font-medium" x-text="item.label"></span>
                                    <span x-show="item.hint" class="block truncate text-xs text-gray-500 dark:text-zinc-400" x-text="item.hint"></span>
                                </span>
                                <acun:icon name="arrow-turn-down-left" size="4" class="text-gray-400" x-show="highlighted === i" />
                            </a>
                        </li>
                    </template>
                    <li x-show="results.length === 0" role="none" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-zinc-400"><span role="status">{{ $empty }}</span></li>
                </ul>

                <div class="flex items-center gap-4 border-t border-gray-200 px-4 py-2.5 text-2xs text-gray-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span><kbd class="font-mono">↑↓</kbd> {{ __('to navigate') }}</span>
                    <span><kbd class="font-mono">↵</kbd> {{ __('to open') }}</span>
                    <span><kbd class="font-mono">Esc</kbd> {{ __('to close') }}</span>
                </div>
            </div>
        </div>
    </template>
</div>
