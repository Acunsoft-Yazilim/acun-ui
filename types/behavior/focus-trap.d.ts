/**
 * Focuses a shown [autofocus], else the first focusable element, else the container itself.
 *
 * @param {HTMLElement} container
 * @returns {HTMLElement}
 */
export declare function focusFirst(container: HTMLElement): HTMLElement;
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
export declare function trapTab(event: KeyboardEvent, container: HTMLElement, below?: Element[]): void;
