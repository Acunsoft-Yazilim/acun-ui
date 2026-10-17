@props([
    // Page sizes to offer, e.g. [10, 25, 50, 100]. Only the numbers are shown.
    'options' => [10, 25, 50, 100],
    // The Livewire property holding the page size.
    'model' => 'perPage',
    'label' => null,
])

{{--
    Page-size box of a list ("10 / 25 / 50 / 100"). Put it before the search in
    acun:list-toolbar's `start` slot. The list contract: the Livewire component has
    the public property named by `model` (perPage by default) and goes back to the
    first page when it changes. php artisan acun:table generates a listing that uses it.
        <acun:slot:start><acun:per-page-select :options="[10, 25, 50]" /></acun:slot:start>
--}}
<div {{ $attributes->except('id')->class('w-20 flex-none') }}>
    <acun:select
        wire:model.live="{{ $model }}"
        :id="$attributes->get('id') ?? 'per-page-'.$model"
        :aria-label="$label ?? __('Rows per page')"
        :options="collect($options)->mapWithKeys(fn ($option) => [(int) $option => (string) (int) $option])->all()"
    />
</div>
