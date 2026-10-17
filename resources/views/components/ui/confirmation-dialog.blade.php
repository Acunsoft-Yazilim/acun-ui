@props([
    'name',
    // Defaults: "Are you sure?", "Confirm", "Cancel" in the current language.
    'title' => null,
    'description' => null,
    'confirmText' => null,
    'cancelText' => null,
    'closeAction' => null,
])

@php
    $title ??= __('Are you sure?');
    $confirmText ??= __('Confirm');
    $cancelText ??= __('Cancel');
@endphp

{{-- An alertdialog: a screen reader reads the question (the description) with the title. --}}
<acun:modal :name="$name" :title="$title" :close-action="$closeAction" max-width="sm" alert {{ $attributes }}>
    @if ($description)
        <p id="{{ $name }}-description" class="text-sm text-gray-600 dark:text-zinc-400">{{ $description }}</p>
    @endif

    <acun:slot:footer>
        <acun:button variant="white" x-on:click="close()">{{ $cancelText }}</acun:button>

        {{--
            Confirm button: customise it by passing your own wire:click action, e.g.
            <acun:confirmation-dialog name="delete-product" ...>
                <acun:button variant="danger" wire:click="delete">Sil</acun:button>
            </acun:confirmation-dialog>
            If the slot is empty, only the '{{ $name }}-confirmed' event is dispatched.
        --}}
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            @php($confirmedEvent = \Illuminate\Support\Js::from($name.'-confirmed'))
            <acun:button variant="danger" x-on:click="close(); $dispatch({{ $confirmedEvent }})">
                {{ $confirmText }}
            </acun:button>
        @endif
    </acun:slot:footer>
</acun:modal>
