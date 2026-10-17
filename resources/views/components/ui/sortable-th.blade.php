@props([
    'field',
    'sortField',
    'sortDirection',
    // The Livewire method the click calls with the field name (your component's own sorting method).
    'method' => 'sortBy',
])

@php($active = $sortField === $field)

{{-- The click target is a real <button> so sorting works from the keyboard too; aria-sort announces the active column. --}}
<th
    @if ($active) aria-sort="{{ $sortDirection === 'desc' ? 'descending' : 'ascending' }}" @endif
    {{ $attributes->merge(['class' => 'select-none']) }}
>
    <button
        type="button"
        wire:click="{{ $method }}(@js($field))"
        class="inline-flex cursor-pointer items-center gap-1 rounded-sm [text-transform:inherit] hover:text-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus dark:hover:text-zinc-200"
    >
        {{ $slot }}
        <span class="flex flex-col justify-center leading-none" aria-hidden="true">
            <svg class="size-3 {{ $active && $sortDirection === 'asc' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-300 dark:text-zinc-600' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12 5l7 8H5l7-8z"/></svg>
            <svg class="-mt-1 size-3 {{ $active && $sortDirection === 'desc' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-300 dark:text-zinc-600' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12 19l-7-8h14l-7 8z"/></svg>
        </span>
    </button>
</th>
