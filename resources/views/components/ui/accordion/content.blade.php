@props([
    // Render the content only once the section is first opened (it then stays). For
    // heavy content: charts, maps, media, or a Livewire component with its own lazy.
    'lazy' => false,
])
@aware(['name' => null])

{{--
    The body of an acun:accordion.item (or acun:accordion-item) written in parts;
    shown while its item is open. Attributes (class…) go on the content box.
    See acun:accordion.heading.
        <acun:accordion.content lazy>
            <livewire:order-stats lazy />
        </acun:accordion.content>
    lazy keeps the content out of the page (in a <template>) until the section opens.
--}}
@php
    if ($name === null) {
        throw new InvalidArgumentException('acun:accordion.content must be inside an acun:accordion-item with a name.');
    }

    // $id('accordion', key) of the accordion's x-id: unique on the page, the same in the heading.
    $key = \Illuminate\Support\Str::slug($name) ?: hash('crc32b', $name);
@endphp

<div :id="$id('accordion', @js($key))" role="region" :aria-labelledby="$id('accordion', @js($key)) + '-heading'" x-show="isOpen(@js($name))" x-collapse.duration.200ms x-cloak>
    @if ($lazy)
        <template x-if="wasOpened(@js($name))">
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
