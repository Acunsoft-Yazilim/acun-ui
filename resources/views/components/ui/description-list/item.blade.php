@props([
    'label',
])

{{--
    One row of acun:description-list (acun:description-item is the old name and renders
    the same): the label, and the value from the slot. Attributes go on the row.
--}}
<div {{ $attributes->class('grid gap-1 py-3 @sm:grid-cols-3 @sm:gap-4') }}>
    <dt class="min-w-0 font-medium break-words text-gray-500 dark:text-zinc-400">{{ $label }}</dt>
    <dd class="min-w-0 text-gray-900 @sm:col-span-2 dark:text-zinc-100">{{ $slot }}</dd>
</div>
