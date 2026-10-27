// The popover (a bubble with a title, opened on click), in plain JavaScript (no Alpine). Markup (acun:popover
// renders the same, classic and in parts):
//
//     <div data-acun-popover data-slot="popover" class="relative inline-block">
//         <div data-slot="popover-trigger"><button type="button" aria-haspopup="dialog" aria-expanded="false">Info</button></div>
//         <div data-slot="popover-content" role="dialog" hidden data-side="bottom" class="absolute … opacity-0 data-open:opacity-100">
//             <div data-slot="popover-title">Returns</div>
//             <div>Products can be returned within 14 days.</div>
//         </div>
//     </div>
//
// Opening, closing, ARIA, placement and the events are behavior/popup.js's (shared with the dropdown): a click
// on the trigger toggles it, a click or the focus outside closes it, Escape closes it and gives the focus back
// to the trigger. A click inside keeps it open (it is a dialog). Its title names it (aria-labelledby; an id is
// made when the title has none). Events (from the root, bubbling to window): acun:popover:opened and
// acun:popover:closed ({ reason }).

import { createPopup } from '../behavior/popup.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * A popover on the page (AcunUI.plugins.get(element, 'popover')).
 *
 * @typedef {object} PopoverInstance
 * @property {HTMLElement} element  the root (data-acun-popover)
 * @property {HTMLElement | null} content  the bubble
 * @property {boolean} isOpen
 * @property {() => void} open
 * @property {(options?: { refocus?: boolean, notify?: boolean }) => void} close
 * @property {() => void} toggle
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-popover';

/**
 * @param {HTMLElement} root
 * @returns {PopoverInstance}
 */
function createPopover(root) {
    const popup = createPopup(root, { kind: 'popover', attribute: ATTRIBUTE });
    const { content } = popup;

    const title = /** @type {HTMLElement | null} */ (content?.querySelector('[data-slot="popover-title"]') ?? null);
    if (content && title) {
        if (!title.id) title.id = `${content.id}-title`;
        if (!content.hasAttribute('aria-labelledby')) content.setAttribute('aria-labelledby', title.id);
    }

    return {
        element: root,
        content,

        get isOpen() {
            return popup.isOpen();
        },

        open() {
            popup.open();
        },

        close({ refocus = false } = {}) {
            popup.close({ refocus }, 'command');
        },

        toggle: popup.toggle,
        destroy: popup.destroy,
    };
}

/** @type {PluginDefinition} */
export const popoverPlugin = {
    attribute: ATTRIBUTE,
    mount: createPopover,
};
