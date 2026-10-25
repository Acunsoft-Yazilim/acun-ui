@aware(['name' => null])

@props([
    'title' => null,
    'description' => null,
])

{{--
    The title row of an acun:drawer written in parts, with the close (X) button.
        <acun:drawer.header title="Sipariş #4821" description="Ayşe Yılmaz, 3 ürün" />
    Without title, the slot is the heading text (text, an icon, a badge); with title,
    the slot goes under the title and description (e.g. tabs). The title names the
    dialog (aria-labelledby) and the description describes it (aria-describedby).
    Attributes go on the row.

    The ids are the drawer's name + -title / -description; the drawer reads them from the
    header. In a nested Livewire component that re-renders on its own, the drawer is not
    around the header on the server and @aware has no name: then the drawer plugin gives the
    same ids in the browser (data-slot="drawer-title" / "drawer-description", js/behavior/dialog-name.js)
    and keeps them through Livewire's next updates, and the X (data-acun-close) closes the
    drawer it sits in.
    Anywhere else (no drawer around it on the server, and no Livewire component that the
    browser puts into one) it fails at once: it could not name a dialog or close one.
    Ending up outside a drawer in the browser, its X says so in the console.
--}}
@php
    if ($name === null && ! isset($__livewire)) {
        throw new \InvalidArgumentException('acun:drawer.header must be inside an acun:drawer.');
    }

    // The ids come from the drawer plugin in the browser when there is no name here.
    $id = static fn (string $suffix): string => $name !== null ? 'id="'.e($name.'-'.$suffix).'" ' : '';
@endphp

<div data-slot="drawer-header" {{ $attributes->merge(['class' => 'flex '.($description ? 'items-start' : 'items-center').' justify-between gap-3 px-5 py-4 border-b border-line']) }}>
    <div class="min-w-0">
        <h3 {!! $id('title') !!}data-slot="drawer-title" class="text-base font-semibold text-ink">{{ $title ?? $slot }}</h3>
        @if ($description)
            <p {!! $id('description') !!}data-slot="drawer-description" class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
        @if ($title !== null && $slot->isNotEmpty())
            {{ $slot }}
        @endif
    </div>
    <button type="button" data-acun-close aria-label="{{ __('Close') }}" title="{{ __('Close') }}" @class(['flex-none cursor-pointer rounded-sm text-muted hover:text-body focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus', 'mt-0.5' => $description])>
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
