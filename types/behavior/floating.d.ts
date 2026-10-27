/**
 * Places panel once: its preferred side, the other one when that fits better, and a sideways shift.
 *
 * @param {HTMLElement} panel
 * @param {Element} anchor   what it opens from (the trigger)
 * @param {'bottom' | 'top'} preferred
 */
export declare function place(panel: HTMLElement, anchor: Element, preferred: 'bottom' | 'top'): void;
/**
 * Places panel now and again on resize and scroll until the returned function is called (the panel keeps
 * its place while it fades out; the next opening places it again).
 *
 * @param {HTMLElement} panel
 * @param {Element} anchor
 * @param {'bottom' | 'top'} preferred
 * @returns {() => void}
 */
export declare function follow(panel: HTMLElement, anchor: Element, preferred: 'bottom' | 'top'): () => void;
