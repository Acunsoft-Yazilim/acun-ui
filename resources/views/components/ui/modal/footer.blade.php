{{--
    The button row of an acun:modal written in parts, as the classic footer slot:
    buttons at the end, on a grey band. Attributes (class…) go on the row.
--}}
<div data-slot="modal-footer" {{ $attributes->merge(['class' => 'flex flex-none items-center justify-end gap-2 border-t border-line bg-sunken/50 px-5 py-4']) }}>
    {{ $slot }}
</div>
