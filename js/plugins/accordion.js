// The accordion, in plain JavaScript (no Alpine). Markup (acun:accordion renders the same):
//
//     <div data-acun-accordion data-multiple="false" class="…">
//         <div data-slot="accordion-item" data-name="general">
//             <h3><button type="button" data-slot="accordion-trigger" aria-expanded="true" class="group …">General</button></h3>
//             <div data-slot="accordion-content" role="region" data-open class="grid grid-rows-[0fr] data-open:grid-rows-[1fr] …"><div class="min-h-0 overflow-hidden">…</div></div>
//         </div>
//     </div>
//
// A trigger opens and closes its item's content; with data-multiple="false" opening one closes the others.
// The open items are the ones the server drew open (aria-expanded="true"). Ids tie each trigger to its
// content (aria-controls, aria-labelledby; made when missing, unique on the page). The height animates in CSS
// and lazy content waits until the first opening (behavior/disclosure.js). Event (from the root, bubbling to
// window): acun:accordion:change ({ name, open, opened: the open names }).

import { dispatch } from '../core/events.js';
import { plugins } from '../core/plugins.js';
import { setRegion } from '../behavior/disclosure.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * An accordion on the page (AcunUI.plugins.get(element, 'accordion')).
 *
 * @typedef {object} AccordionInstance
 * @property {HTMLElement} element  the root (data-acun-accordion)
 * @property {string[]} opened  the open items' names
 * @property {(name: string) => boolean} isItemOpen
 * @property {(name: string, open?: boolean) => void} toggleItem  open: true opens, false closes, none switches
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-accordion';
let counter = 0;

/** A name as an id part (letters, digits, dashes). @param {string} name */
const keyOf = (name) => name.toLowerCase().normalize('NFKD').replace(/[^\w-]+/g, '-').replace(/^-+|-+$/g, '') || 'item';

/**
 * @param {HTMLElement} root
 * @returns {AccordionInstance}
 */
function createAccordion(root) {
    const multiple = root.dataset.multiple === 'true';
    const base = root.id || `acun-accordion-${++counter}`;
    const items = () => /** @type {HTMLElement[]} */ ([...root.querySelectorAll('[data-slot="accordion-item"]')].filter((item) => item.closest(`[${ATTRIBUTE}]`) === root));
    /** @param {HTMLElement} item @param {string} slot */
    const part = (item, slot) => /** @type {HTMLElement | null} */ ([...item.querySelectorAll(`[data-slot="${slot}"]`)].find((element) => element.closest('[data-slot="accordion-item"]') === item) ?? null);
    const itemOf = (/** @type {string} */ name) => items().find((item) => item.dataset.name === name);

    /** @type {Map<HTMLElement, () => void>} */
    const cancels = new Map();

    items().forEach((item) => {
        const trigger = part(item, 'accordion-trigger');
        const content = part(item, 'accordion-content');
        if (content && !content.id) content.id = `${base}-${keyOf(item.dataset.name ?? '')}`;
        if (trigger && content) {
            if (!trigger.id) trigger.id = `${content.id}-heading`;
            trigger.setAttribute('aria-controls', content.id);
            content.setAttribute('aria-labelledby', trigger.id);
            plugins.own(trigger, 'aria-expanded');
        }
        if (content) plugins.own(content, 'hidden', 'data-open');
    });

    const isOpen = (/** @type {string} */ name) => {
        const item = itemOf(name);
        return !!item && part(item, 'accordion-trigger')?.getAttribute('aria-expanded') === 'true';
    };
    const opened = () => items().filter((item) => isOpen(item.dataset.name ?? '')).map((item) => item.dataset.name ?? '');

    /** @param {HTMLElement} item @param {boolean} open */
    const set = (item, open) => {
        const content = part(item, 'accordion-content');
        if (!content) return;
        cancels.get(content)?.();
        cancels.set(content, setRegion(content, part(item, 'accordion-trigger'), open));
    };

    /** @type {AccordionInstance} */
    const instance = {
        element: root,

        get opened() {
            return opened();
        },

        isItemOpen: isOpen,

        toggleItem(name, open) {
            const item = itemOf(name);
            if (!item) return;
            const next = open ?? !isOpen(name);
            if (next === isOpen(name)) return;

            if (next && !multiple) items().filter((other) => other !== item && isOpen(other.dataset.name ?? '')).forEach((other) => set(other, false));
            set(item, next);
            dispatch('acun:accordion:change', { name, open: next, opened: opened() }, root);
        },

        destroy() {
            root.removeEventListener('click', onClick);
            cancels.forEach((cancel) => cancel());
        },
    };

    const onClick = (/** @type {MouseEvent} */ event) => {
        const trigger = /** @type {HTMLButtonElement | null} */ (/** @type {Element} */ (event.target).closest?.('[data-slot="accordion-trigger"]') ?? null);
        const item = trigger?.closest('[data-slot="accordion-item"]');
        if (!trigger || trigger.disabled || !item || item.closest(`[${ATTRIBUTE}]`) !== root) return;
        instance.toggleItem(/** @type {HTMLElement} */ (item).dataset.name ?? '');
    };
    root.addEventListener('click', onClick);

    return instance;
}

/** @type {PluginDefinition} */
export const accordionPlugin = {
    attribute: ATTRIBUTE,
    mount: createAccordion,
};
