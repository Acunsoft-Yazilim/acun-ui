@aware(['headings' => true])
@props([
    // The section's key: data-switcher-section="…" on it.
    'name' => null,
    // The heading; it names the section's group (and its radio group) for screen readers.
    'title',
    // A line under the heading.
    'description' => null,
    // One choice out of a few, as native radios: value => label, or value => ['label' => …,
    // 'description' => …, 'icon' => …]. Side by side in a segmented bar; as cards when one has a description.
    'options' => [],
    // With options: the Alpine expression of the chosen value ($store.navbar.mode).
    'value' => null,
    // With options: the Alpine expression of the function a choice goes to ($store.navbar.set).
    'change' => null,
    // Whether the section is a group named by its heading; null: when it has no options (its radio
    // group is named instead). false: the slot names its own controls (aria-labelledby the heading's id).
    'group' => null,
])

{{--
    A section of acun:theme-switcher: a heading and a radio group (options) or your own controls (the
    slot). The switcher's own sections and the ones a package adds (Appearance::section()) use it,
    and so can an application's, in the switcher's slot:

        <acun:theme-switcher as="drawer">
            <acun:theme-switcher.section name="density" :title="__('Density')"
                :options="['comfortable' => __('Comfortable'), 'compact' => __('Compact')]"
                value="$store.density.value" change="$store.density.set" />
        </acun:theme-switcher>

    Native radios: Tab enters the group once, the arrow keys move the choice, and a screen reader
    reads the heading as the group's name. The heading's id is the id attribute when one is given
    (the slot's controls can point at it), else a generated one. The heading is for screen readers
    only when the switcher's headings prop is false.
--}}
@php
    $headingId = \Acun\Ui\Support\Field::id('theme-switcher-section', $attributes, null, $name, $title);
    $choices = collect($options)->map(fn ($option) => is_array($option)
        ? ['label' => (string) ($option['label'] ?? ''), 'description' => $option['description'] ?? null, 'icon' => $option['icon'] ?? null]
        : ['label' => (string) $option, 'description' => null, 'icon' => null])->all();
    $cards = collect($choices)->contains(fn (array $option) => filled($option['description']));
    $group ??= $choices === [];
@endphp

<div {{ $attributes->except('id')->class('space-y-2.5') }}@if ($name) data-switcher-section="{{ $name }}"@endif @if ($group) role="group" aria-labelledby="{{ $headingId }}"@endif>
    <h4 id="{{ $headingId }}" @class(['font-mono text-2xs font-medium tracking-[0.08em] text-muted uppercase', 'sr-only' => ! $headings])>{{ $title }}</h4>

    @if ($description)
        <p class="text-xs text-muted">{{ $description }}</p>
    @endif

    @if ($choices !== [])
        <div
            role="radiogroup"
            aria-labelledby="{{ $headingId }}"
            @if ($change) x-on:change="({{ $change }})($event.target.value)" @endif
            @class(['grid grid-cols-2 gap-2' => $cards, 'flex gap-1 rounded-sm bg-sunken p-1' => ! $cards])
        >
            @foreach ($choices as $key => $option)
                <label @class(['relative', 'flex-1' => ! $cards])>
                    <input data-acun-control type="radio" name="{{ $headingId }}" value="{{ $key }}" class="peer sr-only" @if ($value) x-bind:checked="({{ $value }}) === @js((string) $key)" @endif>
                    @if ($cards)
                        <span class="block h-full cursor-pointer rounded-sm p-3 text-left ring-1 ring-line ring-inset transition-shadow hover:ring-line-strong peer-checked:ring-2 peer-checked:ring-primary-ink peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus">
                            <span class="block text-sm font-medium text-ink">{{ $option['label'] }}</span>
                            <span class="block text-xs text-muted">{{ $option['description'] }}</span>
                        </span>
                    @else
                        <span class="flex cursor-pointer items-center justify-center gap-1.5 rounded-sm px-3 py-2 text-sm font-medium text-muted transition-colors select-none hover:text-ink peer-checked:bg-surface peer-checked:text-ink peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-control peer-checked:ring-inset peer-focus-visible:outline-2 peer-focus-visible:outline-offset-1 peer-focus-visible:outline-focus">
                            @if ($option['icon'])
                                <acun:icon :name="$option['icon']" size="4" />
                            @endif
                            {{ $option['label'] }}
                        </span>
                    @endif
                </label>
            @endforeach
        </div>
    @endif

    {{ $slot }}
</div>
