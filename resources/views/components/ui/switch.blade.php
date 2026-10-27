@props([
    'label' => null,
    'description' => null,
    'name' => null,
    'checked' => false,
    'size' => 'md',
    // Classes for the (visually hidden) checkbox itself; class (with style, x-show, wire:key) places the whole switch.
    'inputClass' => null,
])

@php
    $fieldName = $name ?? $attributes->get('name') ?? $attributes->whereStartsWith('wire:model')->first();
    // Switches bound to the same array (wire:model="channels" value="sms") need the value in the id too.
    $switchValue = $attributes->get('value');
    // The same on every render (Livewire's morph matches the switch by it); the same name twice gets -2.
    $switchId = \Acun\Ui\Support\Field::id('switch', $attributes, $fieldName ? 'switch-'.$fieldName.(filled($switchValue) ? '-'.$switchValue : '') : null, $label, $description);
    [$trackClass, $thumbClass, $slideClass] = $size === 'sm' ? ['h-4 w-7', 'size-3', 'peer-checked:translate-x-3'] : ['h-5 w-9', 'size-4', 'peer-checked:translate-x-4'];
    $error = \Acun\Ui\Support\Field::error($fieldName, $errors ?? null);
    // A switch of a list (a name ending in [] or a value bound to an array) only says it is
    // invalid; the list's message is shown once, under it (as acun:checkbox).
    $inList = str_ends_with((string) $fieldName, '[]') || (filled($switchValue) && \Acun\Ui\Support\Field::bound($attributes));
    $showsMessage = $error && ! $inList;
    $messageId = $switchId.'-message';
    $describedBy = trim($attributes->get('aria-describedby').' '.($showsMessage ? $messageId : '')) ?: null;
    // Layout attributes (class, style, x-show, wire:key…) place the whole switch: they go on the
    // label. The rest (name, wire:model, value, aria-*, data-*, events…) belong to the checkbox.
    $isLayout = fn (string $key): bool => in_array($key, ['class', 'style', ':class', ':style', 'x-bind:class', 'x-bind:style', 'x-show', 'x-cloak', 'wire:key', 'wire:show'], true)
        || str_starts_with($key, 'x-transition') || str_starts_with($key, 'wire:transition');
    $controlClass = \Illuminate\Support\Arr::toCssClasses($inputClass ?? []);
@endphp

<label for="{{ $switchId }}" {{ $attributes->filter(fn ($value, $key) => $isLayout($key))->class('inline-flex cursor-pointer items-start gap-3 select-none has-disabled:cursor-not-allowed has-disabled:opacity-50') }}>
    <span class="relative mt-0.5 inline-flex flex-none">
        <input
            data-acun-control
            id="{{ $switchId }}"
            type="checkbox"
            role="switch"
            name="{{ $fieldName }}"
            @checked($checked)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->filter(fn ($value, $key) => ! $isLayout($key))->except(['id', 'label', 'checked', 'aria-describedby'])->merge(['value' => '1', 'class' => 'peer sr-only'.($controlClass !== '' ? ' '.$controlClass : '')]) }}
        />
        {{-- In forced colors (Windows high contrast) the track keeps an edge, the thumb a fill and an on switch the Highlight colour. --}}
        <span class="{{ $trackClass }} rounded-full bg-control transition-colors peer-checked:bg-primary peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus peer-aria-invalid:ring-2 peer-aria-invalid:ring-danger-line forced-colors:border forced-colors:border-[ButtonText] forced-colors:peer-checked:bg-[Highlight]"></span>
        <span class="{{ $thumbClass }} {{ $slideClass }} absolute top-0.5 left-0.5 rounded-full bg-on-primary shadow transition-transform motion-reduce:transition-none forced-colors:bg-[ButtonText]"></span>
    </span>

    @if ($label || $description || $slot->isNotEmpty() || $showsMessage)
        <span class="text-sm">
            @if ($label || $slot->isNotEmpty())
                <span class="font-medium text-body">{{ $label ?? $slot }}</span>
            @endif
            @if ($description)
                <span class="block text-muted">{{ $description }}</span>
            @endif
            @if ($showsMessage)
                {{--
                    Inside the label, so the switch keeps its root element (and its place in a row);
                    aria-hidden keeps it out of the label's name, aria-describedby still reads it.
                --}}
                <span id="{{ $messageId }}" class="mt-0.5 block text-danger-ink" aria-hidden="true">{{ $error }}</span>
            @endif
        </span>
    @endif
</label>
