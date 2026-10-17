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
    confirm button and comes back to the first one.
--}}
@php
    $question ??= __('Are you sure?');
    $cancelText ??= __('Cancel');
    $outline = str_starts_with($variant, 'outline-') ? $variant : 'outline-'.$variant;
@endphp

<span
    x-data="{ asking: false }"
    x-on:click.outside="asking = false"
    x-on:keydown.escape="if (asking) { $event.stopPropagation(); asking = false; $nextTick(() => $refs.ask.focus()) }"
    class="inline-flex items-center gap-2"
>
    <acun:button :variant="$outline" :size="$size" x-ref="ask" x-show="! asking" x-on:click="asking = true; $nextTick(() => $refs.confirm.focus())">{{ $slot }}</acun:button>

    <span x-show="asking" x-cloak role="group" :aria-label="@js($question)" class="inline-flex items-center gap-2">
        <span class="text-sm text-body">{{ $question }}</span>
        <acun:button :variant="$variant" :size="$size" x-ref="confirm" x-on:click="asking = false" {{ $attributes }}>{{ $confirmText ?? $slot }}</acun:button>
        <acun:button variant="ghost" :size="$size" x-on:click="asking = false; $nextTick(() => $refs.ask.focus())">{{ $cancelText }}</acun:button>
    </span>
</span>
