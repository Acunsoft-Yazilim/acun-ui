@props([
    // Preset colours: key => [#rrggbb, name]; null: Acun\Ui\Support\Appearance::presets().
    'presets' => null,
    // The free colour input.
    'custom' => true,
    // A label above the swatches.
    'label' => null,
])
{{-- 1.x: the primary colour picker. A wrapper of acun:theme-switcher (as="inline", the colour only) until 3.0. --}}
<acun:theme-switcher as="inline" :modes="false" :themes="false" :reset="false" :colors="$presets ?? true" :custom="$custom" :title="$label" :headings="false" {{ $attributes }} />
