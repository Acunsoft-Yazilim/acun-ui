// The Alpine scope adapters/alpine.js gives every tabs root (plugins/tabs.js has the behaviour, with no Alpine):
// content and code written for the 2.x tabs, an Alpine component with x-data on its root, keep reading and
// writing tab (the open tab's value) and calling selected(value); wire:model and x-model on acun:tabs bind
// the open tab both ways (x-modelable on model).

import { plugins } from '../core/plugins.js';

/** @import { AlpineLike } from '../core/types.js' */
/** @import { TabsInstance } from '../plugins/tabs.js' */

/**
 * @param {Element} root
 * @returns {TabsInstance | null}
 */
const instanceOf = (root) => /** @type {TabsInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'tabs') ?? plugins.mount(root, 'tabs')));

/**
 * The scope of a tabs root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function tabsScope(root, Alpine) {
    const value = instanceOf(root)?.value ?? null;
    const scope = Alpine.reactive({
        tab: value,
        model: value,
        /** @param {string} wanted */
        selected(wanted) {
            return this.tab !== null && String(this.tab) === String(wanted);
        },
    });

    const onChange = (/** @type {Event} */ event) => {
        if (event.target !== root) return;
        const next = instanceOf(root)?.value ?? null;
        scope.tab = next;
        scope.model = next;
    };
    root.addEventListener('acun:tabs:change', onChange);
    // Written by the content (tab = 'x') or by the bound property: the tabs follow.
    const select = (/** @type {unknown} */ wanted) => {
        const tabs = instanceOf(root);
        if (tabs && String(wanted ?? '') !== String(tabs.value ?? '')) tabs.select(wanted === null || wanted === undefined ? null : String(wanted));
    };
    const stopTab = Alpine.watch(() => scope.tab, select);
    const stopModel = Alpine.watch(() => scope.model, select);
    // A bound property that starts empty takes the open tab (the first enabled one), as in 2.x.
    queueMicrotask(() => {
        if ((scope.model === null || scope.model === undefined || scope.model === '') && instanceOf(root)?.value) scope.model = instanceOf(root)?.value ?? null;
        else if (scope.model !== null && scope.model !== undefined && scope.model !== '') select(scope.model);
    });

    return {
        scope,
        stop() {
            root.removeEventListener('acun:tabs:change', onChange);
            stopTab();
            stopModel();
        },
    };
}
