{{--
    The scrollable content of an acun:modal written in parts, padded as the classic
    body. Attributes (class…) go on the content box.
--}}
<div data-slot="modal-body" {{ $attributes->merge(['class' => 'flex-1 overflow-y-auto p-5']) }}>
    {{ $slot }}
</div>
