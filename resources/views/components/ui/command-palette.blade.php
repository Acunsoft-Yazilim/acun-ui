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
    Behaviour: js/plugins/command-palette.js (no Alpine). The options are on the page from
    the start; the plugin hides the ones that don't match. On mount the layer moves to the end
    of body, above the page's other layers.
--}}
@php
    $placeholder ??= __('Search…');
    $empty ??= __('No results found');
    $listId = 'command-palette-'.\Illuminate\Support\Str::random(6);
    $entries = collect($items)->values()->map(fn ($entry) => [
        'label' => (string) ($entry['label'] ?? ''),
        // Items may come from a database: javascript:, data: and other schemes become "#".
        'href' => \Acun\Ui\Support\Url::safe((string) ($entry['href'] ?? '#')) ?? '#',
        'group' => (string) ($entry['group'] ?? ''),
        'hint' => (string) ($entry['hint'] ?? ''),
    ]);
@endphp

<div data-acun-command-palette @if ($shortcut) data-shortcut="k" @endif {{ $attributes->merge(['class' => 'w-full']) }}>
    <button
        type="button"
        data-slot="command-palette-trigger"
        class="flex h-9 w-full cursor-pointer items-center gap-2 rounded-sm bg-sunken/60 px-3 text-sm text-muted ring-1 ring-line transition-colors ring-inset hover:bg-field hover:ring-line-strong"
        aria-haspopup="dialog"
    >
        <acun:icon name="magnifying-glass" size="4" />
        <span class="flex-1 truncate text-left">{{ $placeholder }}</span>
        @if ($shortcut)
            <kbd class="hidden rounded-sm border border-line bg-surface px-1.5 font-mono text-2xs text-muted sm:inline">⌘K</kbd>
        @endif
    </button>

    {{-- Closed, the layer is hidden; open, it and its backdrop and panel carry data-open (CSS transitions). --}}
    <div data-slot="command-palette-layer" id="{{ $listId }}-layer" hidden class="pointer-events-none fixed inset-0 z-[80] flex items-start justify-center overflow-y-auto px-4 pt-[12vh] data-open:pointer-events-auto">
        <div data-slot="command-palette-backdrop" class="ui-backdrop fixed inset-0 opacity-0 transition-opacity duration-150 data-open:opacity-100 motion-reduce:transition-none" aria-hidden="true"></div>

        <div
            data-slot="command-palette-panel"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $placeholder }}"
            class="relative w-full max-w-xl -translate-y-2 overflow-hidden rounded-sm bg-surface opacity-0 shadow-xl transition-[opacity,translate] duration-150 ease-out data-open:translate-y-0 data-open:opacity-100 motion-reduce:transition-none"
        >
            {{-- The row shows the search box's focus (the box itself has no ring). --}}
            <div class="flex items-center gap-3 border-b border-line px-4 focus-within:shadow-[inset_0_-2px_0_var(--ac-focus)]">
                <acun:icon name="magnifying-glass" size="5" class="text-faint" />
                {{-- The focus stays here; screen readers follow the highlighted option through aria-activedescendant. --}}
                <input
                    data-acun-control
                    data-slot="command-palette-input"
                    id="{{ $listId }}-input"
                    type="text"
                    placeholder="{{ $placeholder }}"
                    aria-label="{{ $placeholder }}"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded="{{ $entries->isNotEmpty() ? 'true' : 'false' }}"
                    aria-controls="{{ $listId }}"
                    autocomplete="off"
                    class="h-14 flex-1 border-0 bg-transparent p-0 text-base text-ink placeholder:text-muted focus:ring-0"
                />
                <kbd class="rounded-sm border border-line px-1.5 font-mono text-2xs text-muted">Esc</kbd>
            </div>

            <ul id="{{ $listId }}" role="listbox" class="max-h-[50vh] overflow-y-auto p-2">
                {{-- A listbox holds options only: the rows and the group titles are presentation, and the
                     first option of a group says the group's name to screen readers. --}}
                @foreach ($entries as $i => $entry)
                    @php($groupStart = $entry['group'] !== '' && ($i === 0 || $entries[$i - 1]['group'] !== $entry['group']))
                    <li role="none" data-group="{{ $entry['group'] }}">
                        @if ($entry['group'] !== '')
                            <p data-slot="command-palette-group" aria-hidden="true" @if (! $groupStart) hidden @endif class="px-2.5 pt-3 pb-1.5 font-mono text-2xs font-medium tracking-[0.08em] text-muted uppercase">{{ $entry['group'] }}</p>
                        @endif
                        <a
                            id="{{ $listId }}-{{ $i }}"
                            href="{{ $entry['href'] }}"
                            role="option"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            @if ($i === 0) data-highlighted @endif
                            data-search="{{ trim($entry['label'].' '.$entry['group'].' '.$entry['hint']) }}"
                            class="group flex items-center gap-3 rounded-sm px-2.5 py-2 text-sm text-dim data-highlighted:bg-sunken data-highlighted:text-ink"
                        >
                            <span class="min-w-0 flex-1">
                                @if ($entry['group'] !== '')
                                    <span data-slot="command-palette-group-name" class="sr-only" @if (! $groupStart) hidden @endif>{{ $entry['group'] }}: </span>
                                @endif
                                <span class="block truncate font-medium">{{ $entry['label'] }}</span>
                                @if ($entry['hint'] !== '')
                                    <span class="block truncate text-xs text-muted">{{ $entry['hint'] }}</span>
                                @endif
                            </span>
                            <acun:icon name="arrow-turn-down-left" size="4" class="invisible text-faint group-data-highlighted:visible" />
                        </a>
                    </li>
                @endforeach
                <li data-slot="command-palette-empty" role="none" @if ($entries->isNotEmpty()) hidden @endif class="px-3 py-8 text-center text-sm text-muted">{{ $empty }}</li>
            </ul>
            {{-- Said as typing goes (always on the page: a region added with its text is not read everywhere). --}}
            <p data-slot="command-palette-status" class="sr-only" role="status" data-results="{{ __(':count results') }}" data-empty="{{ $empty }}"></p>

            <div class="flex items-center gap-4 border-t border-line px-4 py-2.5 text-2xs text-muted">
                <span><kbd class="font-mono">↑↓</kbd> {{ __('to navigate') }}</span>
                <span><kbd class="font-mono">↵</kbd> {{ __('to open') }}</span>
                <span><kbd class="font-mono">Esc</kbd> {{ __('to close') }}</span>
            </div>
        </div>
    </div>
</div>
