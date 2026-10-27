// The tooltip, in plain JavaScript (no Alpine). Markup (acun:tooltip renders the same):
//
//     <span data-acun-tooltip class="relative">
//         <button type="button">Delete</button>
//         <span role="tooltip" data-slot="tooltip-content" data-position="top" popover="manual" hidden class="fixed m-0 inset-auto … opacity-0 data-open:opacity-100">Delete the order</span>
//     </span>
//
// The bubble shows while the pointer is on the element (or on the bubble: a short delay covers the gap) and
// while the focus is inside it; Escape hides it, also when the pointer opened it (WCAG 1.4.13), and inside a
// dialog it hides only the tooltip. It describes the element's control (aria-describedby on the first link,
// button or field, else the first focusable element; an id is made when the bubble has none). The bubble is
// shown in the browser's top layer (the Popover API: above dialogs, not clipped by a scrolling panel, and left
// in place in the DOM, so a Livewire morph finds it where the server put it) at the element's screen position
// (fixed: above it with data-position="top", else below); a browser without the Popover API shows it fixed.

import { plugins } from '../core/plugins.js';
import { enter, leave } from '../core/transition.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * A tooltip on the page (AcunUI.plugins.get(element, 'tooltip')).
 *
 * @typedef {object} TooltipInstance
 * @property {HTMLElement} element  the root (data-acun-tooltip)
 * @property {HTMLElement | null} content  the bubble
 * @property {boolean} isOpen
 * @property {() => void} open
 * @property {(options?: { delay?: number, notify?: boolean }) => void} close
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-tooltip';
/** The pause before the bubble hides when the pointer leaves (it may be on its way to the bubble). */
const HIDE_DELAY = 120;
/** The space between the element and the bubble (px). */
const GAP = 6;
let counter = 0;

/** @param {HTMLElement} element @param {boolean} on */
const topLayer = (element, on) => {
    const popover = /** @type {HTMLElement & { showPopover?: () => void, hidePopover?: () => void }} */ (element);
    try {
        if (on) popover.showPopover?.();
        else popover.hidePopover?.();
    } catch {
        // Already shown or hidden, or not a popover (an older browser): it is shown fixed.
    }
};

/**
 * @param {HTMLElement} root
 * @returns {TooltipInstance}
 */
function createTooltip(root) {
    const content = /** @type {HTMLElement | null} */ ([...root.querySelectorAll('[data-slot="tooltip-content"]')].find((element) => element.closest(`[${ATTRIBUTE}]`) === root) ?? null);
    const control = /** @type {HTMLElement | null} */ (root.querySelector('a[href], button, input, select, textarea') ?? root.querySelector('[tabindex]'));
    const above = content?.dataset.position !== 'bottom';

    if (content && !content.id) content.id = `acun-tooltip-${++counter}`;
    if (content && control && control !== content) {
        control.setAttribute('aria-describedby', content.id);
        plugins.own(control, 'aria-describedby');
    }
    if (content) plugins.own(content, 'hidden', 'data-open', 'style');

    let shown = false;
    /** @type {ReturnType<typeof setTimeout> | undefined} */
    let timer;
    let cancelLeave = () => {};

    const place = () => {
        if (!content) return;
        const box = root.getBoundingClientRect();
        content.style.left = `${box.left + box.width / 2}px`;
        content.style.top = `${above ? box.top - GAP : box.bottom + GAP}px`;
    };

    // Before the dialogs' Escape (capture): the Escape that hides the tooltip is marked handled, so it
    // does not also close the modal or drawer around (or under) it.
    const onWindowKeydown = (/** @type {KeyboardEvent} */ event) => {
        // With the focus inside, the root's own handler takes it (below).
        if (event.key !== 'Escape' || !shown || root.contains(/** @type {Node | null} */ (event.target))) return;
        event.preventDefault();
        instance.close();
    };

    /** @type {TooltipInstance} */
    const instance = {
        element: root,
        content,

        get isOpen() {
            return shown;
        },

        open() {
            clearTimeout(timer);
            if (!content || shown) return;

            shown = true;
            cancelLeave();
            place();
            content.removeAttribute('hidden');
            topLayer(content, true);
            enter([content], [content]);
            window.addEventListener('keydown', onWindowKeydown, true);
        },

        close({ delay = 0 } = {}) {
            clearTimeout(timer);
            const hide = () => {
                if (!content || !shown) return;
                shown = false;
                window.removeEventListener('keydown', onWindowKeydown, true);
                cancelLeave = leave([content], [content], () => topLayer(content, false));
            };
            if (delay > 0) timer = setTimeout(hide, delay);
            else hide();
        },

        destroy() {
            clearTimeout(timer);
            cancelLeave();
            window.removeEventListener('keydown', onWindowKeydown, true);
            stop.splice(0).forEach((undo) => undo());
            if (content && shown) topLayer(content, false);
            shown = false;
        },
    };

    /** @type {(() => void)[]} */
    const stop = [];
    /** @param {string} type @param {(event: any) => void} handler */
    const on = (type, handler) => {
        root.addEventListener(type, handler);
        stop.push(() => root.removeEventListener(type, handler));
    };

    // The bubble is inside the root in the DOM: the pointer on it still counts as on the element.
    on('mouseenter', () => instance.open());
    on('mouseleave', () => instance.close({ delay: HIDE_DELAY }));
    on('focusin', () => instance.open());
    on('focusout', () => instance.close());
    on('keydown', (/** @type {KeyboardEvent} */ event) => {
        if (event.key !== 'Escape' || !shown) return;
        event.stopPropagation();
        instance.close();
    });

    return instance;
}

/** @type {PluginDefinition} */
export const tooltipPlugin = {
    attribute: ATTRIBUTE,
    mount: createTooltip,
};
