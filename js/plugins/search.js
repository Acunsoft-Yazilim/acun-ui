// acun:search's clear button and shortcut key, in plain JavaScript (no Alpine):
//
//     <div role="search" data-acun-search data-shortcut="/">
//         <input data-acun-control type="search" …>
//         <button type="button" data-slot="search-clear" aria-label="Clear search">×</button>
//     </div>
//
// The clear button (and Escape in a field with text) empties the field, tells Livewire / the page with input and
// change events and keeps the focus in the field. The shortcut key focuses the field from anywhere on the page,
// except while typing in another field or with a modifier key. Shown or not, the clear button and the hint follow
// the field's :placeholder-shown in CSS.

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @param {HTMLElement} root
 */
function createSearch(root) {
    const input = /** @type {HTMLInputElement | null} */ (root.querySelector('input[type="search"], input'));
    const clearButton = root.querySelector('[data-slot="search-clear"]');
    const shortcut = root.dataset.shortcut;

    const clear = () => {
        if (!input) return;
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.focus();
    };
    const onKeydown = (/** @type {KeyboardEvent} */ event) => {
        if (event.key !== 'Escape' || !clearButton || !input || input.value === '' || event.target !== input) return;
        event.preventDefault();
        event.stopPropagation();
        clear();
    };
    const onShortcut = (/** @type {KeyboardEvent} */ event) => {
        const target = /** @type {Element | null} */ (event.target);
        if (event.key !== shortcut || event.ctrlKey || event.metaKey || event.altKey || target?.closest?.('input, textarea, select, [contenteditable]')) return;
        event.preventDefault();
        input?.focus();
    };

    clearButton?.addEventListener('click', clear);
    root.addEventListener('keydown', onKeydown);
    if (shortcut) window.addEventListener('keydown', onShortcut);

    return {
        element: root,
        destroy() {
            clearButton?.removeEventListener('click', clear);
            root.removeEventListener('keydown', onKeydown);
            window.removeEventListener('keydown', onShortcut);
        },
    };
}

/** @type {PluginDefinition} */
export const searchPlugin = {
    attribute: 'data-acun-search',
    mount: createSearch,
};
