@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    // Text or slot: to the left / right of the field.
    'prefix' => null,
    'suffix' => null,
    // Classes for the <input> itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

@php
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes);
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    $inputId = \Acun\Ui\Support\Field::id('input', $attributes, $fieldName, $label, $prefix, $suffix);
    // The error or hint below the field describes it for screen readers (as in acun:input).
    $messageId = $inputId.'-message';
    $describedBy = trim($attributes->get('aria-describedby').' '.($error || $hint ? $messageId : '')) ?: null;
    // The add-on sits on the field's own background; a line (::after) separates them and,
    // while the field has focus, takes the frame's colour.
    $addonClass = 'relative flex flex-none items-center px-3 text-sm text-body after:pointer-events-none after:absolute after:inset-y-0 after:w-px '
        .($error
            ? 'after:bg-danger-line group-focus-within/input-group:after:bg-danger-line'
            : 'after:bg-line-strong group-focus-within/input-group:after:bg-focus');
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // wrapper, so the label, the add-ons and the message follow them. The rest (name, wire:model,
    // type, required, aria-*, data-*, events…) belong to the <input>; input-class styles it
    // (Acun\Ui\Support\Field). The error frame is the frame around the add-ons, not the input's.
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5') }}>
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium text-body">
            {{ $label }}
            @if ($attributes->get('required'))<span class="text-danger-line" aria-hidden="true">*</span>@endif
        </label>
    @endif

    {{--
        The frame is a real border: an inset ring (as on acun:input) is painted under the
        children, so the add-ons covered it. On focus the 1px frame takes the brand colour
        and the focus shadow token of panel.css (none by default). A disabled or read-only field gets
        the inactive look of acun:input.
    --}}
    <div @class([
        'group/input-group flex h-10 overflow-hidden rounded-sm border bg-field transition-[border-color,box-shadow] duration-150',
        'has-[input:disabled]:cursor-not-allowed has-[input:disabled]:bg-field-disabled has-[input[readonly]]:bg-field-disabled',
        'border-danger-line focus-within:border-danger-line focus-within:shadow-(--ac-focus-shadow-danger)' => $error,
        'border-line-strong focus-within:border-focus focus-within:shadow-(--ac-focus-shadow)' => ! $error,
    ])>
        @if ($prefix || isset($before))
            <span class="{{ $addonClass }} after:end-0">{{ $before ?? $prefix }}</span>
        @endif

        {{-- w-full with min-w-0: the field never asks for its natural width (about 20 characters), which overflowed narrow grid columns. --}}
        <input
            id="{{ $inputId }}"
            name="{{ $fieldName }}"
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ \Acun\Ui\Support\Field::controlAttributes($attributes)->except(['id', 'label', 'hint', 'prefix', 'suffix', 'aria-describedby'])->merge([
                'type' => 'text',
                'class' => \Acun\Ui\Support\Field::controlClass('w-full min-w-0 flex-1 border-0 bg-transparent px-3 text-ink placeholder:text-muted focus:ring-0 focus:outline-none sm:text-sm disabled:cursor-not-allowed disabled:text-muted', $inputClass),
            ]) }}
        />

        @if ($suffix || isset($after))
            <span class="{{ $addonClass }} after:start-0">{{ $after ?? $suffix }}</span>
        @endif
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-danger-ink">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-muted">{{ $hint }}</p>
    @endif
</div>
