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

    return {
        open: false,
        // The value wire:model / x-model binds (x-modelable): true opens the modal, false closes it.
        model: false,

        init() {
            stop.push(
                listen(EVENTS.modal.open, (event) => isFor(event, name) && this.show()),
                listen(EVENTS.modal.close, (event) => isFor(event, name, { all: true }) && this.close(false)),
                // Replaced by another modal: the server is told, as when the user closes it.
                listen(REPLACE, (event) => event.detail?.modal !== this.$el && !this.$el.contains(event.detail?.opener) && this.close()),
                // The panel (the first dialog inside: a nested modal's comes later) keeps the
                // name of a header that a nested Livewire component draws.
                keepHeaderName(this.$el.querySelector('[role="dialog"], [role="alertdialog"]'), this.$el, 'modal'),
            );

            this.$watch('model', (value) => (value ? this.show() : this.close(false)));
            // Bound open from the start (a property already true): it opens once Alpine has set it.
            this.$nextTick(() => { if (this.model && !this.open) this.show(); });
        },

        destroy() {
            stop.splice(0).forEach((stopListening) => stopListening());
            overlays.close(this.$el);
        },

        show() {
            if (this.open) return;

            // One modal at a time (no doubt about which X belongs to which modal): another
            // open one closes first. One opened from inside an open modal (a confirmation
            // over an edit form) goes on top of it instead; Escape closes the top one.
            dispatch(REPLACE, { modal: this.$el, opener: document.activeElement });

            this.open = this.model = true;
            overlays.open(this.$el, {
                close: () => this.requestClose(),
                trap: () => this.$refs.panel,
            });
            // The panel's x-transition holds $nextTick until the panel is shown.
            this.$nextTick(() => focusFirst(this.$refs.panel));
            dispatch(EVENTS.modal.opened, { name }, this.$el);
        },

        /** notifyServer: false when closed by an event (the server asked for it). */
        close(notifyServer = true) {
            if (!this.open) return;

            this.open = this.model = false;
            overlays.close(this.$el);

            if (notifyServer && closeAction) this.$wire[closeAction]();

            dispatch(EVENTS.modal.closed, { name }, this.$el);
            dispatch('modal-closed', { name }, this.$el);
        },

        /** X, Escape and the backdrop: closes, or asks first (request-close-event, e.g. unsaved changes). */
        requestClose() {
            if (requestCloseEvent) dispatch(requestCloseEvent, {}, this.$el);
            else this.close();
        },

        /** The panel's elements that Tab reaches. */
        focusables() {
            return focusable(this.$refs.panel);
        },
    };
}
