<?php

namespace Acun\Ui\Support;

use InvalidArgumentException;

/**
 * Toasts shown by acun:toast-container.
 *
 * Right away, from a Livewire component: use the WithToasts trait ($this->toast(...)).
 * After a redirect (from a controller or a Livewire action that redirects):
 *
 *   Toast::flash('Müşteri kaydedildi.');
 *   return redirect()->route('customers.index');
 *
 * Flashed toasts are shown once, on the next page that renders the container.
 */
final class Toast
{
    /**
     * The types the container draws. The palette's names work too (Acun\Ui\Support\Tone):
     * danger (and red) is error, green is success, amber is warning, blue is info.
     */
    public const TYPES = ['success', 'error', 'warning', 'info'];

    private const SESSION_KEY = 'acun-ui.toasts';

    /**
     * The event payload acun:toast-container expects. A link with a scheme other than http,
     * https, mailto or tel (javascript:, data:…) is dropped: it may come from stored data.
     * download: the link is a file download (never opened with wire:navigate).
     *
     * @return array{type: string, message: string, link: ?string, linkLabel: ?string, download: bool}
     */
    public static function payload(string $message, string $type = 'success', ?string $link = null, ?string $linkLabel = null, bool $download = false): array
    {
        return ['type' => self::type($type), 'message' => $message, 'link' => Url::safe($link), 'linkLabel' => $linkLabel, 'download' => $download];
    }

    /** Queues a toast for the next page load (session flash). */
    public static function flash(string $message, string $type = 'success', ?string $link = null, ?string $linkLabel = null, bool $download = false): void
    {
        $toasts = session()->get(self::SESSION_KEY, []);
        $toasts[] = self::payload($message, $type, $link, $linkLabel, $download);

        session()->flash(self::SESSION_KEY, $toasts);
    }

    /**
     * The toast type for $type: one of TYPES, or a palette name for one of them
     * (danger → error), in any case and with spaces around it ("Danger" is error, as the
     * container reads a dispatched type). Anything else is an error in the calling code.
     */
    public static function type(string $type): string
    {
        $tone = Tone::name(strtolower(trim($type)), '');
        $resolved = $tone === 'danger' ? 'error' : $tone;

        if (! in_array($resolved, self::TYPES, true)) {
            throw new InvalidArgumentException("Unknown toast type [{$type}]. Allowed: ".implode(', ', self::TYPES).' (danger is error).');
        }

        return $resolved;
    }

    /**
     * Takes the queued toasts out of the session (so they are shown only once).
     *
     * @return array<int, array{type: string, message: string, link: ?string, linkLabel: ?string, download?: bool}>
     */
    public static function pull(): array
    {
        // No session configured (e.g. a stateless app): nothing can have been queued.
        if (! app()->bound('session')) {
            return [];
        }

        return array_values((array) session()->pull(self::SESSION_KEY, []));
    }
}
