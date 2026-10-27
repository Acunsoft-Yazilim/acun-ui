@props([
    'label' => null,
    'name' => null,
    'checked' => false,
    // Classes for the box itself; class (with style, x-show, wire:key) places the whole field.
    'inputClass' => null,
])

@php
    // Attributes with modifiers such as wire:model.live/.lazy/.defer do not
    // match the name "wire:model" exactly, so whereStartsWith is used.
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes);
    $boxValue = $attributes->get('value');
    $group = $fieldName ?? $attributes->whereStartsWith('x-model')->first();
    $boxId = \Acun\Ui\Support\Field::id('checkbox', $attributes, filled($group) ? $group.(filled($boxValue) ? '-'.$boxValue : '') : null, $label);
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    // A single box ("I accept…") shows its message; the boxes of a list only turn red: a name
    // ending in [] (tags[]) or a box with its own value bound to an array (wire:model="tags").
    // The list's message is shown once, under it (acun:checkbox-group).
    $inList = str_ends_with((string) $fieldName, '[]') || (filled($boxValue) && \Acun\Ui\Support\Field::bound($attributes));
    $showsMessage = $error && ! $inList;
    $messageId = $boxId.'-message';
    // An aria-describedby of the page and the message, printed once.
    $describedBy = trim($attributes->get('aria-describedby').' '.($showsMessage ? $messageId : '')) ?: null;
    // Layout attributes (class, style, x-show, wire:key…) place the whole field: they go on the
    // label, its only root element. The rest (name, wire:model, value, aria-*, data-*, events…)
    // belong to the box; input-class styles it (Acun\Ui\Support\Field).
@endphp

{{--
    The label stays the root with or without an error (it used to become a <div> around the
    label and the message): a morph that brings the error keeps the element, and the box keeps
    its place in a flex row.
--}}
<label for="{{ $boxId }}" {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('inline-flex '.($showsMessage ? 'items-start' : 'items-center').' gap-2.5 text-sm text-body select-none') }}>
    <span @class(['relative flex size-4.5 flex-none items-center justify-center', 'mt-px' => $showsMessage])>
        <input
            data-acun-control
            id="{{ $boxId }}"
            type="checkbox"
            name="{{ $fieldName }}"
            @checked($checked)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ \Acun\Ui\Support\Field::controlAttributes($attributes)->except(['id', 'label', 'checked', 'aria-describedby'])->merge([
                'value' => '1',
                // The kit's form base (css/forms.css, data-acun-control) gives a
                // focused box a ring on a ring offset, as @tailwindcss/forms did;
                // '!' keeps it off whatever the order of the compiled CSS (an
                // application that still loads the plugin gets the plugin's too).
                // No outline-none: it sets the outline style to none for the
                // focus-visible ring too, which then never showed (on a mouse
                // focus the base's outline is transparent anyway).
                'class' => \Acun\Ui\Support\Field::controlClass('peer size-4.5 shrink-0 appearance-none rounded-(--ac-radius-check) border border-control bg-field checked:border-primary checked:bg-primary checked:bg-none indeterminate:border-primary indeterminate:bg-primary indeterminate:bg-none focus:!ring-0 focus:!ring-offset-0 focus-visible:!outline-2 focus-visible:!outline-offset-2 focus-visible:!outline-focus aria-invalid:border-danger-line disabled:cursor-not-allowed disabled:opacity-40', $inputClass),
            ]) }}
        />
        <svg class="pointer-events-none absolute size-3.5 text-on-primary opacity-0 peer-checked:opacity-100 peer-indeterminate:opacity-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{-- "Some selected" (input.indeterminate = true, e.g. a select-all box): a dash instead of the tick. --}}
        <svg class="pointer-events-none absolute size-3.5 text-on-primary opacity-0 peer-indeterminate:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 12h12"/></svg>
    </span>
    @if ($showsMessage)
        <span>
            @if ($label)
                <span class="block">{{ $label }}</span>
            @else
                {{ $slot }}
            @endif
            {{--
                Inside the label (as acun:switch); aria-hidden keeps it out of the label's name,
                the box's aria-describedby still reads it.
            --}}
            <span id="{{ $messageId }}" class="mt-0.5 block text-danger-ink" aria-hidden="true">{{ $error }}</span>
        </span>
    @elseif ($label)
        <span>{{ $label }}</span>
    @else
        {{ $slot }}
    @endif
</label>
