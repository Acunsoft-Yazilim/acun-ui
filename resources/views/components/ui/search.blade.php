@props([
    // Visible label above the field; without it the field is labelled with aria-label.
    'label' => null,
    'name' => null,
    // With an action the component is a <form role="search">: Enter or the button sends
    // the term (GET by default) to that address. Without it, it is a <div role="search">
    // for wire:model or an enclosing form.
    'action' => null,
    'method' => 'GET',
    // Text of the submit button joined to the end of the field; null: no button.
    'button' => null,
    // A key that focuses the field from anywhere on the page (e.g. "/"), shown as a hint.
    'shortcut' => null,
    'clearable' => true,
    // md (40px, as acun:input) or lg (48px, for a page's main search).
    'size' => 'md',
    // Classes for the <input> itself; class (with style, x-show, wire:key) places the whole search.
    'inputClass' => null,
])

@php
    // The address may come from data: javascript:, another site's //host and other unsafe
    // addresses are not printed (Acun\Ui\Support\Url logs a warning). The form then searches
    // with GET on the current page: an action="#" with method="post" sent the term and the CSRF
    // token to the page itself (POST /orders).
    $isForm = filled($action);
    $action = $isForm ? \Acun\Ui\Support\Url::safe((string) $action) : null;
    $post = $action !== null && strtoupper($method) === 'POST';
    $model = $attributes->whereStartsWith('wire:model')->first();
    $fieldName = $name ?? $model;
    // Two searches without a name no longer share id="search-field".
    $inputId = \Acun\Ui\Support\Field::id('search', $attributes, $fieldName ? 'search-'.$fieldName : null, $label, $action);
    $placeholder = $attributes->get('placeholder') ?: __('Search');
    $disabled = (bool) $attributes->get('disabled');
    $large = $size === 'lg';
@endphp

{{--
    Search field: a magnifier, the field, a clear (×) button once there is text (Esc clears
    too), an optional shortcut hint and submit button. The clear button and the hint follow
    the field's :placeholder-shown, so they are right also after the server changes the value.
    While a Livewire request for the wire:model property runs, a spinner replaces the magnifier.

        <acun:search name="q" :value="request('q')" :action="route('orders.index')" button="Ara" />
        <acun:search wire:model.live.debounce.300ms="search" shortcut="/" />

    Layout attributes (class, style, x-show, wire:key) place the whole search (e.g.
    class="max-w-md"); every other attribute goes to the field, and input-class styles it.
    The slot is placed inside the form, for hidden fields that keep other query parameters.
--}}
<{{ $isForm ? 'form' : 'div' }}
    role="search"
    @if ($isForm) method="{{ $post ? 'POST' : 'GET' }}"@if ($action !== null) action="{{ $action }}"@endif @endif
    {{ \Acun\Ui\Support\Field::layoutAttributes($attributes)->class('flex w-full flex-col gap-1.5') }}
    x-data="{
        clear() {
            const input = this.$refs.input;
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
        },
    }"
    @if ($shortcut)
        x-on:keydown.window="if ($event.key === @js($shortcut) && ! $event.ctrlKey && ! $event.metaKey && ! $event.altKey && ! $event.target.closest('input, textarea, select, [contenteditable]')) { $event.preventDefault(); $refs.input.focus(); }"
    @endif
>
    {{-- The CSRF token goes only to this application, never to another site's address. --}}
    @if ($post && \Acun\Ui\Support\Url::isSameOrigin($action))
        @csrf
    @endif

    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium text-gray-700 dark:text-zinc-300">{{ $label }}</label>
    @endif

    <div @class([
        'flex items-stretch rounded-sm border border-gray-300 bg-white transition-[border-color,box-shadow] duration-150 focus-within:border-brand-600 focus-within:shadow-(--ui-focus-shadow) dark:border-zinc-600 dark:bg-zinc-800',
        'has-[input:disabled]:cursor-not-allowed has-[input:disabled]:bg-gray-50 dark:has-[input:disabled]:bg-zinc-900',
        $large ? 'h-12' : 'h-10',
    ])>
        <span @class(['pointer-events-none flex flex-none items-center text-gray-400 dark:text-zinc-500', $large ? 'ps-4' : 'ps-3'])>
            <svg @if ($model) wire:loading.remove wire:target="{{ $model }}" @endif class="{{ $large ? 'size-5' : 'size-4' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            @if ($model)
                <span wire:loading wire:target="{{ $model }}" class="{{ $large ? 'size-5' : 'size-4' }} animate-spin rounded-full border-2 border-current border-r-transparent motion-reduce:animate-[spin_1.5s_linear_infinite]" aria-hidden="true"></span>
            @endif
        </span>

        {{-- peer: the clear button and the shortcut hint below read its :placeholder-shown / :focus. --}}
        <input
            x-ref="input"
            id="{{ $inputId }}"
            @if ($name) name="{{ $name }}" @endif
            @if (! $label) aria-label="{{ $attributes->get('aria-label') ?? $placeholder }}" @endif
            @if ($shortcut) aria-keyshortcuts="{{ $shortcut }}" @endif
            @if ($clearable) x-on:keydown.escape="if ($el.value !== '') { $event.preventDefault(); $event.stopPropagation(); clear(); }" @endif
            {{ \Acun\Ui\Support\Field::controlAttributes($attributes)->except(['id', 'name', 'placeholder', 'aria-label'])->merge([
                'type' => 'search',
                'placeholder' => $placeholder,
                'autocomplete' => 'off',
                'enterkeyhint' => 'search',
                'class' => \Acun\Ui\Support\Field::controlClass('peer w-full min-w-0 flex-1 border-0 bg-transparent px-2.5 text-gray-900 placeholder:text-gray-500 dark:placeholder:text-zinc-400 focus:ring-0 focus:outline-none dark:text-zinc-100 disabled:cursor-not-allowed disabled:text-gray-500 dark:disabled:text-zinc-500 [&::-webkit-search-cancel-button]:appearance-none [&::-webkit-search-decoration]:appearance-none '
                    .($large ? 'text-base' : 'text-base sm:text-sm'), $inputClass),
            ]) }}
        />

        @if ($clearable && ! $disabled)
            <button
                type="button"
                x-on:click="clear()"
                class="hidden flex-none items-center px-2 text-gray-500 hover:text-gray-600 focus-visible:text-gray-600 focus-visible:outline-none peer-[:not(:placeholder-shown)]:flex dark:text-zinc-400 dark:hover:text-zinc-300"
                aria-label="{{ __('Clear search') }}"
                title="{{ __('Clear search') }}"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        @endif

        @if ($shortcut)
            <span class="pointer-events-none hidden flex-none items-center pe-2.5 peer-[:placeholder-shown:not(:focus)]:flex" aria-hidden="true">
                <kbd class="rounded-sm border border-gray-200 px-1.5 font-mono text-xs text-gray-500 dark:border-zinc-700 dark:text-zinc-400">{{ $shortcut }}</kbd>
            </span>
        @endif

        @if ($button)
            {{-- Joined to the field: it covers the frame's end edge, so the two read as one control. --}}
            <button
                type="submit"
                @disabled($disabled)
                @class([
                    'ui-btn ui-btn-primary relative -my-px -me-px flex-none rounded-e-sm font-semibold transition-colors duration-180 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white/80 focus-visible:shadow-(--ui-focus-shadow) disabled:cursor-not-allowed disabled:opacity-60',
                    $large ? 'px-6 text-base' : 'px-4 text-sm',
                ])
            >{{ $button }}</button>
        @endif
    </div>

    {{ $slot }}
</{{ $isForm ? 'form' : 'div' }}>
