{{--
    Closes the acun:modal it is in: wrap the button that closes it.
        <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
    It works in a modal written in parts and in the classic footer slot alike. A click
    closes the modal without its request-close-event question (data-acun-close-now; the
    close-action still runs); the wrapper is layout-neutral (display: contents), so the
    button keeps its role. Attributes go on the wrapper.
    It needs no name: the modal plugin closes the modal the click is in, so the part also
    works in a nested Livewire component that re-renders on its own (without the modal
    around it on the server). Outside a modal a click says so in the console.
--}}
<span data-slot="modal-close" data-acun-close-now {{ $attributes->merge(['class' => 'contents']) }}>{{ $slot }}</span>
