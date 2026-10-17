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

/** Rendered and visible (not inside display: none, not visibility: hidden). */
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
 */
export function focusable(container) {
    const reachable = (element) => element.getAttribute('tabindex') !== '-1'
        && !element.matches(':disabled')
        && !element.closest('[inert]')
        && isVisible(element);
    const elements = [...container.querySelectorAll(FOCUSABLE)].filter(reachable);

    return elements.filter((element) => {
        if (element.type !== 'radio' || !element.name) return true;

        const group = elements.filter((other) => other.type === 'radio' && other.name === element.name && other.form === element.form);

        return element === (group.find((radio) => radio.checked) ?? group[0]);
    });
}

// A window opened over a dialog with its own focus handling (a dropdown list, a date
// picker calendar appended to <body>): a focus there is not "outside" the dialog.
const OWN_FOCUS = '[role="dialog"], [role="alertdialog"], [role="listbox"], [role="menu"], [data-acun-layer], .flatpickr-calendar';

/** The element holds a focus that belongs to a window of its own. */
export function inOwnFocusLayer(element) {
    return !!element?.closest?.(OWN_FOCUS);
}
