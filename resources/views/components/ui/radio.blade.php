@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'checked' => false,
    // Classes for the radio itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

@php
    // Like acun:checkbox: with only wire:model(.live…) the group name and the
    // id/for pair come from the bound property, so the label stays associated
    // and radios of different groups sharing a value get different ids.
    $fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
    // x-model radios too: without a name every one got id="radio-" and all labels pointed at the first.
    $group = $fieldName ?? $attributes->whereStartsWith('x-model')->first();
    $radioId = \Acun\Ui\Support\Field::id('radio', $attributes, filled($group) ? $group.'-'.$value : null, $label, $value);
    // The group's message is the page's (under the group); each radio says it is invalid.
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // label. The rest (name, wire:model, aria-*, data-*, events…) belong to the radio;
    // input-class styles it (Acun\Ui\Support\Field).
@endphp

<label for="{{ $radioId }}" {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('inline-flex items-center gap-2 text-sm text-gray-700 select-none has-disabled:cursor-not-allowed has-disabled:text-gray-400 dark:text-zinc-300 dark:has-disabled:text-zinc-500') }}>
    <input
        id="{{ $radioId }}"
        type="radio"
        name="{{ $fieldName }}"
        value="{{ $value }}"
        @checked($checked)
        @if ($error) aria-invalid="true" @endif
        {{ \Acun\Ui\Support\Field::controlAttributes($attributes)->except(['id', 'label', 'checked', 'value'])->merge([
            'class' => \Acun\Ui\Support\Field::controlClass('size-4 border-(--ui-control-border) text-brand-600 focus:ring-2 focus:ring-brand-600 aria-invalid:border-red-500 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-zinc-800', $inputClass),
        ]) }}
    />
    {{ $label ?? $slot }}
</label>
