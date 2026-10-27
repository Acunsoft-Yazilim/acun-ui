{{--
    Added to the layout once: <acun:toast-container />
    To trigger it from any Livewire component:
        $this->dispatch('toast', type: 'success', message: 'Kaydedildi.');
    type: success | error | warning | info; danger is error (the palette's name), an unknown type shows as info.
    Optional link (opened with wire:navigate when Livewire is on the page, as a normal link otherwise):
        $this->dispatch('toast', type: 'error', message: '...', link: $url, linkLabel: 'Göster');
    A file download link (never opened with wire:navigate): add download: true.
    Shortcuts: the Acun\Ui\Livewire\Concerns\WithToasts trait ($this->toast('Kaydedildi.')).
    After a redirect: Acun\Ui\Support\Toast::flash('Kaydedildi.'); the toasts queued in the
    session are shown once, when the next page renders this container.
--}}
@php
    $flashed = \Acun\Ui\Support\Toast::pull();
    // The look of each type: written out here so Tailwind finds the classes (js/plugins/toast.js adds them).
    $typeClasses = [
        'success' => 'bg-success text-on-success',
        'error' => 'bg-danger text-on-danger',
        'warning' => 'bg-warning text-on-warning',
        'info' => 'bg-inverse text-on-inverse',
    ];
@endphp
{{-- The behaviour is the toast plugin's (js/plugins/toast.js): no Alpine. An error is announced at once (alert),
     the others wait their turn (status); the template is what every toast is drawn from. --}}
<div
    data-acun-toasts
    data-type-classes="{{ json_encode($typeClasses) }}"
    @if ($flashed !== []) data-flashed="{{ json_encode($flashed) }}" @endif
    aria-live="polite"
    aria-relevant="additions"
    class="fixed z-[100] flex flex-col gap-2 bottom-4 end-4"
>
    <template data-slot="toast-template">
        <div data-slot="toast" class="flex w-80 translate-y-2 items-start gap-3 rounded-sm p-4 text-sm opacity-0 shadow-lg ring-1 ring-ink/5 transition duration-200 ease-out data-open:translate-y-0 data-open:opacity-100 motion-reduce:transition-none">
            <div class="flex-1">
                <p data-slot="toast-message"></p>
                {{-- A download (download: true) gets the download attribute and is never opened with wire:navigate. --}}
                <a data-slot="toast-link" hidden class="mt-1.5 inline-flex font-semibold underline underline-offset-2 hover:no-underline"></a>
            </div>
            <button type="button" data-slot="toast-close" aria-label="{{ __('Close') }}" class="opacity-70 hover:opacity-100">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
