@aware(['name' => null])

@props([
    'title' => null,
    'description' => null,
])

{{--
    The title row of an acun:modal written in parts, with the close (X) button.
        <acun:modal.header title="Yeni müşteri" description="Müşteri grubunu ve sorumlu temsilciyi seçin." />
    Without title, the slot is the heading text (text, an icon, a badge); with title,
    the slot goes under the title and description. The title names the dialog
    (aria-labelledby) and the description describes it (aria-describedby).
    Attributes go on the row.

    The ids are the modal's name + -title / -description; the modal reads them from the
    header. In a nested Livewire component that re-renders on its own, the modal is not
    around the header on the server and @aware has no name: then the modal plugin gives the
    same ids in the browser (data-slot="modal-title" / "modal-description", js/behavior/dialog-name.js)
    and keeps them through Livewire's next updates, and the X (data-acun-close) closes the modal
    it sits in, through its request-close-event when it has one.
    Anywhere else (no modal around it on the server, and no Livewire component that the
    browser puts into one) it fails at once: it could not name a dialog or close one.
    Ending up outside a modal in the browser, its X says so in the console.
--}}
@php
    if ($name === null && ! isset($__livewire)) {
        throw new \InvalidArgumentException('acun:modal.header must be inside an acun:modal.');
    }

    // The ids come from the modal plugin in the browser when there is no name here.
    $id = static fn (string $suffix): string => $name !== null ? 'id="'.e($name.'-'.$suffix).'" ' : '';
@endphp

<div data-slot="modal-header" {{ $attributes->merge(['class' => 'flex flex-none '.($description ? 'items-start' : 'items-center').' justify-between gap-3 border-b border-line px-5 py-4']) }}>
    <div class="min-w-0">
        <h3 {!! $id('title') !!}data-slot="modal-title" class="text-base font-semibold text-ink">{{ $title ?? $slot }}</h3>
        @if ($description)
            <p {!! $id('description') !!}data-slot="modal-description" class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
        @if ($title !== null && $slot->isNotEmpty())
            {{ $slot }}
        @endif
    </div>
    <button type="button" data-acun-close aria-label="{{ __('Close') }}" @class(['flex-none cursor-pointer text-muted hover:text-danger-ink', 'mt-0.5' => $description])>
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
