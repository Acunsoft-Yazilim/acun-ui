@props([
    'label',
    'open' => false,
])

{{--
    Standalone expandable/collapsible area. The trigger text is label, the content is the slot.
    Works without Alpine (the collapse plugin of the runtime); the height animates in CSS.
--}}
<div {{ $attributes->merge(['data-acun-collapse' => '']) }}>
    <button
        type="button"
        data-slot="collapse-trigger"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        class="group inline-flex cursor-pointer items-center gap-1.5 rounded-sm text-sm font-medium text-primary-ink hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
    >
        {{ $label }}
        <svg class="size-4 transition-transform group-aria-expanded:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>
    <div
        data-slot="collapse-content"
        @if ($open) data-open @else hidden @endif
        class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-200 ease-out data-open:grid-rows-[1fr] motion-reduce:transition-none"
    >
        <div class="min-h-0 overflow-hidden"><div class="pt-3">{{ $slot }}</div></div>
    </div>
</div>
