{{--
    Closes the acun:drawer it is in: wrap the button that closes it.
        <acun:drawer.close><acun:button variant="white">Kapat</acun:button></acun:drawer.close>
    It works in a drawer written in parts and in the classic footer slot alike. A click
    calls the drawer's close(); the wrapper is layout-neutral (display: contents), so
    the button keeps its role. Attributes go on the wrapper.
    It needs no name: close() comes from the drawer's Alpine scope, so the part also
    works in a nested Livewire component that re-renders on its own (without the drawer
    around it on the server). Outside a drawer a click says so in the console.
--}}
<span data-slot="drawer-close" x-on:click="$el.closest('[data-acun-drawer]') ? close() : console.error('acun:drawer.close is not inside an acun:drawer.')" {{ $attributes->merge(['class' => 'contents']) }}>{{ $slot }}</span>
