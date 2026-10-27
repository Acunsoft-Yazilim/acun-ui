// The open dialogs (modal, drawer, command palette…), newest last.
//
// For each it locks the page scroll, keeps Tab inside it, closes it on Escape and
// gives the focus back when it closes. Escape and Tab are handled by one keydown
// listener for the whole page, and only for the newest dialog; one opened on top of
// another is lifted above it (z-index).

import { scrollLock } from './scroll-lock.js';
import { modalDialogIn, trapTab } from './focus-trap.js';
import { rememberFocus } from './focus-restore.js';
import { plugins } from '../core/plugins.js';

/**
 * How a dialog takes part (overlays.open's options).
 *
 * @typedef {object} OverlayOptions
 * @property {() => void} [close]  closes it (Escape)
 * @property {boolean} [lockScroll]  locks the page scroll (default true)
 * @property {HTMLElement | null | (() => HTMLElement | null)} [trap]  the element Tab stays in (none: null)
 * @property {'inside' | 'anywhere' | false | (() => 'inside' | 'anywhere' | false)} [escape]  where an Escape belongs to it (default 'inside')
 * @property {boolean} [restoreFocus]  gives the focus back when it closes (default true)
 * @property {Element | null} [opener]  the element the focus goes back to (default: the one focused now)
 */

/**
 * @typedef {object} OverlayEntry
 * @property {HTMLElement} element
 * @property {() => void} close
 * @property {OverlayOptions['trap'] | null} trap
 * @property {NonNullable<OverlayOptions['escape']>} escape
 * @property {boolean} locked
 * @property {(() => void) | null} restore
 * @property {string | null} zIndex
 */

/** @type {OverlayEntry[]} */
const stack = [];

/**
 * @template T
 * @param {T | (() => T)} value
 * @returns {T}
 */
const resolve = (value) => (typeof value === 'function' ? /** @type {() => T} */ (value)() : value);
/** @param {Element} element */
const entryOf = (element) => stack.find((entry) => entry.element === element);

/**
 * Escape belongs to the dialog when the focus is in it, or nowhere ('anywhere': always).
 *
 * @param {OverlayEntry} entry
 * @param {KeyboardEvent} event
 */
function ownsEscape(entry, event) {
    if (entry.escape === false) return false;
    if (resolve(entry.escape) === 'anywhere') return true;

    const active = document.activeElement;

    return !active || active === document.body || entry.element.contains(active) || entry.element.contains(/** @type {Node | null} */ (event.target));
}

/** @param {KeyboardEvent} event */
function onKeydown(event) {
    const top = stack[stack.length - 1];
    if (!top) return;

    if (event.key === 'Escape') {
        // A character still being composed with an input method (IME: Japanese, Chinese, Korean) takes the Escape.
        if (event.isComposing || event.keyCode === 229) return;
        // A list, menu or search box inside the dialog took the Escape (and stopped it or marked it).
        if (event.defaultPrevented || /** @type {Element | null} */ (event.target)?.closest?.('[role="combobox"][aria-expanded="true"]')) return;
        if (!ownsEscape(top, event)) return;
        // A modal <dialog> the page opened inside the dialog: the browser's Escape closes it (cancel).
        if (modalDialogIn(top.element)) return;
        // Handled: nothing else (a fullscreen video, the page) acts on the same Escape.
        event.preventDefault();
        top.close();
    } else if (event.key === 'Tab') {
        const container = resolve(top.trap);
        if (container) trapTab(event, container, stack.slice(0, -1).map((entry) => entry.element));
    }
}

// The page behind the newest dialog that keeps Tab inside it is inert (the WAI-ARIA modal dialog): the
// pointer, the keyboard and screen readers reach nothing else (Tab alone was kept in before). What the
// page says (toasts, live regions) stays heard. Only what this made inert goes back when it closes,
// and a Livewire morph does not take it away meanwhile (plugins.own).
const LIVE = '[data-acun-toasts], [aria-live], [role="status"], [role="alert"]';
/** @type {Set<Element>} */
const madeInert = new Set();

/** @param {Element} element */
function shut(element) {
    if (element.hasAttribute('inert') || element.matches('script, style, template, link, meta') || element.matches(LIVE)) return;
    // A part of the page with a live region in it: its other parts go, the region stays.
    if (element.querySelector(LIVE)) {
        [...element.children].forEach(shut);
        return;
    }
    element.setAttribute('inert', '');
    plugins.own(element, 'inert');
    madeInert.add(element);
}

function updateInert() {
    madeInert.forEach((element) => element.removeAttribute('inert'));
    madeInert.clear();

    const top = stack[stack.length - 1];
    if (!top || !resolve(top.trap) || typeof document === 'undefined') return;

    // Every sibling of the dialog and of each of its ancestors up to <body>.
    for (let node = /** @type {Element} */ (top.element); node.parentElement && node !== document.body; node = node.parentElement) {
        for (const sibling of node.parentElement.children) {
            if (sibling !== node) shut(sibling);
        }
    }
}

/** @param {OverlayEntry} entry */
function lift(entry) {
    const below = stack[stack.length - 1];
    if (!below) return;

    const z = (/** @type {Element} */ element) => Number.parseInt(getComputedStyle(element).zIndex, 10) || 0;
    const needed = z(below.element) + 1;

    if (z(entry.element) < needed) {
        entry.zIndex = entry.element.style.zIndex;
        entry.element.style.zIndex = String(needed);
    }
}

export const overlays = {
    /**
     * Registers an open dialog. element: its outermost element (the layer).
     * options.close(): closes it (Escape) · lockScroll (true) · trap: the element Tab
     * stays in (or a function returning it, null for none) · escape: 'inside' (default),
     * 'anywhere' or false · restoreFocus (true) · opener: the element the focus goes back to
     * (default: the one focused now).
     *
     * @param {HTMLElement | null | undefined} element
     * @param {OverlayOptions} [options]
     */
    open(element, options = {}) {
        if (!element || entryOf(element)) return;

        /** @type {OverlayEntry} */
        const entry = {
            element,
            close: options.close ?? (() => {}),
            trap: options.trap ?? null,
            escape: options.escape ?? 'inside',
            locked: options.lockScroll !== false,
            restore: options.restoreFocus === false ? null : rememberFocus(options.opener ?? document.activeElement, element),
            zIndex: null,
        };

        if (entry.locked) scrollLock.lock();
        lift(entry);
        stack.push(entry);
        updateInert();
    },

    /**
     * Unregisters it: releases the scroll lock and gives the focus back.
     *
     * @param {Element | null | undefined} element
     */
    close(element) {
        if (!element) return;
        const entry = entryOf(element);
        if (!entry) return;

        stack.splice(stack.indexOf(entry), 1);
        updateInert();
        if (entry.locked) scrollLock.unlock();
        if (entry.zIndex !== null) entry.element.style.zIndex = entry.zIndex;

        // Only while the focus is still in the dialog, or nowhere: not when it went on to another window.
        // A click on the backdrop moves it to the nearest focusable element around the dialog (a
        // <main tabindex="-1"> the dialog is rendered in): that is nowhere too.
        const active = document.activeElement;
        if (entry.restore && (!active || active === document.body || element.contains(active) || active.contains(element))) entry.restore();
    },

    /** @param {Element} element */
    isOpen(element) {
        return Boolean(entryOf(element));
    },

    /**
     * The newest open dialog's element.
     *
     * @returns {HTMLElement | null}
     */
    top() {
        return stack[stack.length - 1]?.element ?? null;
    },

    get count() {
        return stack.length;
    },
};

/** Called once by the runtime. */
export function installOverlayKeys() {
    window.addEventListener('keydown', onKeydown);
}

/** After a wire:navigate page change: dialogs of the old page are gone; one kept by @persist stays. */
export function forgetDetachedOverlays() {
    for (const entry of [...stack]) {
        if (!entry.element.isConnected) stack.splice(stack.indexOf(entry), 1);
    }
    updateInert();

    scrollLock.reset();
    stack.filter((entry) => entry.locked).forEach(() => scrollLock.lock());
}
