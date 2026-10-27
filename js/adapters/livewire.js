// The plugins under Livewire. The plugins themselves know nothing of it; this adapter:
// - keeps their state through morphs: the server renders a closed modal (hidden, no data-open)
//   on every update, and a morph would close an open one. The attributes a plugin manages
//   (plugins.own) keep their values in the browser (morph.updating, before the morph applies them);
// - runs a modal's close-action, a method of the Livewire component around it
//   (data-close-action="cancel" calls $wire.cancel()), when the user closed it (acun:modal:closed with
//   reason 'user'; the modal plugin only reports the reason).
// wire:model on acun:modal / acun:drawer is Livewire's own x-model: adapters/alpine.js ties it to
// the plugin (Livewire always brings Alpine).

import { EVENTS } from '../core/events.js';
import { plugins } from '../core/plugins.js';

/** @import { AcunWindow, HostElement, LivewireLike, WireLike } from '../core/types.js' */

/**
 * Called once by the runtime, when Livewire is on the page (core/livewire.js).
 *
 * @param {LivewireLike} Livewire
 */
export function keepPluginStateThroughMorphs(Livewire) {
    Livewire.hook('morph.updating', (/** @type {{ el: Element, toEl: Element }} */ { el, toEl }) => plugins.keepOwned(el, toEl));
}

/**
 * Called once by the runtime, when Livewire is on the page: a modal the user closed runs its close-action.
 */
export function runCloseActions() {
    window.addEventListener(EVENTS.modal.closed, (event) => {
        const root = /** @type {Element | null} */ (event.target instanceof Element ? event.target : null);
        const action = root?.getAttribute('data-close-action');
        if (root && action && /** @type {CustomEvent<{ reason?: string }>} */ (event).detail?.reason === 'user') callComponent(root, action);
    });
}

/**
 * The $wire of the Livewire component element is in, or null.
 *
 * @param {Element} element
 * @returns {WireLike | null}
 */
export function wireOf(element) {
    for (let node = /** @type {HostElement | null} */ (element); node; node = node.parentElement) {
        if (node.__livewire?.$wire) return node.__livewire.$wire;
        const id = node.getAttribute('wire:id');
        if (id) return /** @type {AcunWindow} */ (window).Livewire?.find?.(id) ?? null;
    }

    return null;
}

/**
 * Calls method on the Livewire component around element (a modal's close-action).
 *
 * @param {Element} element
 * @param {string} method
 * @returns {unknown}
 */
export function callComponent(element, method) {
    const wire = wireOf(element);
    // $wire answers any method name with a function that calls it on the server.
    if (typeof wire?.[method] === 'function') return wire[method]();

    console.warn(`[Acun UI] "${method}" (close-action) needs a Livewire component around the dialog.`, element);
    return undefined;
}
