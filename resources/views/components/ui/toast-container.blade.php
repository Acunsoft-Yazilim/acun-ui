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
@php($flashed = \Acun\Ui\Support\Toast::pull())
<div
    x-data="{
        toasts: [],
        // id => { left: ms, since: start of the running stretch, handle }; outside the reactive list.
        timers: new Map(),
        push(toast) {
            const id = Date.now() + Math.random();
            const link = this.safeLink(toast.link);
            this.toasts.push({ id, type: this.toastType(toast.type), message: toast.message, link, linkLabel: toast.linkLabel ?? null, download: this.downloadName(toast.download) });
            // Toasts with a link stay a little longer so the link can be clicked.
            this.timers.set(id, { left: link ? 8000 : 5000 });
            this.resume(id);
        },
        // While the pointer or the focus is on a toast it stays (WCAG 2.2.1: time to read and act).
        pause(id) {
            const timer = this.timers.get(id);
            if (! timer?.handle) return;
            clearTimeout(timer.handle);
            timer.handle = null;
            timer.left -= Date.now() - timer.since;
        },
        resume(id) {
            const timer = this.timers.get(id);
            if (! timer || timer.handle) return;
            timer.since = Date.now();
            timer.handle = setTimeout(() => this.dismiss(id), Math.max(timer.left, 1000));
        },
        dismiss(id) {
            clearTimeout(this.timers.get(id)?.handle);
            this.timers.delete(id);
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        // The types of Acun\Ui\Support\Toast (a dispatched event does not pass Toast::payload):
        // the palette's names for them work too (danger is error), in any case (Danger, ERROR);
        // anything else shows as info.
        toastType(type) {
            const name = String(type ?? '').trim().toLowerCase();
            const resolved = { danger: 'error', red: 'error', green: 'success', emerald: 'success', amber: 'warning', yellow: 'warning', blue: 'info', sky: 'info' }[name] ?? name;
            return ['success', 'error', 'warning', 'info'].includes(resolved) ? resolved : 'info';
        },
        // download: true downloads the file (download attribute, the server's file name), a
        // text names the saved file; anything else is a plain link (null leaves the attribute out).
        downloadName(download) {
            if (download === true) return '';
            return typeof download === 'string' && download.trim() !== '' ? download.trim() : null;
        },
        // The rule of Acun\Ui\Support\Url: only http, https, mailto, tel and relative links;
        // javascript:, data:… are dropped, and so is a protocol-relative //host link to another site.
        safeLink(url) {
            if (typeof url !== 'string' || url === '') return null;
            const normalized = url.replace(/[\t\n\r]/g, '').replace(/^[\x00-\x20]+/, '');
            // Browsers read a backslash as a slash: /\host and \\host are //host too.
            if (/^[\\\/]{2}/.test(normalized)) return this.sameSite(normalized) ? url : null;
            const scheme = normalized.match(/^([a-z][a-z0-9+.\-]*):/i)?.[1].toLowerCase();
            return ! scheme || ['http', 'https', 'mailto', 'tel'].includes(scheme) ? url : null;
        },
        sameSite(url) {
            try {
                return new URL(url, location.href).origin === location.origin;
            } catch {
                return false;
            }
        },
    }"
    x-on:toast.window="push($event.detail)"
    @if ($flashed !== []) x-init="{{ \Illuminate\Support\Js::from($flashed) }}.forEach((toast) => push(toast))" @endif
    aria-live="polite"
    aria-relevant="additions"
    class="fixed z-[100] flex flex-col gap-2 bottom-4 end-4"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            {{-- An error is announced at once (alert); the others wait their turn (status). push() resolved danger to error. --}}
            :role="toast.type === 'error' ? 'alert' : 'status'"
            x-on:mouseenter="pause(toast.id)"
            x-on:mouseleave="if (! $el.contains(document.activeElement)) resume(toast.id)"
            x-on:focusin="pause(toast.id)"
            x-on:focusout="if (! $el.contains($event.relatedTarget)) resume(toast.id)"
            class="flex items-start gap-3 w-80 rounded-sm p-4 text-sm shadow-lg ring-1 ring-black/5"
            :class="{
                'bg-emerald-solid text-white': toast.type === 'success',
                'bg-red-600 text-white': toast.type === 'error',
                'bg-amber-solid text-white': toast.type === 'warning',
                'bg-gray-900 text-white dark:bg-zinc-800': toast.type === 'info',
            }"
        >
            <div class="flex-1">
                <p x-text="toast.message"></p>
                <template x-if="toast.link">
                    {{-- A download (download: true) gets the download attribute and is never opened with wire:navigate. --}}
                    <a
                        :href="toast.link"
                        :download="toast.download"
                        x-on:click="if (toast.download !== null) { dismiss(toast.id); return } if (window.Livewire?.navigate && sameSite(toast.link)) { $event.preventDefault(); window.Livewire.navigate(toast.link) } dismiss(toast.id)"
                        class="mt-1.5 inline-flex font-semibold underline underline-offset-2 hover:no-underline"
                        x-text="toast.linkLabel"
                    ></a>
                </template>
            </div>
            <button type="button" x-on:click="dismiss(toast.id)" aria-label="{{ __('Close') }}" class="text-white/70 hover:text-white">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
