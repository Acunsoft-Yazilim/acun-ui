// Gives the focus back to where it was before a dialog opened.

/**
 * Remembers the focused element (or element: the button a dialog was opened from, which Safari does
 * not focus on a click); the returned function focuses it again if it is still on the page.
 *
 * @param {Element | null} [element]
 * @returns {() => void}
 */
export function rememberFocus(element = document.activeElement) {
    const target = /** @type {HTMLElement | null} */ (element);

    return () => {
        if (target && target !== document.body && target.isConnected && typeof target.focus === 'function') {
            target.focus({ preventScroll: true });
        }
    };
}
