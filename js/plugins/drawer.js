// The drawer (a side panel), in plain JavaScript (no Alpine). Markup (acun:drawer renders the same):
//
//     <div data-acun-drawer="order-detail" id="order-detail" hidden role="dialog" aria-modal="true" aria-label="Order" class="relative z-50">
//         <div data-slot="drawer-backdrop" data-acun-close class="ui-backdrop fixed inset-0 opacity-0 transition-opacity duration-200 data-open:opacity-100" aria-hidden="true"></div>
//         <div class="fixed inset-y-0 right-0 flex max-w-full">
//             <div data-slot="drawer-panel" tabindex="-1" class="… translate-x-full transition-transform duration-200 data-open:translate-x-0">…</div>
//         </div>
//     </div>
//     <button type="button" data-acun-toggle="order-detail" aria-expanded="false">Details</button>
//
// Open, the root, the backdrop and the panel carry data-open (and the root loses hidden); the CSS
// transitions do the rest (core/transition.js). Options on the root: data-lock-scroll="false" (the
// page keeps scrolling) and data-docked: a side panel beside the content from lg up (the page stays
// in use, no focus trap, Escape from anywhere) and a modal overlay below; the root stays, its
// backdrop and panel are hidden when closed. A followed link inside the panel closes it (a menu),
// except one the page handles itself, one opened elsewhere and one inside [data-drawer-keep-open].
// Events (from the root, bubbling to window), when it opens and as soon as it starts closing:
// acun:drawer:opened / acun:drawer:closed ({ name }) and drawer-state-changed ({ name, open }).

import { EVENTS, dispatch, listen } from '../core/events.js';
import { focusable, ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';
import { enter, leave } from '../core/transition.js';
import { keepHeaderName } from '../behavior/dialog-name.js';
import { focusFirst } from '../behavior/focus-trap.js';
import { overlays } from '../behavior/overlay-manager.js';

/** @import { OpenOptions, PluginDefinition } from '../core/plugins.js' */

/**
 * A drawer on the page (AcunUI.plugins.get(element, 'drawer')).
 *
 * @typedef {object} DrawerInstance
 * @property {HTMLElement} element  the root (data-acun-drawer)
 * @property {HTMLElement} panel    the sliding panel
 * @property {boolean} docked       beside the content from lg up (data-docked)
 * @property {string | null} name
 * @property {boolean} isOpen
 * @property {boolean} overlay      modal now (a docked panel only below lg)
 * @property {(options?: OpenOptions) => void} open
 * @property {() => void} close
 * @property {(options?: OpenOptions) => void} toggle
 * @property {() => HTMLElement[]} focusables  the panel's elements that Tab reaches
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-drawer';
const DESKTOP = '(min-width: 1024px)';
const onDesktop = () => window.matchMedia?.(DESKTOP)?.matches === true;

/**
 * A click in the panel: a followed link closes it (a menu). Not a link the page handles itself
 * (wire:click.prevent, a cancelled click: pagination, tabs), one opened elsewhere (target, download,
 * a modifier key) or one inside [data-drawer-keep-open]. wire:navigate links cancel the click too,
 * but they do leave the page.
 *
 * @param {MouseEvent} event
 */
function closesOnClick(event) {
    const link = /** @type {HTMLAnchorElement | null} */ (/** @type {Element | null} */ (event.target)?.closest?.('a[href]') ?? null);
    if (!link || link.closest('[data-drawer-keep-open]')) return false;

    const navigates = [...link.attributes].some((attribute) => attribute.name.startsWith('wire:navigate'));
    const elsewhere = (link.target && link.target !== '_self') || link.hasAttribute('download')
        || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

    return !elsewhere && (navigates || !event.defaultPrevented);
}

/**
 * @param {string | null | undefined} name
 * @returns {DrawerInstance[]}
 */
const named = (name) => /** @type {DrawerInstance[]} */ (/** @type {unknown} */ (plugins.find(name, 'drawer')));

/**
 * @param {HTMLElement} root
 * @returns {DrawerInstance}
 */
function createDrawer(root) {
    const name = () => root.getAttribute(ATTRIBUTE);
    const docked = root.hasAttribute('data-docked');
    const lockScroll = root.getAttribute('data-lock-scroll') !== 'false';
    // The markup of 2.0 and earlier (x-data="acunDrawer(…)", x-ref="panel") shows and hides itself.
    const panel = ownPart(root, ATTRIBUTE, 'drawer-panel', '[x-ref="panel"]') ?? root;
    const managed = panel.getAttribute('data-slot') === 'drawer-panel';
    const backdrop = ownPart(root, ATTRIBUTE, 'drawer-backdrop');
    const dialog = root.matches('[role="dialog"]') ? root : (panel.matches('[role="dialog"]') ? panel : root.querySelector('[role="dialog"]'));
    const layers = managed ? (docked ? [backdrop, panel] : [root]).filter((layer) => layer !== null) : [];
    const parts = [root, backdrop, managed ? panel : null].filter((part) => part !== null);

    let opened = false;
    // Modal: always, except a docked panel on desktop (beside the content, the page stays in use).
    let overlay = !docked || !onDesktop();
    let cancelLeave = () => {};
    /** @type {(() => void)[]} */
    const stop = [];

    plugins.own(root, 'hidden', 'data-open');
    parts.forEach((part) => plugins.own(part, 'data-open', 'hidden'));

    const markModal = () => {
        if (!docked || !dialog) return;
        dialog.setAttribute('aria-modal', overlay ? 'true' : 'false');
        plugins.own(dialog, 'aria-modal');
    };

    /** Joins the dialog stack in the current mode: a modal overlay locks the page and keeps Tab in. */
    /** @param {Element | null} opener */
    const register = (opener) => overlays.open(root, {
        close: () => instance.close(),
        lockScroll: lockScroll && overlay,
        trap: () => (overlay ? panel : null),
        escape: overlay ? 'inside' : 'anywhere',
        opener,
    });

    const announce = () => {
        plugins.expand(name(), opened);
        dispatch(opened ? EVENTS.drawer.opened : EVENTS.drawer.closed, { name: name() }, root);
        dispatch('drawer-state-changed', { name: name(), open: opened }, root);
    };

    /** @type {DrawerInstance} */
    const instance = {
        element: root,
        panel,
        docked,

        get name() {
            return name();
        },

        get isOpen() {
            return opened;
        },

        /** Modal now (a docked panel only below lg). */
        get overlay() {
            return overlay;
        },

        open({ opener } = {}) {
            if (opened) return;

            opened = true;
            if (docked) overlay = !onDesktop();
            markModal();
            cancelLeave();
            enter(layers, parts);
            register(opener ?? document.activeElement);
            // On desktop a docked panel leaves the focus on the trigger beside it. The markup of 2.0
            // shows its panel on the frame after Alpine's next update (x-show).
            if (overlay && managed) focusFirst(panel);
            else if (overlay) requestAnimationFrame(() => requestAnimationFrame(() => opened && focusFirst(panel)));
            announce();
        },

        close() {
            if (!opened) return;

            opened = false;
            cancelLeave = leave(layers, parts);
            overlays.close(root);
            announce();
        },

        toggle(options) {
            if (opened) instance.close();
            else instance.open(options);
        },

        focusables() {
            return focusable(panel);
        },

        destroy() {
            stop.splice(0).forEach((undo) => undo());
            cancelLeave();
            overlays.close(root);
        },
    };

    markModal();
    stop.push(keepHeaderName(dialog, root, 'drawer'));

    const onPanelClick = (/** @type {MouseEvent} */ event) => closesOnClick(event) && instance.close();
    panel.addEventListener('click', onPanelClick);
    stop.push(() => panel.removeEventListener('click', onPanelClick));

    // A docked panel changes mode with the window (a rotated tablet, a resized window): beside the
    // content from lg up, a modal overlay below.
    const media = docked ? window.matchMedia?.(DESKTOP) : null;
    if (media?.addEventListener) {
        const onChange = () => {
            const next = !onDesktop();
            if (next === overlay) return;

            overlay = next;
            markModal();
            if (!opened) return;

            overlays.close(root);
            register(null);
            if (overlay && !panel.contains(document.activeElement)) focusFirst(panel);
        };
        media.addEventListener('change', onChange);
        stop.push(() => media.removeEventListener('change', onChange));
    }

    return instance;
}

/** @type {PluginDefinition} */
export const drawerPlugin = {
    attribute: ATTRIBUTE,

    // The commands on window (AcunUI.drawer.open(name), Livewire's dispatch('open-drawer', name: …)).
    setup() {
        listen(EVENTS.drawer.open, (event) => named(event.detail?.name).forEach((drawer) => drawer.open()));
        listen(EVENTS.drawer.toggle, (event) => named(event.detail?.name).forEach((drawer) => drawer.toggle()));
        listen(EVENTS.drawer.close, (event) => {
            const target = event.detail?.name;
            const drawers = target === undefined || target === null ? /** @type {DrawerInstance[]} */ (/** @type {unknown} */ (plugins.all('drawer'))) : named(target);
            drawers.forEach((drawer) => drawer.close());
        });
    },

    mount: createDrawer,
};
