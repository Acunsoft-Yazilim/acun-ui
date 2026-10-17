// acun:drawer: x-data="acunDrawer({ name, docked, lockScroll })" (the Blade
// component prints it). The markup binds rendered, entered, opened and overlay;
// slots call close(). Open state can be bound with wire:model / x-model (x-modelable="model").

import { EVENTS, dispatch, isFor, listen } from '../core/events.js';
import { focusable } from '../core/dom.js';
import { keepHeaderName } from '../behavior/dialog-name.js';
import { focusFirst } from '../behavior/focus-trap.js';
import { overlays } from '../behavior/overlay-manager.js';

const DESKTOP = '(min-width: 1024px)';
const onDesktop = () => window.matchMedia?.(DESKTOP)?.matches === true;

export function drawer({ name, docked = false, lockScroll = true } = {}) {
    const stop = [];
    // The component root. Alpine binds this.$el to the element whose directive runs, so a
    // method called from a part (the X, acun:drawer.close) would see that part instead.
    let root = null;
    let closeTimer = null;

    return {
        // rendered: on the page (stays true while the panel slides out) · entered: slid in ·
        // opened: open, false as soon as it starts closing · model: the value wire:model /
        // x-model binds · overlay: modal (a docked panel only below lg; on desktop it sits
        // beside the content and the page stays in use).
        rendered: false,
        entered: false,
        opened: false,
        model: false,
        overlay: !docked,

        init() {
            root = this.$el;
            stop.push(
                listen(EVENTS.drawer.open, (event) => isFor(event, name) && this.openPanel()),
                listen(EVENTS.drawer.toggle, (event) => isFor(event, name) && (this.opened ? this.close() : this.openPanel())),
                listen(EVENTS.drawer.close, (event) => isFor(event, name, { all: true }) && this.close()),
                // The dialog (the root, or the docked panel) keeps the name of a header that
                // a nested Livewire component draws.
                keepHeaderName(root.matches('[role="dialog"]') ? root : root.querySelector('[role="dialog"]'), root, 'drawer'),
            );

            this.$watch('model', (value) => (value ? this.openPanel() : this.close()));

            // A docked panel changes mode with the window (a rotated tablet, a resized window):
            // beside the content from lg up, a modal overlay below.
            const media = docked ? window.matchMedia?.(DESKTOP) : null;
            if (media?.addEventListener) {
                const onChange = () => this.syncDock();
                media.addEventListener('change', onChange);
                stop.push(() => media.removeEventListener('change', onChange));
            }
            // Sent when it opens and as soon as it starts closing.
            this.$watch('opened', (open) => {
                dispatch(open ? EVENTS.drawer.opened : EVENTS.drawer.closed, { name }, root);
                dispatch('drawer-state-changed', { name, open }, root);
            });
        },

        destroy() {
            stop.splice(0).forEach((stopListening) => stopListening());
            clearTimeout(closeTimer);
            overlays.close(root);
        },

        openPanel() {
            if (this.opened) return;

            this.opened = this.model = true;
            if (docked) this.overlay = !onDesktop();
            clearTimeout(closeTimer);
            this.rendered = true;
            this.register();

            // Slides in and takes the focus two frames later: x-show renders the panel on the
            // next frame, and an element that is not rendered cannot take the focus. Closed by
            // then (a quick double toggle), it stays closed. On desktop a docked panel leaves
            // the focus on the trigger beside it.
            requestAnimationFrame(() => requestAnimationFrame(() => {
                this.entered = this.opened;
                if (this.opened && this.overlay) focusFirst(this.$refs.panel);
            }));
        },

        /** Joins the dialog stack in the current mode: a modal overlay locks the page and keeps Tab in. */
        register() {
            overlays.open(root, {
                close: () => this.close(),
                lockScroll: lockScroll && this.overlay,
                trap: () => (this.overlay ? this.$refs.panel : null),
                escape: this.overlay ? 'inside' : 'anywhere',
            });
        },

        /** The window crossed lg while a docked panel is open: it changes mode in place. */
        syncDock() {
            const overlay = !onDesktop();
            if (!docked || overlay === this.overlay) return;

            this.overlay = overlay;
            if (!this.opened) return;

            overlays.close(root);
            this.register();
            if (overlay && !this.$refs.panel.contains(document.activeElement)) focusFirst(this.$refs.panel);
        },

        close() {
            if (!this.opened) return;

            this.opened = this.model = false;
            this.entered = false;
            overlays.close(root);
            clearTimeout(closeTimer);
            closeTimer = setTimeout(() => {
                this.rendered = false;
            }, 200);
        },

        /**
         * A click in the panel: a followed link closes it (a menu). Not a link the page
         * handles itself (wire:click.prevent, a cancelled click: pagination, tabs), one
         * opened elsewhere (target, download, a modifier key) or one inside
         * [data-drawer-keep-open]. wire:navigate links cancel the click too, but they do
         * leave the page.
         */
        followLink(event) {
            const link = event.target.closest?.('a[href]');
            if (!link || link.closest('[data-drawer-keep-open]')) return;

            const navigates = [...link.attributes].some((attribute) => attribute.name.startsWith('wire:navigate'));
            const elsewhere = (link.target && link.target !== '_self') || link.hasAttribute('download')
                || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

            if (!elsewhere && (navigates || !event.defaultPrevented)) this.close();
        },

        /** The panel's elements that Tab reaches. */
        focusableElements() {
            return focusable(this.$refs.panel);
        },
    };
}
