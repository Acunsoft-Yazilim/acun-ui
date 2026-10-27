// Tabs, in plain JavaScript (no Alpine). Markup (acun:tabs renders the same, classic and in parts):
//
//     <div data-acun-tabs data-slot="tabs" data-value="summary">
//         <div role="tablist" data-slot="tabs-list">
//             <button type="button" role="tab" data-slot="tabs-trigger" data-value="summary" aria-selected="true" tabindex="0" class="… aria-selected:text-primary-tint-ink">Summary</button>
//             <button type="button" role="tab" data-slot="tabs-trigger" data-value="shipping" aria-selected="false" tabindex="-1">Shipping</button>
//         </div>
//         <div role="tabpanel" data-slot="tabs-panel" data-value="summary" tabindex="0">…</div>
//         <div role="tabpanel" data-slot="tabs-panel" data-value="shipping" tabindex="0" hidden>…</div>
//     </div>
//
// A click on a tab opens its panel; on the tablist Left / Right move to the previous / next enabled tab and
// open it, Home / End to the first / last; only the open tab is in the Tab
// order. The open tab carries aria-selected="true" (its look: aria-selected: classes), the other panels are
// hidden. The value on the root (data-value) is the open tab; without one (or one no enabled tab has) the first
// enabled tab opens. Ids tie each tab to its panel (aria-controls, aria-labelledby; made when missing). A nested
// acun:tabs is its own. Event (from the root, bubbling to window): acun:tabs:change ({ value }) when the open tab
// changes, by the user or select().

import { dispatch } from '../core/events.js';
import { plugins } from '../core/plugins.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * Tabs on the page (AcunUI.plugins.get(element, 'tabs')).
 *
 * @typedef {object} TabsInstance
 * @property {HTMLElement} element  the root (data-acun-tabs)
 * @property {string | null} value  the open tab
 * @property {(value: string | null | undefined, options?: { focus?: boolean }) => void} select  opens a tab (an empty value: the first enabled)
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-tabs';
let counter = 0;

/** An id cannot contain spaces. @param {string} value */
const keyOf = (value) => value.replace(/\s+/g, '-');

/**
 * @param {HTMLElement} root
 * @returns {TabsInstance}
 */
function createTabs(root) {
    /** @param {string} selector */
    const own = (selector) => /** @type {HTMLElement[]} */ ([...root.querySelectorAll(selector)].filter((element) => element.closest(`[${ATTRIBUTE}]`) === root));
    const tabs = () => own('[role="tab"]');
    const panels = () => own('[role="tabpanel"]');
    const enabled = () => tabs().filter((tab) => !(/** @type {HTMLButtonElement} */ (tab)).disabled);
    const base = root.id || `acun-tabs-${++counter}`;

    /** @param {HTMLElement} tab @param {HTMLElement | undefined} panel */
    const link = (tab, panel) => {
        const key = keyOf(tab.dataset.value ?? '');
        if (!tab.id) tab.id = `${base}-tab-${key}`;
        if (panel && !panel.id) panel.id = `${base}-panel-${key}`;
        if (panel) {
            tab.setAttribute('aria-controls', panel.id);
            panel.setAttribute('aria-labelledby', tab.id);
        }
    };
    const panelOf = (/** @type {string} */ value) => panels().find((panel) => panel.dataset.value === value);
    tabs().forEach((tab) => {
        link(tab, panelOf(tab.dataset.value ?? ''));
        plugins.own(tab, 'aria-selected', 'tabindex');
    });
    panels().forEach((panel) => plugins.own(panel, 'hidden'));
    plugins.own(root, 'data-value');

    /** @type {string | null} */
    let value = null;

    /** @param {string | null | undefined} wanted */
    const resolve = (wanted) => {
        const open = enabled();
        if (wanted !== null && wanted !== undefined && wanted !== '' && open.some((tab) => tab.dataset.value === String(wanted))) return String(wanted);

        return open[0]?.dataset.value ?? null;
    };

    /** @param {string | null} next */
    const show = (next) => {
        tabs().forEach((tab) => {
            const selected = tab.dataset.value === next;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.setAttribute('tabindex', selected ? '0' : '-1');
        });
        panels().forEach((panel) => panel.toggleAttribute('hidden', panel.dataset.value !== next));
        if (next === null) root.removeAttribute('data-value');
        else root.dataset.value = next;
    };

    /** @type {TabsInstance} */
    const instance = {
        element: root,

        get value() {
            return value;
        },

        select(wanted, { focus = false } = {}) {
            const next = resolve(wanted);
            const changed = next !== value;
            value = next;
            show(next);
            const tab = tabs().find((element) => element.dataset.value === next);
            if (focus) tab?.focus();
            tab?.scrollIntoView?.({ block: 'nearest', inline: 'nearest' });
            if (changed) dispatch('acun:tabs:change', { value: next }, root);
        },

        destroy() {
            stop.splice(0).forEach((undo) => undo());
        },
    };

    // The value the server wrote (the active prop), else the first enabled tab; no event for the start.
    value = resolve(root.dataset.value);
    show(value);

    /** @type {(() => void)[]} */
    const stop = [];
    /** @param {string} type @param {(event: any) => void} handler */
    const on = (type, handler) => {
        root.addEventListener(type, handler);
        stop.push(() => root.removeEventListener(type, handler));
    };

    on('click', (/** @type {MouseEvent} */ event) => {
        const tab = /** @type {HTMLElement | null} */ (/** @type {Element} */ (event.target).closest?.('[role="tab"]') ?? null);
        if (tab && tab.closest(`[${ATTRIBUTE}]`) === root && !(/** @type {HTMLButtonElement} */ (tab)).disabled) instance.select(tab.dataset.value);
    });
    on('keydown', (/** @type {KeyboardEvent} */ event) => {
        const list = /** @type {HTMLElement | null} */ (/** @type {Element} */ (event.target).closest?.('[role="tablist"]') ?? null);
        if (!list || list.closest(`[${ATTRIBUTE}]`) !== root) return;

        const step = /** @type {Record<string, number | 'first' | 'last'>} */ ({ ArrowRight: 1, ArrowLeft: -1, Home: 'first', End: 'last' })[event.key];
        if (step === undefined) return;

        const open = enabled().filter((tab) => tab.closest('[role="tablist"]') === list);
        if (!open.length) return;
        event.preventDefault();
        const index = open.indexOf(/** @type {HTMLElement} */ (document.activeElement));
        const next = step === 'first' ? open[0] : step === 'last' ? open[open.length - 1] : open[(Math.max(index, 0) + step + open.length) % open.length];
        instance.select(next?.dataset.value, { focus: true });
    });

    return instance;
}

/** @type {PluginDefinition} */
export const tabsPlugin = {
    attribute: ATTRIBUTE,
    mount: createTabs,
};
