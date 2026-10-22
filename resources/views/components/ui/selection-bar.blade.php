@props([
    // Number of selected records, e.g. count($selected).
    'count' => 0,
    // true when "all records matching the filter" are selected; $total is shown then.
    'allMatching' => false,
    'total' => null,
    // Livewire method that clears the selection; the button is hidden when null.
    'clearAction' => 'clearSelection',
    // true: the bar sticks to the bottom of the screen. Place it AFTER the table and
    // pagination: appearing there pushes nothing that is on screen (no layout shift),
    // and it stays in view while the list scrolls. false: a plain bar in the flow.
    'floating' => true,
])

{{--
    Bulk action bar - visible while something is selected. Action buttons come from the slot.
    The list contract: the Livewire component counts its own selection and has the method
    named by clear-action (clearSelection by default).
        …table… {{ $orders->links() }}
        <acun:selection-bar :count="$selectionCount" :all-matching="$allMatching" :total="$selectionCount">
            <acun:button variant="danger" size="sm" wire:click="deleteSelected">Delete</acun:button>
        </acun:selection-bar>
--}}
@if ($count > 0 || $allMatching)
    <div {{ $attributes->class([
        'flex flex-wrap items-center gap-3 rounded-sm border border-primary/25 px-4 py-3 text-sm',
        // Floating over the rows: solid in the dark theme (a see-through tint would show the rows below).
        'sticky bottom-4 z-20 bg-surface bg-linear-to-r from-primary-tint to-primary-tint shadow-lg' => $floating,
        'bg-primary-tint' => ! $floating,
    ]) }} role="status" data-selection-bar>
        <span class="font-medium text-primary-tint-ink">
            {{ $allMatching
                ? trans_choice(':count record (all matching the filters) selected.|:count records (all matching the filters) selected.', (int) ($total ?? $count), ['count' => \Acun\Ui\Support\Format::number((int) ($total ?? $count))])
                : trans_choice(':count record selected.|:count records selected.', (int) $count, ['count' => \Acun\Ui\Support\Format::number((int) $count)]) }}
        </span>

        <div class="ms-auto flex flex-wrap items-center gap-2">
            {{ $slot }}

            @if ($clearAction)
                <acun:button variant="ghost" size="sm" wire:click="{{ $clearAction }}">{{ __('Clear selection') }}</acun:button>
            @endif
        </div>
    </div>
@endif
