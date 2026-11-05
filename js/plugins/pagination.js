// acun:pagination on a Livewire page, in plain JavaScript: a page button pressed below the top of the list brings
// the component's top back into view (the new page starts there), as a full page load would.
//
//     <nav data-acun-pagination aria-label="Pagination Navigation">… <button wire:click="nextPage('page')">…</button></nav>
//
// Only when the component's top is above the viewport: a list already in view does not move.

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @param {HTMLElement} root
 */
function createPagination(root) {
    const onClick = (/** @type {MouseEvent} */ event) => {
        if (!(/** @type {Element} */ (event.target).closest('a, button:not([disabled])'))) return;
        let component = root.parentElement;
        while (component && !component.hasAttribute('wire:id')) component = component.parentElement;
        if (component && component.getBoundingClientRect().top < 0) component.scrollIntoView({ block: 'start' });
    };
    root.addEventListener('click', onClick);

    return {
        element: root,
        destroy() {
            root.removeEventListener('click', onClick);
        },
    };
}

/** @type {PluginDefinition} */
export const paginationPlugin = {
    attribute: 'data-acun-pagination',
    mount: createPagination,
};
