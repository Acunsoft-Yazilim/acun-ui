@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    // #rrggbb; null: the wire:model property's value (Acun\Ui\Support\Bound), else empty.
    'value' => null,
    // Classes for the text box itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

{{--
    A colour field for a form (a product's colour, a brand colour saved in the settings): a text box with the
    #rrggbb value and, beside it, the browser's colour picker. The text box is the form control: wire:model,
    x-model, name, required and the validation error go on it, so the field works without JavaScript too
    (the value is typed). js/plugins/color-input.js keeps the picker and the box in step: a colour picked fills
    the box (with an input event for the binding), a value typed (also the short three-digit form, with or without the hash) moves the picker.
        <acun:color-input wire:model="renk" label="Ürün rengi" hint="Örneğin #c2410c" />
--}}
@php
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes);
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    $inputId = \Acun\Ui\Support\Field::id('color', $attributes, $fieldName, $label);
    $messageId = $inputId.'-message';
    $describedBy = trim($attributes->get('aria-describedby').' '.($error || $hint ? $messageId : '')) ?: null;
    $current = (string) ($value ?? \Acun\Ui\Support\Bound::value($attributes) ?? '');
    // The picker takes only #rrggbb: another value (empty, a typo) leaves it black until the box holds a colour.
    $swatch = preg_match('/^#[0-9a-f]{6}$/i', $current) === 1 ? strtolower($current) : '#000000';
    $disabled = (bool) $attributes->get('disabled');
    $errorRing = 'ring-danger-line focus:ring-2 focus:ring-danger-line focus:shadow-(--ac-focus-shadow-danger)';
    $ring = $error ? $errorRing : 'ring-control hover:ring-line-hover focus:ring-focus focus:shadow-(--ac-focus-shadow)';
    $controlAttributes = \Acun\Ui\Support\Field::controlAttributes($attributes);
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5 min-w-0') }}>
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium text-body">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-danger-line" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="flex items-center gap-2" data-acun-color-input>
        <input
            data-acun-control
            type="color"
            data-slot="color-swatch"
            value="{{ $swatch }}"
            aria-label="{{ $label ? __(':label: colour picker', ['label' => $label]) : __('Colour picker') }}"
            @disabled($disabled)
            class="h-10 w-12 flex-none cursor-pointer rounded-sm border-0 bg-field p-1 ring-1 ring-inset ring-control hover:ring-line-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:opacity-60 [&::-moz-color-swatch]:rounded-[3px] [&::-moz-color-swatch]:border-0 [&::-webkit-color-swatch]:rounded-[3px] [&::-webkit-color-swatch]:border-0 [&::-webkit-color-swatch-wrapper]:p-0"
        />
        <input
            data-acun-control
            data-slot="color-value"
            type="text"
            id="{{ $inputId }}"
            name="{{ $fieldName }}"
            value="{{ $current }}"
            maxlength="7"
            pattern="#[0-9a-fA-F]{6}"
            placeholder="#000000"
            autocomplete="off"
            spellcheck="false"
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $controlAttributes->except(['id', 'label', 'hint', 'aria-describedby', 'type', 'value'])->merge([
                'class' => \Acun\Ui\Support\Field::controlClass('block h-10 w-full min-w-0 rounded-sm border-0 bg-field px-3 font-mono text-ink uppercase ring-1 ring-inset '
                    .$ring
                    .' placeholder:text-muted placeholder:normal-case focus:outline-hidden sm:text-sm disabled:cursor-not-allowed disabled:bg-field-disabled disabled:text-muted', $inputClass, $error ? $errorRing : ''),
            ]) }}
        />
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-danger-ink">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-muted">{{ $hint }}</p>
    @endif
</div>
