// The modal, in plain JavaScript (no Alpine). Markup (acun:modal renders the same):
//
//     <div data-acun-modal="order-4821" hidden class="pointer-events-none fixed inset-0 z-50 … data-open:pointer-events-auto">
//         <div data-slot="modal-backdrop" data-acun-close class="… opacity-0 data-open:opacity-100" aria-hidden="true"></div>
//         <div data-slot="modal-panel" role="dialog" aria-modal="true" tabindex="-1" class="… opacity-0 data-open:opacity-100">
//             <h3 data-slot="modal-title">Order #4821</h3>
//             <button type="button" data-acun-close aria-label="Close">…</button>
//         </div>
//     </div>
//     <button type="button" data-acun-open="order-4821">Show order</button>
//
// Open, the root, the backdrop and the panel carry data-open (and the root loses hidden); the CSS
// transitions do the rest (core/transition.js). Options on the root: data-close-action (a method of
// the Livewire component around it, run when the user closes the modal: adapters/livewire.js) and
// data-request-close-event (a close guard: the X, Escape and the backdrop send this event instead
// of closing; the page then closes it with AcunUI.modal.close(name)).
//
// One modal is open at a time: opening one closes the others, except one opened from inside an
// open modal (a confirmation over an edit form), which goes on top of it; Escape closes the top one.
// Escape, Tab, the page scroll lock and the focus restore come from the overlay manager.
// Events (from the root, bubbling to window): acun:modal:opened ({ name }), acun:modal:closed and modal-closed
// ({ name, reason }): reason 'user' (the X, Escape, the backdrop, data-acun-close, another modal opening),
// 'command' (AcunUI.modal.close(), a close-modal event: the page or the server asked) or 'removed' (taken off the
// page while open). A framework adapter tells a user's close from its own with it (React/Vue onOpenChange).

import { EVENTS, dispatch, listen } from '../core/events.js';
import { focusable, ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';
import { enter, leave } from '../core/transition.js';
import { keepHeaderName } from '../behavior/dialog-name.js';
import { focusFirst } from '../behavior/focus-trap.js';
import { overlays } from '../behavior/overlay-manager.js';

/** @import { OpenOptions, PluginDefinition } from '../core/plugins.js' */

/**
 * Why a modal closed (the detail of acun:modal:closed).
 *
 * @typedef {'user' | 'command' | 'removed'} CloseReason
 */

/**
 * A modal on the page (AcunUI.plugins.get(element, 'modal')).
 *
 * @typedef {object} ModalInstance
 * @property {HTMLElement} element  the root (data-acun-modal)
 * @property {HTMLElement} panel    the dialog panel
 * @property {string | null} name
 * @property {boolean} isOpen
 * @property {(options?: OpenOptions) => void} open
 * @property {(options?: { notify?: boolean }) => void} close  notify: false when the page or the server asked for it (reason 'command': no close-action)
 * @property {(options?: OpenOptions) => void} toggle
 * @property {() => void} requestClose  the user's close: through data-request-close-event when there is one
 * @property {() => HTMLElement[]} focusables  the panel's elements that Tab reaches
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-modal';
/** @type {Set<ModalInstance>} the open modals, in the order they opened */
const openModals = new Set();

/**
 * @param {string | null | undefined} name
 * @returns {ModalInstance[]}
 */
const named = (name) => /** @type {ModalInstance[]} */ (/** @type {unknown} */ (plugins.find(name, 'modal')));

/**
 * @param {HTMLElement} root
 * @returns {ModalInstance}
 */
function createModal(root) {
    const name = () => root.getAttribute(ATTRIBUTE);
    // The markup of 2.0 and earlier (x-data="acunModal(…)", x-ref="panel", x-show children) shows
    // and hides itself: the root is not hidden then.
    const panel = ownPart(root, ATTRIBUTE, 'modal-panel', '[x-ref="panel"], [role="dialog"], [role="alertdialog"]') ?? root;
    const managed = panel.getAttribute('data-slot') === 'modal-panel';
    const backdrop = ownPart(root, ATTRIBUTE, 'modal-backdrop');
    const layers = managed ? [root] : [];
    const parts = [root, backdrop, managed ? panel : null].filter((part) => part !== null);
    const dialog = panel.matches('[role="dialog"], [role="alertdialog"]') ? panel : root.querySelector('[role="dialog"], [role="alertdialog"]');

    let isOpen = false;
    let cancelLeave = () => {};

    plugins.own(root, 'hidden', 'data-open');
    parts.forEach((part) => plugins.own(part, 'data-open'));
    const stopNaming = keepHeaderName(dialog, root, 'modal');

    /** @type {ModalInstance} */
    const instance = {
        element: root,
        panel,

        get name() {
            return name();
        },

        get isOpen() {
            return isOpen;
        },

        open({ opener } = {}) {
            if (isOpen) return;

            // One modal at a time (no doubt about which X belongs to which modal): another open one
            // closes first, as when the user closes it. One opened from inside an open modal goes on top.
            const from = opener ?? document.activeElement;
            [...openModals].filter((other) => other !== instance && !other.element.contains(from)).forEach((other) => other.close());

            isOpen = true;
            openModals.add(instance);
            cancelLeave();
            enter(layers, parts);
            overlays.open(root, {
                close: () => instance.requestClose(),
                trap: () => panel,
                opener: from,
            });
            // The markup of 2.0 shows its panel on the frame after Alpine's next update (x-show).
            if (managed) focusFirst(panel);
            else requestAnimationFrame(() => requestAnimationFrame(() => isOpen && focusFirst(panel)));
            plugins.expand(name(), true);
            dispatch(EVENTS.modal.opened, { name: name() }, root);
        },

        close({ notify = true } = {}) {
            if (!isOpen) return;

            isOpen = false;
            openModals.delete(instance);
            cancelLeave = leave(layers, parts);
            overlays.close(root);

            /** @type {CloseReason} */
            const reason = notify ? 'user' : 'command';
            plugins.expand(name(), false);
            dispatch(EVENTS.modal.closed, { name: name(), reason }, root);
            dispatch('modal-closed', { name: name(), reason }, root);
        },

        toggle(options) {
            if (isOpen) instance.close();
            else instance.open(options);
        },

        requestClose() {
            const guard = root.getAttribute('data-request-close-event');
            if (guard) dispatch(guard, {}, root);
            else instance.close();
        },

        focusables() {
            return focusable(panel);
        },

        destroy() {
            stopNaming();
            cancelLeave();
            openModals.delete(instance);
            overlays.close(root);
            // Removed while open (a morph, an @if, an unmount): the triggers and the listeners learn it closed.
            if (isOpen) {
                isOpen = false;
                plugins.expand(name(), false);
                const target = root.isConnected ? root : window;
                dispatch(EVENTS.modal.closed, { name: name(), reason: 'removed' }, target);
                dispatch('modal-closed', { name: name(), reason: 'removed' }, target);
            }
        },
    };

    return instance;
}

/** @type {PluginDefinition} */
export const modalPlugin = {
    attribute: ATTRIBUTE,

    // The commands on window (AcunUI.modal.open(name), Livewire's dispatch('open-modal', name: …)).
    setup() {
        listen(EVENTS.modal.open, (event) => named(event.detail?.name).forEach((modal) => modal.open()));
        listen(EVENTS.modal.close, (event) => {
            const target = event.detail?.name;
            const modals = target === undefined || target === null ? /** @type {ModalInstance[]} */ (/** @type {unknown} */ (plugins.all('modal'))) : named(target);
            modals.forEach((modal) => modal.close({ notify: false }));
        });
    },

    mount: createModal,
};
