// The title of a dialog (data-slot="{component}-title", the heading of acun:modal.header and
// acun:drawer.header) names it: the dialog's aria-labelledby; its description
// (data-slot="{component}-description") is the aria-describedby. A title or description without
// an id gets {name}-title / {name}-description. Plain HTML needs nothing else.
//
// With Blade the server links them when it sees the header in the dialog's slot. A header drawn
// by a nested Livewire component is not there on the page's own updates (Livewire leaves an
// empty stub for the component, and the browser keeps its DOM), so those updates print the
// fallback name (aria-label) again, and the component's own updates draw the header without ids
// (no dialog around it on the server): this puts the name and the ids back.

import { plugins } from '../core/plugins.js';

/**
 * Keeps dialog named by the title of owner (the component root, marked data-acun-{component})
 * as morphs change them. Returns the function that stops.
 *
 * @param {Element | null} dialog
 * @param {Element} owner
 * @param {'modal' | 'drawer'} component
 * @returns {() => void}
 */
export function keepHeaderName(dialog, owner, component) {
    if (!dialog) return () => {};

    const marker = `data-acun-${component}`;
    /** @param {string} part */
    const ownPart = (part) => [...dialog.querySelectorAll(`[data-slot="${component}-${part}"]`)]
        .find((element) => element.closest(`[${marker}]`) === owner) ?? null;
    /**
     * @param {Element | null} element
     * @param {string} suffix
     */
    const giveId = (element, suffix) => {
        if (!element || element.id) return;
        element.id = `${owner.getAttribute(marker)}-${suffix}`;
        plugins.own(element, 'id');
    };

    const link = () => {
        const title = ownPart('title');
        if (!title) return;

        const description = ownPart('description');
        giveId(title, 'title');
        giveId(description, 'description');

        if (!dialog.hasAttribute('aria-labelledby')) {
            dialog.setAttribute('aria-labelledby', title.id);
            dialog.removeAttribute('aria-label');
        }

        if (description && !dialog.hasAttribute('aria-describedby')) dialog.setAttribute('aria-describedby', description.id);
    };

    link();
    // The dialog's own attributes, a header that arrives (a lazy component) and ids a morph takes away.
    const observer = new MutationObserver(link);
    observer.observe(dialog, { attributes: true, attributeFilter: ['aria-label', 'aria-labelledby', 'aria-describedby', 'id'], childList: true, subtree: true });

    return () => observer.disconnect();
}
