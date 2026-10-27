{{--
    What opens and closes an acun:dropdown written in parts; wraps your button or link:
        <acun:dropdown.trigger>
            <acun:button variant="white">İşlemler <acun:icon name="chevron-down" size="4" /></acun:button>
        </acun:dropdown.trigger>
    A click toggles the panel; Arrow Down / Up open it on its first / last item. The first
    element inside, when it is a button or a link, gets aria-expanded (and aria-controls, the
    panel's id, from the runtime). Attributes go on the wrapper.
--}}
<div data-slot="dropdown-trigger" {{ $attributes }}>
    {!! \Acun\Ui\Support\Parts::controlAttributes($slot, 'aria-expanded="false"') !!}
</div>
