// Deprecated until 3.0: the Alpine side of acun:modal from before 2.1. The behaviour is the modal
// plugin's (plugins/modal.js), with no Alpine; these delegate to it.
// - modal(): Alpine.data('acunModal'), for markup of its own written with
//   x-data="acunModal({ name, closeAction, requestCloseEvent })" (x-ref="panel", x-show="open").
// - modalScope(): the Alpine scope adapters/alpine.js gives every modal root, so content that calls
//   close() / requestClose() or reads open keeps working, and wire:model / x-model bind its model.
//   New markup: data-acun-close (the user's close, through the guard) and data-acun-close-now.

import { plugins } from '../core/plugins.js';
import { followState } from '../adapters/alpine.js';

/** @import { AlpineLike } from '../core/types.js' */
/** @import { ModalInstance } from '../plugins/modal.js' */
/** @import { AlpineMagics } from './drawer.js' */

let warned = false;
const warn = () => {
    if (!warned) console.warn('[Acun UI] x-data="acunModal(…)" is deprecated and goes in 3.0: use the modal markup with data-acun-modal (it needs no Alpine).');
    warned = true;
};

/**
 * @param {Element} root
 * @returns {ModalInstance | null}
 */
const instanceOf = (root) => /** @type {ModalInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'modal') ?? plugins.mount(root, 'modal')));

/**
 * What modalScope() and modal() share: the 2.0 names, on the plugin instance of root.
 *
 * @param {Element} root
 */
function methods(root) {
    return {
        show() {
            instanceOf(root)?.open();
        },
        /** notifyServer: false when closed by an event (no close-action). */
        close(notifyServer = true) {
            instanceOf(root)?.close({ notify: notifyServer });
        },
        requestClose() {
            instanceOf(root)?.requestClose();
        },
        /** @returns {HTMLElement[]} */
        focusables() {
            return instanceOf(root)?.focusables() ?? [];
        },
    };
}

/**
 * The scope of a modal root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function modalScope(root, Alpine) {
    const open = instanceOf(root)?.isOpen ?? false;
    const scope = Alpine.reactive({ open, model: open, ...methods(root) });
    const stopFollowing = followState(root, 'modal', (value) => {
        scope.open = value;
        scope.model = value;
    });
    // The bound property (wire:model / x-model through x-modelable): true opens, false closes (no close-action).
    const stopWatching = Alpine.watch(() => scope.model, (value) => (value ? instanceOf(root)?.open() : instanceOf(root)?.close({ notify: false })));

    return {
        scope,
        stop() {
            stopFollowing();
            stopWatching();
        },
    };
}

/**
 * Alpine.data('acunModal'): deprecated, see above.
 *
 * @param {{ name?: string, closeAction?: string | null, requestCloseEvent?: string | null }} [options]
 */
export function modal({ name, closeAction = null, requestCloseEvent = null } = {}) {
    let stop = () => {};

    return {
        open: false,
        // The value wire:model / x-model binds (x-modelable): true opens the modal, false closes it.
        model: false,

        /** @this {AlpineMagics & Record<string, any>} */
        init() {
            warn();
            const root = this.$el;
            if (!root.hasAttribute('data-acun-modal') && name) root.setAttribute('data-acun-modal', name);
            if (closeAction && !root.hasAttribute('data-close-action')) root.setAttribute('data-close-action', closeAction);
            if (requestCloseEvent && !root.hasAttribute('data-request-close-event')) root.setAttribute('data-request-close-event', requestCloseEvent);
            Object.assign(this, methods(root));

            stop = followState(root, 'modal', (value) => {
                this.open = value;
                this.model = value;
            });
            this.$watch('model', (value) => (value ? this.show() : this.close(false)));
            // Bound open from the start (a property already true): it opens once Alpine has set it.
            this.$nextTick(() => { if (this.model && !this.open) this.show(); });
        },

        destroy() {
            stop();
        },

        show() {},
        close() {},
        requestClose() {},
        /** @returns {HTMLElement[]} */
        focusables() {
            return [];
        },
    };
}
