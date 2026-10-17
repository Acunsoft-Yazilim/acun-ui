<?php

namespace Acun\Ui\Livewire\Concerns;

use Acun\Ui\Support\Toast;

/**
 * Toast shortcuts for Livewire components (acun:toast-container must be on the page;
 * the package layouts include it).
 *
 *   use WithToasts;
 *
 *   $this->toast('Kaydedildi.');
 *   $this->toast('Kayıt silinemedi.', 'error');   // 'danger' is the same type
 *   $this->toast('Dışa aktarma hazır.', 'info', link: $url, linkLabel: 'İndir', download: true);
 *
 *   // When the action redirects, the event would be lost: queue it for the next page.
 *   $this->toastAfterRedirect('Müşteri eklendi.');
 *   $this->redirectRoute('customers.index', navigate: true);
 *
 * download: the link is a file download, never opened with wire:navigate.
 * The methods are protected on purpose: public Livewire methods can be called from the browser.
 */
trait WithToasts
{
    protected function toast(string $message, string $type = 'success', ?string $link = null, ?string $linkLabel = null, bool $download = false): void
    {
        $this->dispatch('toast', ...Toast::payload($message, $type, $link, $linkLabel, $download));
    }

    protected function toastAfterRedirect(string $message, string $type = 'success', ?string $link = null, ?string $linkLabel = null, bool $download = false): void
    {
        Toast::flash($message, $type, $link, $linkLabel, $download);
    }
}
