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

{{-- A group is named (label, aria-label or aria-labelledby); without a name it is no radiogroup (the radios share a name). --}}
<div
    @if ($label || $attributes->has('aria-label') || $attributes->has('aria-labelledby')) role="radiogroup" @endif
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->class(['gap-0.5 rounded-sm bg-sunken p-0.5', 'flex w-full' => $fullWidth, 'inline-flex' => ! $fullWidth]) }}
>
    @foreach ($options as $key => $text)
        <label @class(['relative', 'flex-1' => $fullWidth])>
            <input data-acun-control type="radio" name="{{ $fieldName }}" value="{{ $key }}" class="peer sr-only" @checked($current !== null && $current === (string) $key) {{ $binding }}>
            <span @class([
                'flex cursor-pointer items-center justify-center rounded-sm font-medium whitespace-nowrap text-dim transition-colors select-none hover:text-ink',
                // The chosen segment: a surface with a 3:1 edge (ring-control), not only a faint fill (WCAG 1.4.11).
                'peer-checked:bg-surface peer-checked:text-ink peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-control peer-checked:ring-inset',
                'peer-focus-visible:outline-2 peer-focus-visible:outline-offset-1 peer-focus-visible:outline-focus peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
                $sizeClass,
            ])>{{ $text }}</span>
        </label>
    @endforeach
</div>
