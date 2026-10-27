// The dropdown menu, in plain JavaScript (no Alpine). Markup (acun:dropdown in parts renders the same):
//
//     <div data-acun-dropdown data-slot="dropdown" class="relative inline-block">
//         <div data-slot="dropdown-trigger"><button type="button" aria-expanded="false">Actions</button></div>
//         <div data-slot="dropdown-content" hidden data-side="bottom" class="absolute … opacity-0 data-open:opacity-100 data-[side=bottom]:top-full data-[side=top]:bottom-full">
//             <button type="button">Edit</button>
//             <a href="/orders">Orders</a>
//         </div>
//     </div>
//
// Opening, closing, ARIA, placement and the events are behavior/popup.js's (shared with the popover). The
// dropdown adds the menu keys: Arrow Down / Up on the trigger open the menu on its first / last item; in
// the menu Arrow Down / Up, Home and End move between the links and enabled buttons; a click in the menu
// closes it (the focus goes back to the trigger when it was in the menu). Events (from the root, bubbling
// to window): acun:dropdown:opened and acun:dropdown:closed ({ reason: 'user' | 'command' | 'removed' }).

import { createPopup } from '../behavior/popup.js';

/** @import { OpenOptions, PluginDefinition } from '../core/plugins.js' */

/**
 * A dropdown on the page (AcunUI.plugins.get(element, 'dropdown')).
 *
 * @typedef {object} DropdownInstance
 * @property {HTMLElement} element  the root (data-acun-dropdown)
 * @property {HTMLElement | null} content  the menu
 * @property {boolean} isOpen
 * @property {(options?: OpenOptions & { focus?: 1 | -1 | 'first' | 'last' }) => void} open  focus: the item to focus once open
 * @property {(options?: { refocus?: boolean, notify?: boolean }) => void} close  refocus: the focus goes back to the trigger
 * @property {() => void} toggle
 * @property {() => HTMLElement[]} items  the menu's links and enabled buttons, in order
 * @property {(to: 1 | -1 | 'first' | 'last') => void} focusItem
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-dropdown';

/**
 * @param {HTMLElement} root
 * @returns {DropdownInstance}
 */
function createDropdown(root) {
    const popup = createPopup(root, { kind: 'dropdown', attribute: ATTRIBUTE });
    const { content } = popup;

    /** @type {DropdownInstance} */
    const instance = {
        element: root,
        content,

        get isOpen() {
            return popup.isOpen();
        },

        open({ focus } = {}) {
            if (popup.open() && focus !== undefined) instance.focusItem(focus);
        },

        close({ refocus = false } = {}) {
            popup.close({ refocus }, 'command');
        },

        toggle: popup.toggle,

        items() {
            // In document order (a selector list may come back grouped by selector).
            return [.../** @type {NodeListOf<HTMLElement>} */ (content?.querySelectorAll('a[href], button:not(:disabled)') ?? [])]
                .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
        },

        focusItem(to) {
            const items = instance.items();
            if (!items.length) return;

            let index = items.indexOf(/** @type {HTMLElement} */ (document.activeElement));
            if (to === 'first' || (index < 0 && to === 1)) index = 0;
            else if (to === 'last' || (index < 0 && to === -1)) index = items.length - 1;
            else index = (index + /** @type {number} */ (to) + items.length) % items.length;
            items[index]?.focus();
        },

        destroy: popup.destroy,
    };

    popup.on(popup.trigger, 'keydown', (/** @type {KeyboardEvent} */ event) => {
        const to = /** @type {Record<string, 1 | -1>} */ ({ ArrowDown: 1, ArrowUp: -1 })[event.key];
        if (to === undefined) return;
        event.preventDefault();
        instance.open({ focus: to });
    });
    // A click in the menu (an item) closes it; the focus goes back to the trigger when it was in the menu.
    popup.on(content, 'click', () => popup.close({ refocus: !!content?.contains(document.activeElement) }, 'user'));
    popup.on(content, 'keydown', (/** @type {KeyboardEvent} */ event) => {
        if (/** @type {Element} */ (event.target).matches?.('input, textarea, select')) return;
        const to = /** @type {Record<string, 1 | -1 | 'first' | 'last'>} */ ({ ArrowDown: 1, ArrowUp: -1, Home: 'first', End: 'last' })[event.key];
        if (to === undefined) return;
        event.preventDefault();
        instance.focusItem(to);
    });

    return instance;
}

/** @type {PluginDefinition} */
export const dropdownPlugin = {
    attribute: ATTRIBUTE,
    mount: createDropdown,
};
