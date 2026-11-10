@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    // The chosen id (a classic form; wire:model / x-model picks it otherwise).
    'value' => null,
    // false: never the value a failed validation sent back (old()), only value: one of several forms on a page with
    // fields of the same name (an address list, each with its own form) keeps its own.
    'old' => true,
    // The empty choice: the theme's own font. null: "Theme font".
    'placeholder' => null,
    // A line under the field in the chosen font (only that family is downloaded); false: none.
    'preview' => true,
    // The preview's text; null: a Turkish pangram in Turkish (every letter, ğ ş ı İ too), an English one otherwise.
    'sample' => null,
])

{{--
    A font from the kit's font library (Acun\Ui\Support\FontLibrary: 20 Google Fonts families served by the package):
    acun:select with the families grouped (sans-serif, serif) and the empty choice first (the theme's own font). Its
    value is the id the layout settings take (font, heading_font): kept by the application, for example an
    administrator's setting in the database, and given back with FontLibrary::using().

        <acun:font-select :label="__('Text font')" wire:model.live="font" />

    The preview line takes the chosen family by CSS alone (:has(), no JavaScript, so wire:model's changes show too);
    a browser downloads only the family it shows, in one weight.
--}}
@php
    $options = \Acun\Ui\Support\FontLibrary::options();
    $groups = ['sans-serif' => __('Sans-serif'), 'serif' => __('Serif')];
    $nonce = \Acun\Ui\Support\FontLibrary::nonce();
@endphp

<div data-acun-font-select {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex flex-col gap-2') }}>
    <acun:select :label="$label" :name="$name" :hint="$hint" :value="$value" :placeholder="$placeholder ?? __('Theme font')" :old="$old" nullable {{ $attributes->filter(fn ($value, $key) => ! \Acun\Ui\Support\Field::isLayout((string) $key)) }}>
        @foreach ($options as $category => $fonts)
            <optgroup label="{{ $groups[$category] ?? $category }}">
                @foreach ($fonts as $id => $family)
                    <option value="{{ $id }}" @selected(! \Acun\Ui\Support\Field::bound($attributes) && (string) \Acun\Ui\Support\Field::old(\Acun\Ui\Support\Field::name($name, $attributes), $value, $old) === $id)>{{ $family }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </acun:select>

    @if ($preview)
        <p data-slot="font-preview" class="truncate rounded-sm bg-sunken px-3 py-2.5 text-lg text-ink" aria-hidden="true">{{ $sample ?? __('Sphinx of black quartz, judge my vow.') }}</p>

        @once
            {{-- Every family's regular weight (declared, downloaded only when the preview shows it) and the rule that shows the chosen one. --}}
            <style data-acun-font-select-faces @if ($nonce) nonce="{{ $nonce }}" @endif>
                @foreach (array_keys(\Acun\Ui\Support\FontLibrary::all()) as $id)
                    {!! \Acun\Ui\Support\FontLibrary::faces($id, [400]) !!}
                    [data-acun-font-select]:has(option[value="{{ $id }}"]:checked) [data-slot="font-preview"]{font-family:{!! \Acun\Ui\Support\FontLibrary::stack($id) !!}}
                @endforeach
            </style>
        @endonce
    @endif
</div>
