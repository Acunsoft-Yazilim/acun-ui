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
    // input-class styles it (Acun\Ui\Support\Field). Checked, it is filled with the primary colour and
    // its dot is that fill's own text colour (a light custom colour takes a dark dot). The keyboard
    // focus ring is acun:checkbox's: an outline in the focus token (@tailwindcss/forms' ring sat on a
    // white ring offset, a white halo in a dark theme).
@endphp

<label for="{{ $radioId }}" {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('inline-flex items-center gap-2 text-sm text-body select-none has-disabled:cursor-not-allowed has-disabled:text-faint') }}>
    <input
        id="{{ $radioId }}"
        type="radio"
        name="{{ $fieldName }}"
        value="{{ $value }}"
        @checked($checked)
        @if ($error) aria-invalid="true" @endif
        {{ \Acun\Ui\Support\Field::controlAttributes($attributes)->except(['id', 'label', 'checked', 'value'])->merge([
            'class' => \Acun\Ui\Support\Field::controlClass('size-4 border-control text-primary not-checked:bg-field checked:bg-[radial-gradient(circle_closest-side,var(--ac-on-primary)_33%,transparent_42%)] focus:!ring-0 focus:!ring-offset-0 focus-visible:!outline-2 focus-visible:!outline-offset-2 focus-visible:!outline-focus aria-invalid:border-danger-line disabled:cursor-not-allowed disabled:opacity-40', $inputClass),
        ]) }}
    />
    {{ $label ?? $slot }}
</label>
