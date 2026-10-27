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
export declare function rememberFocus(element?: Element | null, within?: Element | null): () => void;
