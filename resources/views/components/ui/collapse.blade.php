@props([
    'label',
    'open' => false,
])

{{-- Standalone expandable/collapsible area. The trigger text is label, the content is the slot. --}}
<div x-data="{ open: @js((bool) $open) }" {{ $attributes }}>
    <button
        type="button"
        x-on:click="open = ! open"
        :aria-expanded="open.toString()"
        class="group inline-flex cursor-pointer items-center gap-1.5 rounded-sm text-sm font-medium text-primary-ink hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
    >
        {{ $label }}
        <svg class="size-4 transition-transform group-aria-expanded:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>
    <div x-show="open" x-collapse.duration.200ms x-cloak>
        <div class="pt-3">{{ $slot }}</div>
    </div>
</div>
