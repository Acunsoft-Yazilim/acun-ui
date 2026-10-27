// Deprecated until 3.0: the Alpine side of acun:drawer from before 2.1. The behaviour is the drawer
// plugin's (plugins/drawer.js), with no Alpine; these delegate to it.
// - drawer(): Alpine.data('acunDrawer'), for markup of its own written with
//   x-data="acunDrawer({ name, docked, lockScroll })" (x-ref="panel", x-show="rendered").
// - drawerScope(): the Alpine scope adapters/alpine.js gives every drawer root, so content that
//   calls close() or reads opened keeps working, and wire:model / x-model bind its model.
//   New markup: data-acun-close.

import { plugins } from '../core/plugins.js';
import { followState } from '../adapters/alpine.js';

/** @import { AlpineLike } from '../core/types.js' */
/** @import { DrawerInstance } from '../plugins/drawer.js' */

let warned = false;
const warn = () => {
    if (!warned) console.warn('[Acun UI] x-data="acunDrawer(…)" is deprecated and goes in 3.0: use the drawer markup with data-acun-drawer (it needs no Alpine).');
    warned = true;
};

/**
 * @param {Element} root
 * @returns {DrawerInstance | null}
 */
const instanceOf = (root) => /** @type {DrawerInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'drawer') ?? plugins.mount(root, 'drawer')));

/**
 * What drawerScope() and drawer() share: the 2.0 names, on the plugin instance of root.
 *
 * @param {Element} root
 */
function methods(root) {
    return {
        openPanel() {
            instanceOf(root)?.open();
        },
        close() {
            instanceOf(root)?.close();
        },
        // The plugin registers itself and follows the window's width.
        register() {},
        syncDock() {},
        // The plugin closes the panel on a followed link itself.
        followLink() {},
        /** @returns {HTMLElement[]} */
        focusableElements() {
            return instanceOf(root)?.focusables() ?? [];
        },
    };
}

/** @param {Element} root */
const state = (root) => {
    const instance = instanceOf(root);
    const open = instance?.isOpen ?? false;

    return { rendered: open, entered: open, opened: open, model: open, overlay: instance?.overlay ?? true };
};

/**
 * The scope of a drawer root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function drawerScope(root, Alpine) {
    const scope = Alpine.reactive({ ...state(root), ...methods(root) });
    const stopFollowing = followState(root, 'drawer', () => Object.assign(scope, state(root)));
    // The bound property (wire:model / x-model through x-modelable): true opens, false closes.
    const stopWatching = Alpine.watch(() => scope.model, (value) => (value ? instanceOf(root)?.open() : instanceOf(root)?.close()));

    return {
        scope,
        stop() {
            stopFollowing();
            stopWatching();
        },
    };
}

/**
 * Alpine.data('acunDrawer'): deprecated, see above.
 *
 * @param {{ name?: string, docked?: boolean, lockScroll?: boolean }} [options]
 */
export function drawer({ name, docked = false, lockScroll = true } = {}) {
    let stop = () => {};

    return {
        rendered: false,
        entered: false,
        opened: false,
        model: false,
        overlay: !docked,

        /** @this {AlpineMagics & Record<string, any>} */
        init() {
            warn();
            const root = this.$el;
            if (!root.hasAttribute('data-acun-drawer') && name) root.setAttribute('data-acun-drawer', name);
            const changed = (docked && !root.hasAttribute('data-docked')) || (!lockScroll && root.getAttribute('data-lock-scroll') !== 'false');
            if (docked) root.setAttribute('data-docked', '');
            if (!lockScroll) root.setAttribute('data-lock-scroll', 'false');
            // On a page the runtime starts before Alpine, so the plugin may have mounted the root before
            // these options were on it: mount it again with them (it reads them once, at mount).
            if (changed && plugins.get(root, 'drawer')) {
                plugins.unmount(root, 'drawer');
                plugins.mount(root, 'drawer');
            }
            Object.assign(this, methods(root));

            stop = followState(root, 'drawer', () => Object.assign(this, state(root)));
            this.$watch('model', (value) => (value ? this.openPanel() : this.close()));
        },

        destroy() {
            stop();
        },

        openPanel() {},
        register() {},
        syncDock() {},
        close() {},
        followLink() {},
        /** @returns {HTMLElement[]} */
        focusableElements() {
            return [];
        },
    };
}

/** @typedef {{ $el: HTMLElement, $watch: (property: string, callback: (value: any) => void) => void, $nextTick: (callback: () => void) => void }} AlpineMagics */
