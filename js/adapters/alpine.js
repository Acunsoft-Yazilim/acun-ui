// The plugins on a page with Alpine (Livewire brings it). The plugins do not need Alpine; this
// adapter keeps what Alpine markup around and inside them expects from the 2.0 components, which
// were Alpine components (x-data on their root):
// - their elements are Alpine roots, so Alpine directives in their content start on the first
//   page load as before (an x-on:click in a modal's body with no x-data of its own);
// - a plugin can give its element an Alpine scope (scope()): acun:modal / acun:drawer content that
//   calls close() or reads open keeps working (deprecated until 3.0: use data-acun-close);
// - wire:model and x-model on the element bind that scope's model (x-modelable, as in 2.0): the
//   component opens and closes with the property, and the property follows when the user closes it.

import { plugins } from '../core/plugins.js';

/** @import { AlpineLike } from '../core/types.js' */

/**
 * Makes the Alpine scope of a plugin element: the scope object (reactive) and the function that
 * stops what it set up.
 *
 * @typedef {(element: HTMLElement, Alpine: AlpineLike) => { scope: object, stop: () => void }} ScopeFactory
 */

/** @type {Map<string, { selector: string, create: ScopeFactory }>} */
const scopes = new Map();
/** @type {AlpineLike | null} */
let alpine = null;

/** @param {Element} element */
const isBound = (element) => element.getAttributeNames().some((name) => name.startsWith('wire:model') || name.startsWith('x-model'));

/**
 * Gives every element of kind an Alpine scope (its content's expressions see it).
 *
 * @param {string} kind
 * @param {string} selector
 * @param {ScopeFactory} create
 */
export function scope(kind, selector, create) {
    if (!scopes.has(kind)) scopes.set(kind, { selector, create });
}

/**
 * @param {AlpineLike} Alpine
 * @param {HTMLElement} element
 */
function giveScope(Alpine, element) {
    // An x-data of its own (markup written for acunModal / acunDrawer) brings its own scope.
    if (element.hasAttribute('x-data')) return;

    for (const { selector, create } of scopes.values()) {
        if (!element.matches(selector)) continue;

        const { scope: data, stop } = create(element, Alpine);
        // As an x-data of the element (bound, not written): Alpine adds the scope in the same order
        // as an x-data attribute's, after the scopes around it.
        Alpine.bind(element, isBound(element) ? { 'x-data': () => data, 'x-modelable': 'model' } : { 'x-data': () => data });
        Alpine.onElRemoved(element, stop);
        return;
    }
}

/**
 * Called once by the runtime, before Alpine starts.
 *
 * @param {AlpineLike} Alpine
 */
export function connectAlpine(Alpine) {
    if (alpine) return;
    alpine = Alpine;

    // Never empty: Alpine matches elements against it.
    Alpine.addRootSelector(() => plugins.selector || '[data-acun-no-plugin]');
    Alpine.interceptInit(Alpine.skipDuringClone((/** @type {HTMLElement} */ element) => giveScope(Alpine, element)));
}

/**
 * Calls back with true / false as the plugin on element opens and closes (its acun:{kind}:opened
 * and :closed events). Returns the function that stops.
 *
 * @param {Element} element
 * @param {'modal' | 'drawer' | 'dropdown' | 'popover'} kind
 * @param {(open: boolean) => void} callback
 * @returns {() => void}
 */
export function followState(element, kind, callback) {
    const opened = (/** @type {Event} */ event) => event.target === element && callback(true);
    const closed = (/** @type {Event} */ event) => event.target === element && callback(false);
    element.addEventListener(`acun:${kind}:opened`, opened);
    element.addEventListener(`acun:${kind}:closed`, closed);

    return () => {
        element.removeEventListener(`acun:${kind}:opened`, opened);
        element.removeEventListener(`acun:${kind}:closed`, closed);
    };
}
