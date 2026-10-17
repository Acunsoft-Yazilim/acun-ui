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
    The behaviour is acunModal of the @acunsoft/acun-ui runtime (js/components/modal.js); Escape,
    Tab, the page scroll lock and the focus restore come from its overlay manager. One modal
    is open at a time: opening one closes the other.
    To open it from anywhere: AcunUI.modal.open('{{ $name }}'), $dispatch('acun:modal:open',
    { name: '{{ $name }}' }) (the first name open-modal works too), or wrap the button in
    <acun:modal.trigger name="{{ $name }}">.
    To close it: AcunUI.modal.close('{{ $name }}') or acun:modal:close (close-modal); without a
    name every modal closes. Inside the modal: <acun:modal.close>, close() or requestClose().
    It sends acun:modal:opened and acun:modal:closed / modal-closed ({ name }).
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
--}}
<div
    x-data="acunModal(@js(['name' => $name, 'closeAction' => $closeAction, 'requestCloseEvent' => $requestCloseEvent]))"
    @if ($attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty()) x-modelable="model" @endif
    data-acun-modal="{{ $name }}"
    :class="open ? 'pointer-events-auto' : 'pointer-events-none'"
    x-cloak
    {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4']) }}
>
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @if ($closableOnBackdrop)
            x-on:click="if ($event.target === $event.currentTarget) requestClose()"
        @endif
        class="ui-backdrop fixed inset-0"
        aria-hidden="true"
    ></div>

    <div
        x-ref="panel"
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="{{ $alert ? 'alertdialog' : 'dialog' }}"
        aria-modal="true"
        @if ($labelledBy) aria-labelledby="{{ $labelledBy }}"@if ($describedBy) aria-describedby="{{ $describedBy }}"@endif @else aria-label="{{ $ariaLabel ?? __('Dialog') }}" @endif
        tabindex="-1"
        {{-- Written in parts, a nested Livewire component that starts with a part (body and footer after the header) is layout-neutral, so its parts are the panel's rows. --}}
        class="relative z-10 flex max-h-[85vh] w-full flex-col {{ $widths[$maxWidth] ?? $widths['md'] }} overflow-hidden rounded-sm bg-white shadow-xl dark:bg-zinc-900{{ $parts ? ' [&>[wire\:id]:has(>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents' : '' }}"
    >
        @if ($titleRow)
            <div class="flex flex-none items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-zinc-700">
                <h3 id="{{ $name }}-title" class="text-base font-semibold text-gray-900 dark:text-zinc-100">{{ $title }}</h3>
                <button type="button" x-on:click="requestClose()" aria-label="{{ __('Close') }}" class="cursor-pointer text-gray-500 hover:text-red-600 dark:hover:text-red-400">
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
            <div class="flex flex-none items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
