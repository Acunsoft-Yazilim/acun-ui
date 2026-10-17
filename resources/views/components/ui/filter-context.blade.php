@props([
    // The filters in use: [key => label], e.g. ['durum' => 'Durum: Kargoda', 'tarih' => 'Ekim 2026'].
    'filters' => [],
    // The Livewire method that removes one filter (called with its key); null: the chips have no X.
    'remove' => null,
    // The Livewire method that clears every filter; null: no "Clear all".
    'clear' => null,
    // The number of results with these filters (optional).
    'count' => null,
])

{{--
    The band over a list that says which filters are in use and lets them go one by one:
        <acun:filter-context :filters="$etkinFiltreler" remove="filtreyiKaldir" clear="filtreleriTemizle" :count="$siparisler->total()" />
    Without filters it draws nothing. The kit only draws it: the filters and the methods
    are the application's.
--}}
@if (count($filters))
    <div {{ $attributes->class('flex flex-wrap items-center gap-2 rounded-sm bg-gray-50 px-3 py-2 text-sm dark:bg-zinc-800/60') }}>
        @if ($count !== null)
            <span class="me-1 text-gray-600 dark:text-zinc-300">{{ trans_choice(':count result|:count results', (int) $count, ['count' => \Acun\Ui\Support\Format::number((float) $count)]) }}</span>
        @endif

        <span class="sr-only">{{ __('Filters in use:') }}</span>
        <ul role="list" class="contents">
            @foreach ($filters as $key => $label)
                <li class="inline-flex items-center gap-1 rounded-full bg-white py-0.5 ps-2.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 ring-inset dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700 {{ $remove ? 'pe-1' : 'pe-2.5' }}" wire:key="filter-{{ $key }}">
                    {{ $label }}
                    @if ($remove)
                        <button type="button" wire:click="{{ $remove }}({{ \Illuminate\Support\Js::from((string) $key) }})" aria-label="{{ __('Remove filter: :label', ['label' => $label]) }}" class="flex size-5 cursor-pointer items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-focus dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($clear)
            <button type="button" wire:click="{{ $clear }}" class="ms-auto cursor-pointer text-xs font-medium text-brand-600 hover:text-brand-700 hover:underline focus-visible:outline-2 focus-visible:outline-focus dark:text-brand-400">{{ __('Clear all') }}</button>
        @endif
    </div>
@endif
