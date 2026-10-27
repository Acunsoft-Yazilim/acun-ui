@props([
    // The question shown in place of the button; default: "Are you sure?" in the current language.
    'question' => null,
    // The confirm button's text; default: the slot.
    'confirmText' => null,
    // The cancel button's text; default: "Cancel".
    'cancelText' => null,
    // acun:button variant of the confirm button (and of the first button with outline-).
    'variant' => 'danger',
    'size' => 'sm',
])

{{--
    A destructive action on a row that asks in place instead of opening a dialog:
        <acun:inline-confirm wire:click="delete({{ $urun->id }})" question="Silinsin mi?">Sil</acun:inline-confirm>
    The first click turns the button into the question with the confirm and cancel buttons;
    only the confirm button runs the action (attributes such as wire:click go on it). Escape,
    the cancel button or a click elsewhere puts the button back. The focus moves to the
    confirm button and comes back to the first one (also after the confirm button: it is
    never lost to the page).
--}}
@php
    $question ??= __('Are you sure?');
    $cancelText ??= __('Cancel');
    $outline = str_starts_with($variant, 'outline-') ? $variant : 'outline-'.$variant;
@endphp

{{-- The behaviour is the inline-confirm plugin's (js/plugins/inline-confirm.js): no Alpine. --}}
<span data-acun-inline-confirm class="inline-flex items-center gap-2">
    <acun:button :variant="$outline" :size="$size" data-slot="inline-confirm-ask">{{ $slot }}</acun:button>

    <span data-slot="inline-confirm-question" hidden role="group" aria-label="{{ $question }}" class="inline-flex items-center gap-2">
        <span class="text-sm text-body">{{ $question }}</span>
        <acun:button :variant="$variant" :size="$size" data-slot="inline-confirm-confirm" {{ $attributes }}>{{ $confirmText ?? $slot }}</acun:button>
        <acun:button variant="ghost" :size="$size" data-slot="inline-confirm-cancel">{{ $cancelText }}</acun:button>
    </span>
</span>
