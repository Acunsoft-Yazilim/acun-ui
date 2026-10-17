@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'options' => [],
    'placeholder' => null,
    // The selected option(s) of a classic form (a <select> has no value attribute); old() wins after a failed validation.
    'value' => null,
    'variant' => 'default',
    // true: re-apply the wire:model value when the box is added by a Livewire update (see the
    // x-init below). null follows config('acun-ui.select_sync').
    'sync' => null,
    // Classes for the <select> itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

@php
    // whereStartsWith is used so attributes with modifiers such as
    // wire:model.live are caught too (see the same fix in acun:input) -
    // otherwise $selectId changed randomly on every render and kept the DOM
    // morph from recognising the element as "the same" one.
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes);
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    $selectId = \Acun\Ui\Support\Field::id('select', $attributes, $fieldName, $label, $placeholder);
    // Bound (wire:model / x-model): the model picks the option. Otherwise the value prop, or old().
    $bound = \Acun\Ui\Support\Field::bound($attributes);
    $current = $bound ? null : \Acun\Ui\Support\Field::old($fieldName, $value);
    $chosen = array_map('strval', array_filter((array) $current, fn ($item) => $item !== null && $item !== ''));
    $floating = $variant === 'floating';
    // The error or hint below the field describes it for screen readers.
    $messageId = $selectId.'-message';
    $describedBy = trim($attributes->get('aria-describedby').' '.($error || $hint ? $messageId : '')) ?: null;
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // wrapper. The rest (name, wire:model, required, aria-*, data-*, events…) belong to the
    // <select>; input-class styles it, never over the error frame (Acun\Ui\Support\Field).
    $controlAttributes = \Acun\Ui\Support\Field::controlAttributes($attributes);
    $errorRing = 'ring-red-400 focus:ring-red-500 focus:shadow-(--ui-focus-shadow-error)';
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5') }}>
    @if (! $floating && $label)
        <label for="{{ $selectId }}" class="text-sm font-medium text-gray-700 dark:text-zinc-300">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-red-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        {{--
            x-init (only with sync, or config('acun-ui.select_sync')): when the box was added LATER
            by a Livewire update (e.g. a field revealed by @if, content loaded into a side panel)
            no option stayed selected even though the wire:model value was correct; the value
            is re-applied on the next tick.
        --}}
        <select
            id="{{ $selectId }}"
            name="{{ $fieldName }}"
            @if (($sync ?? config('acun-ui.select_sync', false)) && $attributes->whereStartsWith('wire:model')->isNotEmpty() && ! $attributes->has('x-init'))
                x-init="$nextTick(() => $el._x_forceModelUpdate?.($el._x_model?.get()))"
            @endif
            @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'options', 'placeholder', 'value', 'aria-describedby'])->merge([
                'class' => \Acun\Ui\Support\Field::controlClass('block w-full appearance-none rounded-sm border-0 text-gray-900 ring-1 ring-inset transition-colors '
                    . ($floating ? 'peer flex h-[45px] items-center pl-3 pr-9' : 'flex h-10 items-center pl-3 pr-9')
                    . ' '
                    . ($error ? $errorRing : 'ring-gray-300 hover:ring-gray-400 focus:ring-brand-600 focus:shadow-(--ui-focus-shadow) dark:ring-zinc-600 dark:hover:ring-zinc-500')
                    . ' focus:outline-none sm:text-sm dark:bg-zinc-800 dark:text-zinc-100 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 dark:disabled:bg-zinc-900 dark:disabled:text-zinc-500'
                    . ($floating ? ' rounded-sm' : ''), $inputClass, $error ? $errorRing : ''),
            ]) }}
        >
            {{--
                Not disabled: with no option selected (a null model value, which matches
                no option) the browser selects the first option that is not disabled, and
                that must be the placeholder, not the first real option. hidden keeps it
                out of the list, as disabled did.
            --}}
            @if ($placeholder)
                <option value="" hidden @selected($chosen === [])>{{ $placeholder }}</option>
            @elseif ($bound)
                {{-- Without it a null model would show the first option, a value the model does not have. --}}
                <option value="" hidden></option>
            @endif

            {{ $slot }}

            @foreach ($options as $optionValue => $optionText)
                <option value="{{ $optionValue }}"{{ in_array((string) $optionValue, $chosen, true) ? ' selected' : '' }}>{{ $optionText }}</option>
            @endforeach
        </select>

        <svg class="pointer-events-none absolute inset-y-0 right-3 my-auto size-4 text-gray-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
        </svg>

        @if ($floating && $label)
            <label
                for="{{ $selectId }}"
                class="pointer-events-none absolute start-3 top-0 z-10 -translate-y-1/2 bg-white px-1 text-xs font-medium text-gray-600 transition-colors peer-focus:text-brand-600 dark:bg-zinc-900 dark:text-zinc-300 dark:peer-focus:text-brand-400"
            >
                {{ $label }}
                @if ($attributes->get('required'))
                    <span class="text-red-500" aria-hidden="true">*</span>
                @endif
            </label>
        @endif
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-gray-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
