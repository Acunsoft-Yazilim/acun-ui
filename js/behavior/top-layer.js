// A dialog layer (the modal's and the drawer's root: fixed, inset-0, z-50) is drawn above the page only when no
// ancestor holds it: a sticky or fixed box, a transform, a filter, containment or a z-index makes a stacking context
// (or a containing block for fixed boxes), and z-50 then counts only inside it. A sticky product gallery with a
// full-screen view, a modal written in a sticky toolbar: the page's own sticky header covered them.
//
// raise(layer) puts such a layer in the browser's top layer while it is open (the Popover API: popover="manual" and
// showPopover()), above everything, without moving it in the DOM: a Livewire morph, wire:click in it and the focus
// trap work as before. A layer nothing holds stays as it is (the page's toasts and the date pickers that a library
// appends to <body> stay above it). data-top-layer marks a raised layer (css/core.css takes the popover's own box
// styles off it). Without the Popover API nothing changes.

import { plugins } from '../core/plugins.js';

/** Values of these properties that make a stacking context or a containing block for fixed descendants. */
const NONE = ['none', 'auto', 'normal', ''];

/**
 * Whether an ancestor of element (below <body>) would hold a fixed layer in its stacking context or containing block.
 *
 * @param {Element} element
 */
export function held(element) {
    for (let node = element.parentElement; node && node !== document.body && node !== document.documentElement; node = node.parentElement) {
        const style = getComputedStyle(node);
        if (style.position === 'sticky' || style.position === 'fixed') return true;
        if (style.position !== 'static' && !NONE.includes(style.zIndex)) return true;
        if (Number.parseFloat(style.opacity) < 1 || style.isolation === 'isolate') return true;
        for (const property of ['transform', 'translate', 'rotate', 'scale', 'filter', 'backdropFilter', 'perspective', 'clipPath', 'mask']) {
            const value = /** @type {any} */ (style)[property];
            if (typeof value === 'string' && !NONE.includes(value)) return true;
        }
        if (/\b(layout|paint|strict|content)\b/.test(style.contain ?? '')) return true;
        if (!NONE.includes(style.containerType ?? '')) return true;
        if (/\b(transform|filter|perspective|opacity)\b/.test(style.willChange ?? '')) return true;
    }

    return false;
}

/**
 * Raises layer into the top layer when an ancestor would hold it; returns the function that lowers it again.
 *
 * @param {HTMLElement} layer
 * @returns {() => void}
 */
export function raise(layer) {
    if (typeof layer.showPopover !== 'function' || !held(layer)) return () => {};
    layer.setAttribute('popover', 'manual');
    layer.setAttribute('data-top-layer', '');
    // Kept through a Livewire morph while it is open (the server's markup has neither).
    plugins.own(layer, 'popover', 'data-top-layer');
    try {
        if (!layer.matches(':popover-open')) layer.showPopover();
    } catch {
        // A layer the browser cannot show as a popover (not connected): it stays where it is.
    }

    return () => {
        try {
            if (layer.matches(':popover-open')) layer.hidePopover();
        } catch {
            // Already hidden.
        }
        layer.removeAttribute('popover');
        layer.removeAttribute('data-top-layer');
    };
}
