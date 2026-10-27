@props([
    'name',
    'title' => null,
    'maxWidth' => 'md',
    'closeAction' => null,
    'ariaLabel' => null,
    'closableOnBackdrop' => true,
    'requestCloseEvent' => null,
    // A question that needs an answer (confirmation-dialog): role="alertdialog".
    'alert' => false,
])

@php
    $widths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];

    // Written in parts (acun:modal.header, acun:modal.body, acun:modal.footer), the
    // slot fills the panel as it is. modal.trigger and modal.close are not layout
    // parts: a classic body may start with one. (The @if around the slot below sits
    // at column 0 so that the classic output stays byte for byte the same.)
    $slotHtml = $slot->toHtml();
    $startsWithParts = fn (string $html): bool => \Acun\Ui\Support\Parts::used($html, 'modal')
        && ! preg_match('/^(?:\s+|<!--.*?-->)*<[a-z][\w-]*\b[^>]*\sdata-slot="modal-(?:trigger|close)"/i', $html);
    // A nested Livewire component first (wire:id): Livewire draws it whole only on the first
    // render and leaves an empty stub on the page's later updates, so the markup around it
    // must not follow what it draws. The classic body stays around it and gives way to its
    // parts in the browser (CSS :has below); its header names the dialog where the server
    // sees it, and acunModal keeps that name on the later updates.
    $nested = \Acun\Ui\Support\Parts::nestedComponent($slotHtml);
    $parts = $nested === null && $startsWithParts($slotHtml);
    // A modal.header takes the place of the title row, and its title (and description)
    // name the dialog. Their ids are read from the header itself: inside the template
    // of a component with a name prop of its own, @aware hands the header that outer
    // name. Without a header, the title prop still draws the title row.
    $headerPart = $parts && str_contains($slotHtml, 'data-slot="modal-header"');
    $nestedHeader = $nested !== null && $startsWithParts($nested) && str_contains($nested, 'data-slot="modal-header"');
    $headerKey = ($headerPart || $nestedHeader) && preg_match('/\sdata-slot="modal-header"(?:[^>"\']|"[^"]*"|\'[^\']*\')*>\s*<div class="min-w-0">\s*<h3 id="([^"]*)-title"/', $slotHtml, $match)
        ? htmlspecialchars_decode($match[1], ENT_QUOTES)
        : null;
    $titleRow = $title && ! $headerPart;
    $labelledBy = $titleRow ? $name.'-title' : ($headerKey !== null ? $headerKey.'-title' : null);
    $describedBy = $headerKey !== null && str_contains($slotHtml, 'id="'.e($headerKey).'-description"') ? $headerKey.'-description' : null;
    // A classic body's text with the id {name}-description describes the dialog too.
    $describedBy ??= $titleRow && str_contains($slotHtml, 'id="'.e($name).'-description"') ? $name.'-description' : null;
@endphp

{{--
    The behaviour is the modal plugin of the @acunsoft/acun-ui runtime (js/plugins/modal.js), plain
    JavaScript bound to this markup by data-* attributes, the same HTML the free npm package documents;
    Escape, Tab, the page scroll lock and the focus restore come from its overlay manager. One modal
    is open at a time: opening one closes the other.
    To open it from anywhere: data-acun-open="{{ $name }}" on a button, AcunUI.modal.open('{{ $name }}'),
    $dispatch('acun:modal:open', { name: '{{ $name }}' }) (the first name open-modal works too), or
    wrap the button in <acun:modal.trigger name="{{ $name }}">.
    To close it: AcunUI.modal.close('{{ $name }}') or acun:modal:close (close-modal); without a
    name every modal closes. Inside the modal: data-acun-close on a button (the X, Escape and the
    backdrop do the same: through request-close-event when there is one), data-acun-close-now
    (no question), or <acun:modal.close>. (close() and requestClose() of the 2.0 Alpine scope still
    work until 3.0.)
    It sends acun:modal:opened ({ name }) and acun:modal:closed / modal-closed ({ name, reason: user · command · removed }).
    wire:model / x-model bind the open state both ways: <acun:modal name="…" wire:model="showForm">
    opens when the property is true and sets it to false when the user closes it.

    Written in parts:
        <acun:modal name="musteri-ekle">
            <acun:modal.header title="Yeni müşteri" description="…" />
            <acun:modal.body>…</acun:modal.body>
            <acun:modal.footer>
                <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
                <acun:button type="submit">Kaydet</acun:button>
            </acun:modal.footer>
        </acun:modal>
    The header's title names the dialog (aria-labelledby) and its description describes it.
    The parts may also be drawn by a nested Livewire component (<livewire:musteri-formu />
    as the modal's content, its root starting with acun:modal.header or acun:modal.body).

    Closed, the root is hidden; open, it and its backdrop and panel carry data-open, and the
    transitions are CSS (data-open: classes, motion-reduce: none).
--}}
<div
    data-acun-modal="{{ $name }}"
    @if ($closeAction) data-close-action="{{ $closeAction }}" @endif
    @if ($requestCloseEvent) data-request-close-event="{{ $requestCloseEvent }}" @endif
    hidden
    {{ $attributes->merge(['class' => 'pointer-events-none fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 data-open:pointer-events-auto']) }}
>
    <div
        data-slot="modal-backdrop"
        @if ($closableOnBackdrop) data-acun-close @endif
        class="ui-backdrop fixed inset-0 opacity-0 transition-opacity duration-150 ease-in data-open:opacity-100 data-open:ease-out motion-reduce:transition-none"
        aria-hidden="true"
    ></div>

    <div
        data-slot="modal-panel"
        role="{{ $alert ? 'alertdialog' : 'dialog' }}"
        aria-modal="true"
        @if ($labelledBy) aria-labelledby="{{ $labelledBy }}"@if ($describedBy) aria-describedby="{{ $describedBy }}"@endif @else aria-label="{{ $ariaLabel ?? __('Dialog') }}" @endif
        tabindex="-1"
        {{-- Written in parts, a nested Livewire component that starts with a part (body and footer after the header) is layout-neutral, so its parts are the panel's rows. --}}
        class="relative z-10 flex max-h-[85vh] w-full flex-col {{ $widths[$maxWidth] ?? $widths['md'] }} overflow-hidden rounded-sm bg-surface shadow-xl scale-95 opacity-0 transition-[opacity,scale] duration-150 ease-in data-open:scale-100 data-open:opacity-100 data-open:duration-200 data-open:ease-out motion-reduce:transition-none{{ $parts ? ' [&>[wire\:id]:has(>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents' : '' }}"
    >
        @if ($titleRow)
            <div class="flex flex-none items-center justify-between border-b border-line px-5 py-4">
                <h3 id="{{ $name }}-title" class="text-base font-semibold text-ink">{{ $title }}</h3>
                <button type="button" data-acun-close aria-label="{{ __('Close') }}" class="cursor-pointer text-muted hover:text-danger-ink">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

@if ($parts)
        {{ $slot }}
@elseif ($nested !== null)
        {{-- The same on every update: when the component starts with a part, this body and the component's root give way (display: contents) and the parts are the panel's rows. --}}
        <div class="flex-1 overflow-y-auto p-5 [&:has(>[wire\:id]>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents [&>[wire\:id]:has(>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents">
            {{ $slot }}
        </div>
@else
        <div class="flex-1 overflow-y-auto p-5">
            {{ $slot }}
        </div>
@endif

        @isset($footer)
            <div class="flex flex-none items-center justify-end gap-2 border-t border-line bg-sunken/50 px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
