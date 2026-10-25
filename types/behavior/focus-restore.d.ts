/**
 * Remembers the focused element (or element: the button a dialog was opened from, which Safari does
 * not focus on a click); the returned function focuses it again if it is still on the page.
 *
 * @param {Element | null} [element]
 * @returns {() => void}
 */
export declare function rememberFocus(element?: Element | null): () => void;
