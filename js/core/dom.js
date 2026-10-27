// DOM helpers shared by the behaviours and components.

export const FOCUSABLE = [
    'a[href]',
    'area[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    // A <details> opens from its <summary>; an iframe (a map, a video) and media with controls
    // take the focus too.
    'summary',
    'iframe',
    'audio[controls]',
    'video[controls]',
    '[contenteditable]:not([contenteditable="false"])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

/**
 * Rendered and visible (not inside display: none, not visibility: hidden).
 *
 * @param {HTMLElement} element
 */
export function isVisible(element) {
    if (element.hidden) return false;

    return typeof element.checkVisibility === 'function'
        ? element.checkVisibility({ visibilityProperty: true })
        : element.offsetParent !== null;
}

/**
 * The elements inside container that Tab reaches, in order. Left out: tabindex="-1",
 * controls disabled by their fieldset, anything inside [inert], and the radios of a
 * group but one (the checked radio, else the first): Tab stops on a group once.
 *
 * @param {ParentNode} container
 * @returns {HTMLElement[]}
 */
export function focusable(container) {
    /** @param {HTMLElement} element */
    const reachable = (element) => element.getAttribute('tabindex') !== '-1'
        && !element.matches(':disabled')
        && !element.closest('[inert]')
        && isVisible(element);
    const elements = [.../** @type {NodeListOf<HTMLElement>} */ (container.querySelectorAll(FOCUSABLE))].filter(reachable);
    /** @param {HTMLElement} element */
    const radio = (element) => (element instanceof HTMLInputElement && element.type === 'radio' ? element : null);

    return elements.filter((element) => {
        const input = radio(element);
        if (!input?.name) return true;

        const group = elements.map(radio).filter((other) => other !== null && other.name === input.name && other.form === input.form);

        return input === (group.find((other) => other?.checked) ?? group[0]);
    });
}

// A window opened over a dialog with its own focus handling (a dropdown list, a date
// picker calendar appended to <body>): a focus there is not "outside" the dialog.
const OWN_FOCUS = '[role="dialog"], [role="alertdialog"], [role="listbox"], [role="menu"], [data-acun-layer], .flatpickr-calendar';

/**
 * The element holds a focus that belongs to a window of its own.
 *
 * @param {Element | null | undefined} element
 */
export function inOwnFocusLayer(element) {
    return !!element?.closest?.(OWN_FOCUS);
}

/**
 * A part of a component (data-slot="{slot}"), not of a component of the same kind nested inside it
 * (a modal in a modal); fallback: a selector for markup without data-slot (2.0 and earlier).
 *
 * @param {HTMLElement} root       the component's root, marked with attribute
 * @param {string} attribute       data-acun-modal, data-acun-drawer…
 * @param {string} slot
 * @param {string} [fallback]
 * @returns {HTMLElement | null}
 */
export function ownPart(root, attribute, slot, fallback) {
    const part = [.../** @type {NodeListOf<HTMLElement>} */ (root.querySelectorAll(`[data-slot="${slot}"]`))].find((element) => element.closest(`[${attribute}]`) === root);

    return part ?? (fallback ? root.querySelector(fallback) : null);
}

/**
 * Focuses element without scrolling (a dialog still moving in would jump), then, once the enter
 * transitions are over, brings it into view when it ended up outside the window (WCAG 2.4.11).
 *
 * @param {HTMLElement} element
 */
export function focusAndReveal(element) {
    element.focus({ preventScroll: true });
    if (typeof window === 'undefined') return;
    setTimeout(() => {
        if (document.activeElement !== element || !element.isConnected) return;
        const box = element.getBoundingClientRect();
        if (box.top < 0 || box.bottom > window.innerHeight || box.left < 0 || box.right > window.innerWidth) element.scrollIntoView?.({ block: 'nearest', inline: 'nearest' });
    }, 250);
}

