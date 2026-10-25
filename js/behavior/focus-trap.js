// Keeps the keyboard focus inside a dialog and gives it its first focus.

import { focusable, inOwnFocusLayer, isVisible } from '../core/dom.js';

/**
 * Focuses a shown [autofocus], else the first focusable element, else the container itself.
 *
 * @param {HTMLElement} container
 * @returns {HTMLElement}
 */
export function focusFirst(container) {
    // An autofocus inside a hidden part (a closed tab, x-show) cannot take the focus.
    const autofocus = focusable(container).find((element) => element.hasAttribute('autofocus'))
        ?? [.../** @type {NodeListOf<HTMLElement>} */ (container.querySelectorAll('[autofocus]'))].find((element) => isVisible(element) && !element.closest('[inert]'));
    const target = autofocus ?? focusable(container)[0] ?? container;
    target.focus({ preventScroll: true });

    return target;
}

// A <dialog> shown with showModal() inside the container (the delete question of a form's action
// bar): the browser makes everything else inert, so Tab goes round in it. A browser that cannot
// tell a modal one (no :modal) takes any open one.
/**
 * @param {HTMLElement} container
 * @returns {HTMLDialogElement | null}
 */
function modalDialogIn(container) {
    const canTell = typeof CSS !== 'undefined' && typeof CSS.supports === 'function' && CSS.supports('selector(:modal)');
    const open = [.../** @type {NodeListOf<HTMLDialogElement>} */ (container.querySelectorAll('dialog[open]'))].filter((dialog) => !canTell || dialog.matches(':modal'));

    return open.at(-1) ?? null;
}

/**
 * Handles a Tab keydown for container: from the last element back to the first and
 * the other way round; a focus that got out (to the page behind) comes back in. In
 * between the browser moves the focus itself. A focus in another window (one opened
 * over the dialog with its own focus handling) is left alone; below: the dialogs under
 * this one, where a focus counts as out. A modal <dialog> open inside container takes
 * its place.
 *
 * @param {KeyboardEvent} event
 * @param {HTMLElement} container
 * @param {Element[]} [below]
 */
export function trapTab(event, container, below = []) {
    container = modalDialogIn(container) ?? container;
    const active = document.activeElement;
    if (active && !container.contains(active) && inOwnFocusLayer(active) && !below.some((layer) => layer.contains(active))) return;

    const elements = focusable(container);

    if (elements.length === 0) {
        event.preventDefault();
        container.focus({ preventScroll: true });
        return;
    }

    const first = elements[0];
    const last = elements[elements.length - 1];

    if (!container.contains(active)) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    } else if (event.shiftKey && (active === first || active === container)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
}
