{{--
    Closes the acun:modal it is in: wrap the button that closes it.
        <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
    It works in a modal written in parts and in the classic footer slot alike. A click
    calls the modal's close() (which also runs its close-action); the wrapper is
    layout-neutral (display: contents), so the button keeps its role. Attributes go on
    the wrapper.
    It needs no name: close() comes from the modal's Alpine scope, so the part also
    works in a nested Livewire component that re-renders on its own (without the modal
    around it on the server). Outside a modal a click says so in the console.
--}}
<span data-slot="modal-close" x-on:click="$el.closest('[data-acun-modal]') ? close() : console.error('acun:modal.close is not inside an acun:modal.')" {{ $attributes->merge(['class' => 'contents']) }}>{{ $slot }}</span>
