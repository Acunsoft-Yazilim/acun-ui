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
    $errorRing = 'ring-red-400 focus:ring-red-500 focus:shadow-(--ui-focus-shadow-error)';
    $ring = $error ? $errorRing : 'ring-gray-300 focus:ring-brand-600 focus:shadow-(--ui-focus-shadow) dark:ring-zinc-600';
    $keep = $error ? $errorRing : '';
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // wrapper. The rest (name, wire:model, required, aria-*, data-*, events…) belong to the
    // <textarea>; input-class styles it (Acun\Ui\Support\Field).
    $controlAttributes = \Acun\Ui\Support\Field::controlAttributes($attributes);
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5') }}>
    @if ($variant !== 'floating' && $label)
        <label for="{{ $areaId }}" class="text-sm font-medium text-gray-700 dark:text-zinc-300">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-red-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($variant === 'floating')
            <textarea
                id="{{ $areaId }}"
                name="{{ $fieldName }}"
                rows="{{ $rows }}"
                placeholder=" "
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'rows', 'value', 'aria-describedby'])->merge([
                    'class' => \Acun\Ui\Support\Field::controlClass('peer block w-full resize-y rounded-sm border-0 bg-transparent px-3 pt-6 pb-2 text-gray-900 ring-1 ring-inset transition placeholder:text-transparent focus:outline-none sm:text-sm dark:text-zinc-100 disabled:cursor-not-allowed disabled:opacity-60 '
                        . ($error ? $errorRing : 'ring-gray-300 hover:ring-gray-400 focus:ring-brand-600 focus:shadow-(--ui-focus-shadow) dark:ring-zinc-600 dark:hover:ring-zinc-500'), $inputClass, $keep),
                ]) }}
            >{{ $content }}</textarea>

            @if ($label)
                <label
                    for="{{ $areaId }}"
                    class="pointer-events-none absolute start-3 top-0 z-10 -translate-y-1/2 bg-white px-1 text-xs font-medium text-gray-600 transition-all peer-placeholder-shown:top-7 peer-placeholder-shown:bg-transparent peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-500 peer-placeholder-shown:dark:text-zinc-400 peer-focus:top-0 peer-focus:bg-white peer-focus:text-xs peer-focus:text-brand-600 dark:bg-zinc-900 dark:text-zinc-300 peer-placeholder-shown:dark:bg-transparent peer-focus:dark:bg-zinc-900 peer-focus:dark:text-brand-400"
                >
                    {{ $label }}
                    @if ($attributes->get('required'))
                        <span class="text-red-500" aria-hidden="true">*</span>
                    @endif
                </label>
            @endif
        @else
            <textarea
                id="{{ $areaId }}"
                name="{{ $fieldName }}"
                rows="{{ $rows }}"
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'rows', 'value', 'aria-describedby'])->merge([
                    'class' => \Acun\Ui\Support\Field::controlClass('block w-full rounded-sm border-0 py-1.5 px-3 text-gray-900 ring-1 ring-inset '
                        . $ring
                        . ' placeholder:text-gray-500 dark:placeholder:text-zinc-400 focus:outline-none sm:text-sm dark:bg-zinc-800 dark:text-zinc-100 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 [&[readonly]]:bg-gray-50 dark:disabled:bg-zinc-900 dark:disabled:text-zinc-500 dark:[&[readonly]]:bg-zinc-900', $inputClass, $keep),
                ]) }}
            >{{ $content }}</textarea>
        @endif
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-gray-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
