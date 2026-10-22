@props([
    // The chips: [value => label] or [value => ['label' => …, 'count' => …]].
    'options' => [],
    // The field name; without it, the wire:model / x-model property (an array when multiple).
    'name' => null,
    // The chosen value(s) (without wire:model / x-model).
    'value' => null,
    // More than one chip at a time (checkboxes) instead of one (radios).
    'multiple' => false,
    // The group's name for screen readers.
    'label' => null,
])

{{--
    Filter chips with counts, over a list or a table:
        <acun:chip-group wire:model.live="durum" label="Durum" :options="[
            'hepsi' => ['label' => 'Tümü', 'count' => 148],
            'bekliyor' => ['label' => 'Bekleyen', 'count' => 12],
            'kargoda' => ['label' => 'Kargoda', 'count' => 35],
        ]" />
    Native radios (or checkboxes with multiple) underneath: forms, wire:model, x-model and
    the keyboard work as with any field. Counts are written in the page's number format.
--}}
@php
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes) ?? \Acun\Ui\Support\Field::id('chips', $attributes, null, $options);
    $binding = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $chosen = \Acun\Ui\Support\Field::bound($attributes) ? [] : array_map('strval', (array) \Acun\Ui\Support\Field::old($name, $value));
    $inputName = $multiple && ! str_ends_with($fieldName, '[]') && $binding->isEmpty() ? $fieldName.'[]' : $fieldName;
@endphp

<div
    role="{{ $multiple ? 'group' : 'radiogroup' }}"
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->class('flex flex-wrap gap-2') }}
>
    @foreach ($options as $key => $option)
        @php
            $text = is_array($option) ? ($option['label'] ?? $key) : $option;
            $count = is_array($option) ? ($option['count'] ?? null) : null;
        @endphp
        <label class="relative">
            <input data-acun-control type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $inputName }}" value="{{ $key }}" class="peer sr-only" @checked(in_array((string) $key, $chosen, true)) {{ $binding }}>
            <span @class([
                'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1 text-sm font-medium text-body transition-colors select-none hover:border-line-strong',
                'peer-checked:border-primary-ink peer-checked:bg-primary-tint peer-checked:text-primary-tint-ink',
                'peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
            ])>
                {{ $text }}
                @if ($count !== null)
                    <span class="rounded-full bg-sunken px-1.5 text-xs tabular-nums text-dim">{{ is_numeric($count) ? \Acun\Ui\Support\Format::number((float) $count) : $count }}</span>
                @endif
            </span>
        </label>
    @endforeach
</div>
