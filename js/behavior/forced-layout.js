// A page that forces its layout (Layout::page(): theme, menu mode, navbar…) is always
// shown from a full page load: its settings are worked out on the server, while the
// stores hold the user's own.
//
// Leaving it: the next page is loaded in full instead of with wire:navigate, so the
// user's own settings come back. On window, so it runs after the listeners on
// document: a leave that the unsaved-changes guard (PRO) stopped stays stopped.
//
// Reaching it with wire:navigate (a menu link, the command palette): Livewire has
// already added the new page's settings to <head> when onSwap runs, and the address
// bar shows the new page, so it is loaded again in full.

import { readConfig } from '../core/config.js';

// Replaceable in tests (jsdom cannot reload).
export const page = { reload: () => window.location.reload() };

export function reloadWhenLeavingForcedLayout() {
    window.addEventListener('livewire:navigate', (event) => {
        const url = event.detail?.url;
        if (event.defaultPrevented || !url || !readConfig()?.forced) return;

        event.preventDefault();
        window.location.href = url;
    });

    document.addEventListener('livewire:navigating', (event) => {
        if (typeof event.detail?.onSwap !== 'function') return;

        event.detail.onSwap(() => {
            if (readConfig()?.forced) page.reload();
        });
    });
}
