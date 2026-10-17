@props([
    // The group's title (a fieldset legend).
    'label' => null,
    // The list's name: tags, tags[] or the wire:model property. Its validation message
    // (tags or tags.*) is shown once, under the boxes.
    'name' => null,
    'description' => null,
    // Side by side, wrapping, instead of one under the other.
    'inline' => false,
    // An asterisk after the title (the rule itself belongs on the server).
    'required' => false,
])

{{--
    A list of checkboxes with one title and one message:

        <acun:checkbox-group label="Kategoriler" name="kategoriler" required>
            <acun:checkbox wire:model="kategoriler" value="ses" label="Ses" />
            <acun:checkbox wire:model="kategoriler" value="giyilebilir" label="Giyilebilir" />
        </acun:checkbox-group>
--}}
@php
    $key = \Acun\Ui\Support\Field::key($name);
    $errorBag = $errors ?? null;
    $error = \Acun\Ui\Support\Field::error($name, $errorBag)
        ?? ($key !== null && ($errorBag instanceof \Illuminate\Support\ViewErrorBag || $errorBag instanceof \Illuminate\Contracts\Support\MessageBag) ? ($errorBag->first($key.'.*') ?: null) : null);
    $groupId = \Acun\Ui\Support\Field::id('checkbox-group', $attributes, $key !== null ? $key.'-group' : null, $label);
    $describedBy = collect([$description ? $groupId.'-description' : null, $error ? $groupId.'-message' : null])->filter()->implode(' ');
@endphp

<fieldset
    {{ $attributes->except('id')->class('min-w-0 space-y-2') }}
    id="{{ $groupId }}"
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
>
    @if ($label)
        <legend class="text-sm font-medium text-gray-900 dark:text-zinc-100">
            {{ $label }}@if ($required)<span class="ms-0.5 text-red-600 dark:text-red-400" aria-hidden="true">*</span><span class="sr-only"> {{ __('(required)') }}</span>@endif
        </legend>
    @endif

    @if ($description)
        <p id="{{ $groupId }}-description" class="text-xs text-gray-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    <div @class(['flex', 'flex-wrap gap-x-6 gap-y-2' => $inline, 'flex-col gap-2' => ! $inline])>
        {{ $slot }}
    </div>

    @if ($error)
        <p id="{{ $groupId }}-message" class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</fieldset>
