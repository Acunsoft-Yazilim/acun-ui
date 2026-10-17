@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'viewable' => false,
    'variant' => 'default',
    // Classes for the <input> itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

@php
    // Attributes with modifiers such as wire:model.live/.debounce.400ms do not
    // match the name "wire:model" exactly, so whereStartsWith is used
    // (the established pattern from acun:checkbox) - otherwise $fieldName (and
    // therefore $inputId) fell back to a different random value on every
    // render, which kept Livewire's DOM morph from recognising the input as
    // "the same element" and caused focus loss on every update
    // (the real reason search boxes felt "as if the page was reloading"
    // on every keystroke).
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes);
    // address[city] is address.city in the error bag; no error bag shared (a mail, a PDF): no error.
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    $inputId = \Acun\Ui\Support\Field::id('input', $attributes, $fieldName, $label);
    $type = $attributes->get('type', 'text');
    // The error or hint below the field describes it for screen readers.
    $messageId = $inputId.'-message';
    $describedBy = trim($attributes->get('aria-describedby').' '.($error || $hint ? $messageId : '')) ?: null;
    // The error frame (input-class never replaces it); the neutral dark ring only without an
    // error: in the dark theme it would hide the red one.
    $errorRing = 'ring-red-400 focus:ring-red-500 focus:shadow-(--ui-focus-shadow-error)';
    $ring = $error ? $errorRing : 'ring-gray-300 focus:ring-brand-600 focus:shadow-(--ui-focus-shadow) dark:ring-zinc-600';
    $keep = $error ? $errorRing : '';
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // wrapper, so the label and the message follow them. The rest (name, wire:model, required,
    // aria-*, data-*, events…) belong to the <input>; input-class styles it (Acun\Ui\Support\Field).
    $controlAttributes = \Acun\Ui\Support\Field::controlAttributes($attributes);
@endphp

<div {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-1.5') }}>
    @if ($variant !== 'floating' && $label)
        <label for="{{ $inputId }}" class="text-sm font-medium text-gray-700 dark:text-zinc-300">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-red-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative" @if ($viewable) x-data="{ shown: false }" @endif>
        @if ($variant === 'floating')
            <input
                id="{{ $inputId }}"
                name="{{ $fieldName }}"
                placeholder=" "
                @if ($viewable) :type="shown ? 'text' : 'password'" @endif
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'aria-describedby', 'placeholder'])->merge([
                    'class' => \Acun\Ui\Support\Field::controlClass('peer block h-[45px] w-full rounded-sm border-0 bg-transparent px-3 pt-1.5 text-gray-900 ring-1 ring-inset transition placeholder:text-transparent focus:outline-none sm:text-sm dark:text-zinc-100 disabled:cursor-not-allowed disabled:opacity-60 '
                        . ($error ? $errorRing : 'ring-gray-300 hover:ring-gray-400 focus:ring-brand-600 focus:shadow-(--ui-focus-shadow) dark:ring-zinc-600 dark:hover:ring-zinc-500')
                        . ($viewable ? ' pe-11' : ''), $inputClass, $keep),
                ]) }}
            />

            @if ($label)
                <label
                    for="{{ $inputId }}"
                    class="pointer-events-none absolute start-3 top-0 z-10 -translate-y-1/2 bg-white px-1 text-xs font-medium text-gray-600 transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:bg-transparent peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-500 peer-placeholder-shown:dark:text-zinc-400 peer-focus:top-0 peer-focus:bg-white peer-focus:text-xs peer-focus:text-brand-600 dark:bg-zinc-900 dark:text-zinc-300 peer-placeholder-shown:dark:bg-transparent peer-focus:dark:bg-zinc-900 peer-focus:dark:text-brand-400"
                >
                    {{ $label }}
                    @if ($attributes->get('required'))
                        <span class="text-red-500" aria-hidden="true">*</span>
                    @endif
                </label>
            @endif
        @else
            <input
                id="{{ $inputId }}"
                name="{{ $fieldName }}"
                @if ($viewable) :type="shown ? 'text' : 'password'" @endif
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $controlAttributes->except(['id', 'label', 'hint', 'aria-describedby'])->merge([
                    'class' => \Acun\Ui\Support\Field::controlClass('block h-10 w-full rounded-sm border-0 '.($type === 'search' ? 'ps-9 pe-3' : 'px-3').' text-gray-900 ring-1 ring-inset '
                        . $ring
                        . ' placeholder:text-gray-500 dark:placeholder:text-zinc-400 focus:outline-none sm:text-sm dark:bg-zinc-800 dark:text-zinc-100 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 [&[readonly]]:bg-gray-50 dark:disabled:bg-zinc-900 dark:disabled:text-zinc-500 dark:[&[readonly]]:bg-zinc-900'
                        . ($viewable ? ' pe-10' : ''), $inputClass, $keep),
                ]) }}
            />

            @if ($type === 'search')
                {{-- Magnifier on the left in search boxes. --}}
                <svg class="pointer-events-none absolute inset-y-0 start-3 my-auto size-4 text-gray-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            @endif
        @endif

        @if ($viewable)
            <button
                type="button"
                @click="shown = !shown"
                class="absolute inset-y-0 z-20 flex items-center text-gray-500 hover:text-gray-600 end-3 dark:hover:text-zinc-200"
                :aria-pressed="shown.toString()"
                aria-label="{{ __('Show password') }}"
            >
                <svg x-show="!shown" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <svg x-show="shown" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
            </button>
        @endif
    </div>

    @if ($error)
        <p id="{{ $messageId }}" class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $messageId }}" class="text-sm text-gray-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
