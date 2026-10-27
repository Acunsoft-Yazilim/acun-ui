@props([
    // Render the content only once the section is first opened (it then stays). For
    // heavy content: charts, maps, media, or a Livewire component with its own lazy.
    'lazy' => false,
])
@aware(['name' => null, 'open' => null])

{{--
    The body of an acun:accordion.item (or acun:accordion-item) written in parts;
    shown while its item is open. Attributes (class…) go on the content box.
    See acun:accordion.heading.
        <acun:accordion.content lazy>
            <livewire:order-stats lazy />
        </acun:accordion.content>
    lazy keeps the content out of the page (in a <template>) until the section opens; a section
    the server draws open renders it at once.
--}}
@php
    if ($name === null) {
        throw new InvalidArgumentException('acun:accordion.content must be inside an acun:accordion-item with a name.');
    }

    $expanded = in_array($name, array_map('strval', (array) $open), true);
@endphp

{{-- The height animates in CSS: a one-row grid whose row opens from 0fr to 1fr (js/behavior/disclosure.js). --}}
<div
    data-slot="accordion-content"
    role="region"
    @if ($expanded) data-open @else hidden @endif
    class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-200 ease-out data-open:grid-rows-[1fr] motion-reduce:transition-none"
>
    <div class="min-h-0 overflow-hidden">
        @if ($lazy && ! $expanded)
            <template data-acun-lazy x-if="wasOpened(@js($name))">
                <div {{ $attributes->merge(['class' => 'px-5 pb-5 text-sm leading-6 text-dim']) }}>
                    {{ $slot }}
                </div>
            </template>
        @else
            <div {{ $attributes->merge(['class' => 'px-5 pb-5 text-sm leading-6 text-dim']) }}>
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
