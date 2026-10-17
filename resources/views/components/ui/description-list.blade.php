@props([
    // label => value pairs; values are escaped. Use acun:description-list.item in the slot for rich values.
    'items' => [],
])

{{--
    Label / value list, e.g. the details of an order or a license.
        <acun:description-list :items="['Sipariş no' => 'AC-1024', 'Tutar' => '₺1.240']" />
        <acun:description-list>
            <acun:description-list.item label="Durum"><acun:badge variant="success">Ödendi</acun:badge></acun:description-list.item>
        </acun:description-list>
    When the list itself is narrow (under 24rem: a phone, or a side card on a wide screen)
    the label sits above its value; the width of the list decides, not the screen's.
--}}
<dl {{ $attributes->class('@container divide-y divide-line-soft text-sm') }}>
    @foreach ($items as $label => $value)
        <acun:description-list.item :label="$label">{{ $value }}</acun:description-list.item>
    @endforeach

    {{ $slot }}
</dl>
