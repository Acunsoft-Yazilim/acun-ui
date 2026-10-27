// acun:collapse, an area a button opens and closes, in plain JavaScript (no Alpine). Markup:
//
//     <div data-acun-collapse>
//         <button type="button" data-slot="collapse-trigger" aria-expanded="false" class="group …">More</button>
//         <div data-slot="collapse-content" hidden class="grid grid-rows-[0fr] data-open:grid-rows-[1fr] …"><div class="min-h-0 overflow-hidden">…</div></div>
//     </div>
//
// The button opens and closes the area (its aria-expanded, aria-controls: the area's id, made when missing);
// the height animates in CSS (behavior/disclosure.js). Event (from the root, bubbling to window):
// acun:collapse:change ({ open }).

import { dispatch } from '../core/events.js';
import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';
import { setRegion } from '../behavior/disclosure.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @typedef {object} CollapseInstance
 * @property {HTMLElement} element  the root (data-acun-collapse)
 * @property {boolean} isOpen
 * @property {() => void} toggle
 * @property {(open: boolean) => void} setOpen
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-collapse';
let counter = 0;

/**
 * @param {HTMLElement} root
 * @returns {CollapseInstance}
 */
function createCollapse(root) {
    const trigger = ownPart(root, ATTRIBUTE, 'collapse-trigger');
    const content = ownPart(root, ATTRIBUTE, 'collapse-content');
    if (content && !content.id) content.id = `acun-collapse-${++counter}`;
    if (trigger && content) trigger.setAttribute('aria-controls', content.id);
    if (trigger) plugins.own(trigger, 'aria-expanded');
    if (content) plugins.own(content, 'hidden', 'data-open');

    let cancel = () => {};
    const isOpen = () => trigger?.getAttribute('aria-expanded') === 'true';

    /** @type {CollapseInstance} */
    const instance = {
        element: root,

        get isOpen() {
            return isOpen();
        },

        toggle() {
            instance.setOpen(!isOpen());
        },

        setOpen(next) {
            if (!content || next === isOpen()) return;
            cancel();
            cancel = setRegion(content, trigger, next);
            dispatch('acun:collapse:change', { open: next }, root);
        },

        destroy() {
            trigger?.removeEventListener('click', onClick);
            cancel();
        },
    };

    const onClick = () => instance.toggle();
    trigger?.addEventListener('click', onClick);

    return instance;
}

/** @type {PluginDefinition} */
export const collapsePlugin = {
    attribute: ATTRIBUTE,
    mount: createCollapse,
};
