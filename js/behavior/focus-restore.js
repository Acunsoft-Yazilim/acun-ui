// Gives the focus back to where it was before a dialog opened.

import { focusAndReveal, isVisible } from '../core/dom.js';

/**
 * @param {Element | null | undefined} element
 * @returns {element is HTMLElement}
 */
const canTakeFocus = (element) => !!element && element !== document.body && element.isConnected
    && typeof (/** @type {HTMLElement} */ (element)).focus === 'function' && isVisible(/** @type {HTMLElement} */ (element));

/**
 * Remembers the focused element (or element: the button a dialog was opened from, which Safari does
 * not focus on a click); the returned function focuses it again. When that element is gone or hidden
 * by then (a menu item: the menu closed and focused its own button), the element focused at the time
 * of the call takes the focus instead. Never an element inside within (the dialog being closed).
 *
 * @param {Element | null} [element]
 * @param {Element | null} [within]
 * @returns {() => void}
 */
export function rememberFocus(element = document.activeElement, within = null) {
    const candidates = [element, document.activeElement];

    return () => {
        for (const candidate of candidates) {
            if (!canTakeFocus(candidate) || within?.contains(candidate)) continue;

            focusAndReveal(candidate);
            if (document.activeElement === candidate) return;
        }
        // Nowhere to go back to: at least the focus does not stay in the dialog that is being hidden.
        const active = /** @type {HTMLElement | null} */ (document.activeElement);
        if (active && within?.contains(active)) active.blur();
    };
}
