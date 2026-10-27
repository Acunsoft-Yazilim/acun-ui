{{--
    What opens and closes an acun:popover written in parts; wraps your button or link:
        <acun:popover.trigger><acun:button variant="white">Bilgi</acun:button></acun:popover.trigger>
    The first element inside, when it is a button or a link, gets aria-haspopup="dialog",
    aria-expanded (and aria-controls, the content's id, from the runtime). Attributes go on the wrapper.
--}}
<div data-slot="popover-trigger" {{ $attributes }}>
    {!! \Acun\Ui\Support\Parts::controlAttributes($slot, 'aria-haspopup="dialog" aria-expanded="false"') !!}
</div>
