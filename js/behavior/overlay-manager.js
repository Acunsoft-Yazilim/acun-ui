// The open dialogs (modal, drawer, command palette…), newest last.
//
// For each it locks the page scroll, keeps Tab inside it, closes it on Escape and
// gives the focus back when it closes. Escape and Tab are handled by one keydown
// listener for the whole page, and only for the newest dialog; one opened on top of
// another is lifted above it (z-index).

import { scrollLock } from './scroll-lock.js';
import { trapTab } from './focus-trap.js';
import { rememberFocus } from './focus-restore.js';

const stack = [];

const resolve = (value) => (typeof value === 'function' ? value() : value);
const entryOf = (element) => stack.find((entry) => entry.element === element);

/** Escape belongs to the dialog when the focus is in it, or nowhere ('anywhere': always). */
function ownsEscape(entry, event) {
    if (entry.escape === false) return false;
    if (resolve(entry.escape) === 'anywhere') return true;

    const active = document.activeElement;

    return !active || active === document.body || entry.element.contains(active) || entry.element.contains(event.target);
}

function onKeydown(event) {
    const top = stack[stack.length - 1];
    if (!top) return;

    if (event.key === 'Escape') {
        // A character still being composed with an input method (IME: Japanese, Chinese, Korean) takes the Escape.
        if (event.isComposing || event.keyCode === 229) return;
        // A list, menu or search box inside the dialog took the Escape (and stopped it or marked it).
        if (event.defaultPrevented || event.target.closest?.('[role="combobox"][aria-expanded="true"]')) return;
        if (!ownsEscape(top, event)) return;
        // Handled: nothing else (a fullscreen video, the page) acts on the same Escape.
        event.preventDefault();
        top.close();
    } else if (event.key === 'Tab') {
        const container = resolve(top.trap);
        if (container) trapTab(event, container, stack.slice(0, -1).map((entry) => entry.element));
    }
}

function lift(entry) {
    const below = stack[stack.length - 1];
    if (!below) return;

    const z = (element) => Number.parseInt(getComputedStyle(element).zIndex, 10) || 0;
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
     * 'anywhere' or false · restoreFocus (true).
     */
    open(element, options = {}) {
        if (!element || entryOf(element)) return;

        const entry = {
            element,
            close: options.close ?? (() => {}),
            trap: options.trap ?? null,
            escape: options.escape ?? 'inside',
            locked: options.lockScroll !== false,
            restore: options.restoreFocus === false ? null : rememberFocus(),
            zIndex: null,
        };

        if (entry.locked) scrollLock.lock();
        lift(entry);
        stack.push(entry);
    },

    /** Unregisters it: releases the scroll lock and gives the focus back. */
    close(element) {
        const entry = entryOf(element);
        if (!entry) return;

        stack.splice(stack.indexOf(entry), 1);
        if (entry.locked) scrollLock.unlock();
        if (entry.zIndex !== null) element.style.zIndex = entry.zIndex;

        // Only while the focus is still in the dialog (or nowhere): not when it went on to another window.
        const active = document.activeElement;
        if (entry.restore && (!active || active === document.body || element.contains(active))) entry.restore();
    },

    isOpen(element) {
        return Boolean(entryOf(element));
    },

    /** The newest open dialog's element. */
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

    scrollLock.reset();
    stack.filter((entry) => entry.locked).forEach(() => scrollLock.lock());
}
