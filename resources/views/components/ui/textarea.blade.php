@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'rows' => 4,
    // The text, when there is no slot (a <textarea> has no value attribute). A classic form gets old() back.
    'value' => null,
    'variant' => 'default',
    // Classes for the <textarea> itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

@php
    // whereStartsWith is used so attributes with modifiers such as
    // wire:model.live are caught too (see the same fix in acun:input).
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes);
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    $areaId = \Acun\Ui\Support\Field::id('textarea', $attributes, $fieldName, $label);
    // The slot, else the value (old() after a failed validation); wire:model / x-model fill it themselves.
    $content = $slot->isNotEmpty() ? $slot : (\Acun\Ui\Support\Field::bound($attributes) ? '' : \Acun\Ui\Support\Field::old($fieldName, $value));
    // The error or hint below the field describes it for screen readers.
    $messageId = $areaId.'-message';
    $describedBy = trim($attributes->get('aria-describedby').' '.($error || $hint ? $messageId : '')) ?: null;
    // The error frame (input-class never replaces it); the neutral dark ring only without an
    // error: in the dark theme it would hide the red one.
    // Focused, an invalid frame doubles in width: the red alone would not show the keyboard focus (WCAG 2.4.7).
    $errorRing = 'ring-danger-line focus:ring-2 focus:ring-danger-line focus:shadow-(--ac-focus-shadow-danger)';
    $ring = $error ? $errorRing : 'ring-control focus:ring-focus focus:shadow-(--ac-focus-shadow)';
    $keep = $error ? $errorRing : '';
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // wrapper. The rest (name, wire:model, required, aria-*, data-*, events…) belong to the
    // <textarea>; input-class styles it (Acun\Ui\Support\Field).
    $controlAttributes = \Acun\Ui\Support\Field::controlAttributes($attributes);
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5') }}>
    @if ($variant !== 'floating' && $label)
        <label for="{{ $areaId }}" class="text-sm font-medium text-body">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-danger-line" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($variant === 'floating')
            <textarea
                data-acun-control
                id="{{ $areaId }}"
                name="{{ $fieldName }}"
                rows="{{ $rows }}"
                placeholder=" "
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'rows', 'value', 'aria-describedby'])->merge([
                    'class' => \Acun\Ui\Support\Field::controlClass('peer block w-full resize-y rounded-sm border-0 bg-transparent px-3 pt-6 pb-2 text-ink ring-1 ring-inset transition placeholder:text-transparent focus:outline-hidden sm:text-sm disabled:cursor-not-allowed disabled:opacity-60 '
                        . ($error ? $errorRing : 'ring-control hover:ring-line-hover focus:ring-focus focus:shadow-(--ac-focus-shadow)'), $inputClass, $keep),
                ]) }}
            >{{ $content }}</textarea>

            @if ($label)
                <label
                    for="{{ $areaId }}"
                    class="pointer-events-none absolute start-3 top-0 z-10 -translate-y-1/2 bg-surface px-1 text-xs font-medium text-dim transition-all peer-placeholder-shown:top-7 peer-placeholder-shown:bg-transparent peer-placeholder-shown:text-sm peer-placeholder-shown:text-muted peer-focus:top-0 peer-focus:bg-surface peer-focus:text-xs peer-focus:text-primary-ink"
                >
                    {{ $label }}
                    @if ($attributes->get('required'))
                        <span class="text-danger-line" aria-hidden="true">*</span>
                    @endif
                </label>
            @endif
        @else
            <textarea
                data-acun-control
                id="{{ $areaId }}"
                name="{{ $fieldName }}"
                rows="{{ $rows }}"
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'rows', 'value', 'aria-describedby'])->merge([
                    'class' => \Acun\Ui\Support\Field::controlClass('block w-full rounded-sm border-0 py-1.5 px-3 bg-field text-ink ring-1 ring-inset '
                        . $ring
                        . ' placeholder:text-muted focus:outline-hidden sm:text-sm disabled:cursor-not-allowed disabled:bg-field-disabled disabled:text-muted [&[readonly]]:bg-field-disabled', $inputClass, $keep),
                ]) }}
            >{{ $content }}</textarea>
        @endif
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-danger-ink">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-muted">{{ $hint }}</p>
    @endif
</div>
