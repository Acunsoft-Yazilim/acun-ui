@props([
    // The choices: [value => label]; their order is the order on screen.
    'options' => [],
    // The field name; without it, the wire:model / x-model property.
    'name' => null,
    // The chosen value (without wire:model / x-model).
    'value' => null,
    // The group's name for screen readers (there is no visible label).
    'label' => null,
    // sm · md
    'size' => 'md',
    // Takes the full width; the segments share it.
    'fullWidth' => false,
])

{{--
    A segmented control: one choice out of a few, side by side (a period, a view, a mode).
        <acun:segmented wire:model.live="donem" label="Dönem" :options="['gun' => 'Gün', 'hafta' => 'Hafta', 'ay' => 'Ay']" />
    Native radios underneath: a form sends the value, wire:model / x-model bind it, the arrow
    keys move between the segments and Tab enters the group once. Attributes other than
    wire:model / x-model go on the group.
--}}
@php
    $fieldName = \Acun\Ui\Support\Field::name($name, $attributes) ?? \Acun\Ui\Support\Field::id('segmented', $attributes, null, $options);
    $binding = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $current = \Acun\Ui\Support\Field::bound($attributes) ? null : (string) \Acun\Ui\Support\Field::old($name, $value);
    $sizeClass = $size === 'sm' ? 'px-2.5 py-1 text-xs' : 'px-3.5 py-1.5 text-sm';
@endphp

<div
    role="radiogroup"
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->class(['gap-0.5 rounded-sm bg-gray-100 p-0.5 dark:bg-zinc-800', 'flex w-full' => $fullWidth, 'inline-flex' => ! $fullWidth]) }}
>
    @foreach ($options as $key => $text)
        <label @class(['relative', 'flex-1' => $fullWidth])>
            <input type="radio" name="{{ $fieldName }}" value="{{ $key }}" class="peer sr-only" @checked($current !== null && $current === (string) $key) {{ $binding }}>
            <span @class([
                'flex cursor-pointer items-center justify-center rounded-sm font-medium whitespace-nowrap text-gray-600 transition-colors select-none hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                'peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow-sm dark:peer-checked:bg-zinc-900 dark:peer-checked:text-zinc-100',
                'peer-focus-visible:outline-2 peer-focus-visible:outline-offset-1 peer-focus-visible:outline-focus peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
                $sizeClass,
            ])>{{ $text }}</span>
        </label>
    @endforeach
</div>
