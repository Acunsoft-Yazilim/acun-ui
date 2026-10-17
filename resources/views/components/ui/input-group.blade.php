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
    $addonClass = 'relative flex flex-none items-center px-3 text-sm text-gray-700 dark:text-zinc-300 after:pointer-events-none after:absolute after:inset-y-0 after:w-px '
        .($error
            ? 'after:bg-red-400 group-focus-within/input-group:after:bg-red-500'
            : 'after:bg-gray-300 dark:after:bg-zinc-600 group-focus-within/input-group:after:bg-brand-600');
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // wrapper, so the label, the add-ons and the message follow them. The rest (name, wire:model,
    // type, required, aria-*, data-*, events…) belong to the <input>; input-class styles it
    // (Acun\Ui\Support\Field). The error frame is the frame around the add-ons, not the input's.
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5') }}>
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium text-gray-700 dark:text-zinc-300">
            {{ $label }}
            @if ($attributes->get('required'))<span class="text-red-500" aria-hidden="true">*</span>@endif
        </label>
    @endif

    {{--
        The frame is a real border: an inset ring (as on acun:input) is painted under the
        children, so the add-ons covered it. On focus the 1px frame takes the brand colour
        and the focus shadow token of panel.css (none by default). A disabled or read-only field gets
        the inactive look of acun:input.
    --}}
    <div @class([
        'group/input-group flex h-10 overflow-hidden rounded-sm border bg-white transition-[border-color,box-shadow] duration-150 dark:bg-zinc-800',
        'has-[input:disabled]:cursor-not-allowed has-[input:disabled]:bg-gray-50 has-[input[readonly]]:bg-gray-50 dark:has-[input:disabled]:bg-zinc-900 dark:has-[input[readonly]]:bg-zinc-900',
        'border-red-400 focus-within:border-red-500 focus-within:shadow-(--ui-focus-shadow-error)' => $error,
        'border-gray-300 dark:border-zinc-600 focus-within:border-brand-600 focus-within:shadow-(--ui-focus-shadow)' => ! $error,
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
                'class' => \Acun\Ui\Support\Field::controlClass('w-full min-w-0 flex-1 border-0 bg-transparent px-3 text-gray-900 placeholder:text-gray-500 dark:placeholder:text-zinc-400 focus:ring-0 focus:outline-none sm:text-sm dark:text-zinc-100 disabled:cursor-not-allowed disabled:text-gray-500 dark:disabled:text-zinc-500', $inputClass),
            ]) }}
        />

        @if ($suffix || isset($after))
            <span class="{{ $addonClass }} after:start-0">{{ $after ?? $suffix }}</span>
        @endif
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-gray-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
