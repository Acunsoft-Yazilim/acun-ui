{{--
    Top bar of list pages: page size (`start` slot) and search (default slot) on the
    left, actions (`actions` slot: filters, export, a "New" button) on the right. It
    only lays them out; the list's Livewire component (or the data table package the
    application uses) owns the state they bind to. Acun Pro's acun:filter-button goes
    among the actions.
        <acun:list-toolbar>
            <acun:slot:start><acun:per-page-select :options="[10, 25, 50]" /></acun:slot:start>
            <acun:search wire:model.live.debounce.300ms="search" placeholder="Search" />
            <acun:slot:actions>
                <acun:button variant="white">Export</acun:button>
                <acun:button>New</acun:button>
            </acun:slot:actions>
        </acun:list-toolbar>
--}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3 rounded-sm bg-surface p-3 shadow-sm']) }}>
    <div class="flex w-full items-center gap-3 sm:w-auto">
        @isset($start)
            {{ $start }}
        @endisset
        <div class="min-w-0 flex-1 sm:w-80 sm:flex-none">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
