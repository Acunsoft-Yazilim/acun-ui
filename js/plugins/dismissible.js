// A box its own close button hides (acun:alert dismissible), in plain JavaScript (no Alpine):
//
//     <div data-acun-dismissible role="…">… <button type="button" data-acun-close aria-label="Close">×</button></div>
//
// The button is the runtime's data-acun-close trigger (core/plugins.js: it closes the nearest component around
// it); the box gets hidden, which a Livewire morph keeps. A wire:click on the button (dismiss-action) runs too.
// Event (from the box, bubbling to window): acun:dismissed.

import { dispatch } from '../core/events.js';
import { plugins } from '../core/plugins.js';
import { moveFocusOut } from '../behavior/focus-neighbour.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @param {HTMLElement} root
 */
function createDismissible(root) {
    plugins.own(root, 'hidden');

    return {
        element: root,
        get isOpen() {
            return !root.hidden;
        },
        close() {
            if (root.hidden) return;
            moveFocusOut(root);
            root.hidden = true;
            dispatch('acun:dismissed', {}, root);
        },
    };
}

/** @type {PluginDefinition} */
export const dismissiblePlugin = {
    attribute: 'data-acun-dismissible',
    mount: createDismissible,
};
