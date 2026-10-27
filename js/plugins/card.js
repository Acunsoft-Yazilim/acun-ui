// A collapsible or closable acun:card, in plain JavaScript (no Alpine):
//
//     <div data-acun-card class="…">
//         <div data-slot="card-header">… <button type="button" data-slot="card-toggle" aria-expanded="true" class="group">⌃</button>
//             <button type="button" data-acun-close aria-label="Close">×</button></div>
//         <div data-slot="card-body" data-open class="grid grid-rows-[0fr] data-open:grid-rows-[1fr] …"><div class="min-h-0 overflow-hidden">…</div></div>
//     </div>
//
// The toggle folds the body away and back (its aria-expanded; the height animates in CSS, behavior/disclosure.js);
// the close button is the runtime's data-acun-close trigger and hides the card (hidden, kept through a morph).
// Events (from the card, bubbling to window): acun:card:toggled ({ open }), acun:dismissed.

import { dispatch } from '../core/events.js';
import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';
import { moveFocusOut } from '../behavior/focus-neighbour.js';
import { setRegion } from '../behavior/disclosure.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

const ATTRIBUTE = 'data-acun-card';
let counter = 0;

/**
 * @param {HTMLElement} root
 */
function createCard(root) {
    const toggle = ownPart(root, ATTRIBUTE, 'card-toggle');
    const body = ownPart(root, ATTRIBUTE, 'card-body');
    if (body && toggle) {
        if (!body.id) body.id = `acun-card-${++counter}`;
        toggle.setAttribute('aria-controls', body.id);
        plugins.own(toggle, 'aria-expanded');
        plugins.own(body, 'hidden', 'data-open');
    }
    plugins.own(root, 'hidden');

    let cancel = () => {};
    const onToggle = () => {
        if (!body || !toggle) return;
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        cancel();
        cancel = setRegion(body, toggle, open);
        dispatch('acun:card:toggled', { open }, root);
    };
    toggle?.addEventListener('click', onToggle);

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
        destroy() {
            cancel();
            toggle?.removeEventListener('click', onToggle);
        },
    };
}

/** @type {PluginDefinition} */
export const cardPlugin = {
    attribute: ATTRIBUTE,
    mount: createCard,
};
