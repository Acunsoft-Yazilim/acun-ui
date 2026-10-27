// The Alpine scopes adapters/alpine.js gives every accordion and collapse root (plugins/accordion.js and
// plugins/collapse.js have the behaviour, with no Alpine). Content and code written for the 2.x components,
// Alpine components with x-data on their root, keep working: the accordion's open (the open names), isOpen(name),
// wasOpened(name) (the lazy content's x-if) and toggle(name); the collapse's open (true opens, false closes).

import { plugins } from '../core/plugins.js';

/** @import { AlpineLike } from '../core/types.js' */
/** @import { AccordionInstance } from '../plugins/accordion.js' */
/** @import { CollapseInstance } from '../plugins/collapse.js' */

/**
 * @param {Element} root
 * @returns {AccordionInstance | null}
 */
const accordionOf = (root) => /** @type {AccordionInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'accordion') ?? plugins.mount(root, 'accordion')));

/**
 * @param {Element} root
 * @returns {CollapseInstance | null}
 */
const collapseOf = (root) => /** @type {CollapseInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'collapse') ?? plugins.mount(root, 'collapse')));

/**
 * The scope of an accordion root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function accordionScope(root, Alpine) {
    const start = accordionOf(root)?.opened ?? [];
    const scope = Alpine.reactive({
        open: [...start],
        // Sections opened at least once: their lazy content stays.
        seen: [...start],
        /** @param {string} name */
        isOpen(name) {
            return this.open.includes(name);
        },
        /** @param {string} name */
        wasOpened(name) {
            return this.seen.includes(name);
        },
        /** @param {string} name */
        toggle(name) {
            accordionOf(root)?.toggleItem(name);
        },
    });

    const onChange = (/** @type {Event} */ event) => {
        if (event.target !== root) return;
        const { name, open, opened } = /** @type {CustomEvent<{ name: string, open: boolean, opened: string[] }>} */ (event).detail;
        if (open && !scope.seen.includes(name)) scope.seen.push(name);
        scope.open = [...opened];
    };
    root.addEventListener('acun:accordion:change', onChange);

    return { scope, stop: () => root.removeEventListener('acun:accordion:change', onChange) };
}

/**
 * The scope of a collapse root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function collapseScope(root, Alpine) {
    const scope = Alpine.reactive({ open: collapseOf(root)?.isOpen ?? false });
    const onChange = (/** @type {Event} */ event) => {
        if (event.target === root) scope.open = /** @type {CustomEvent<{ open: boolean }>} */ (event).detail.open;
    };
    root.addEventListener('acun:collapse:change', onChange);
    const stopWatching = Alpine.watch(() => scope.open, (value) => collapseOf(root)?.setOpen(!!value));

    return {
        scope,
        stop() {
            root.removeEventListener('acun:collapse:change', onChange);
            stopWatching();
        },
    };
}
