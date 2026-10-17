// acun:modal: x-data="acunModal({ name, closeAction, requestCloseEvent })" (the
// Blade component prints it). The markup binds open; slots call close() or
// requestClose().

import { EVENTS, dispatch, isFor, listen } from '../core/events.js';
import { focusable } from '../core/dom.js';
import { keepHeaderName } from '../behavior/dialog-name.js';
import { focusFirst } from '../behavior/focus-trap.js';
import { overlays } from '../behavior/overlay-manager.js';

// Sent by a modal that opens: the open ones that do not hold its opener close.
const REPLACE = 'acun:modal:replace';

export function modal({ name, closeAction = null, requestCloseEvent = null } = {}) {
    const stop = [];
    // The component root. Alpine binds this.$el to the element whose directive runs, so a
    // method called from a part (the X, acun:modal.close) would see that part instead.
    let root = null;

    return {
        open: false,
        // The value wire:model / x-model binds (x-modelable): true opens the modal, false closes it.
        model: false,

        init() {
            root = this.$el;
            stop.push(
                listen(EVENTS.modal.open, (event) => isFor(event, name) && this.show()),
                listen(EVENTS.modal.close, (event) => isFor(event, name, { all: true }) && this.close(false)),
                // Replaced by another modal: the server is told, as when the user closes it.
                listen(REPLACE, (event) => event.detail?.modal !== root && !root.contains(event.detail?.opener) && this.close()),
                // The panel (the first dialog inside: a nested modal's comes later) keeps the
                // name of a header that a nested Livewire component draws.
                keepHeaderName(root.querySelector('[role="dialog"], [role="alertdialog"]'), root, 'modal'),
            );

            this.$watch('model', (value) => (value ? this.show() : this.close(false)));
            // Bound open from the start (a property already true): it opens once Alpine has set it.
            this.$nextTick(() => { if (this.model && !this.open) this.show(); });
        },

        destroy() {
            stop.splice(0).forEach((stopListening) => stopListening());
            overlays.close(root);
        },

        show() {
            if (this.open) return;

            // One modal at a time (no doubt about which X belongs to which modal): another
            // open one closes first. One opened from inside an open modal (a confirmation
            // over an edit form) goes on top of it instead; Escape closes the top one.
            dispatch(REPLACE, { modal: root, opener: document.activeElement });

            this.open = this.model = true;
            overlays.open(root, {
                close: () => this.requestClose(),
                trap: () => this.$refs.panel,
            });
            // The panel's x-transition holds $nextTick until the panel is shown.
            this.$nextTick(() => focusFirst(this.$refs.panel));
            dispatch(EVENTS.modal.opened, { name }, root);
        },

        /** notifyServer: false when closed by an event (the server asked for it). */
        close(notifyServer = true) {
            if (!this.open) return;

            this.open = this.model = false;
            overlays.close(root);

            if (notifyServer && closeAction) this.$wire[closeAction]();

            dispatch(EVENTS.modal.closed, { name }, root);
            dispatch('modal-closed', { name }, root);
        },

        /** X, Escape and the backdrop: closes, or asks first (request-close-event, e.g. unsaved changes). */
        requestClose() {
            if (requestCloseEvent) dispatch(requestCloseEvent, {}, root);
            else this.close();
        },

        /** The panel's elements that Tab reaches. */
        focusables() {
            return focusable(this.$refs.panel);
        },
    };
}
