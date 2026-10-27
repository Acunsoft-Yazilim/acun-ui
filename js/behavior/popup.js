// A panel that opens from a trigger beside it: the dropdown's menu and the popover's bubble. Shared by
// plugins/dropdown.js and plugins/popover.js. The root holds a trigger part and a content part (data-slot);
// the trigger's first button or link gets aria-expanded and aria-controls (the content's id, made when it
// has none). A click on the trigger toggles the panel; a click or the focus outside the root closes it;
// Escape inside closes only the panel (not a dialog around it) and gives the focus back to the trigger,
// Escape elsewhere on the page closes it too. Open, the root and the content carry data-open and the
// content loses hidden (CSS transitions, core/transition.js); the content opens on its data-side and
// flips or shifts when the window has no room for it (behavior/floating.js). Events from the root:
// acun:{kind}:opened and acun:{kind}:closed ({ reason: 'user' | 'command' | 'removed' }).

import { dispatch } from '../core/events.js';
import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';
import { enter, leave } from '../core/transition.js';
import { follow } from './floating.js';

/** @typedef {'user' | 'command' | 'removed'} PopupReason */

/**
 * @typedef {object} Popup
 * @property {HTMLElement | null} trigger
 * @property {HTMLElement | null} content
 * @property {HTMLElement | null} control  the trigger's button or link
 * @property {() => boolean} isOpen
 * @property {() => boolean} open  opens (false when there is no content); true once open
 * @property {(options?: { refocus?: boolean }, reason?: PopupReason) => void} close
 * @property {() => void} toggle
 * @property {(target: EventTarget | null, type: string, handler: (event: any) => void) => void} on  a listener removed on destroy
 * @property {() => void} destroy
 */

const ids = new Map();
/** @param {string} kind */
const nextId = (kind) => {
    const id = (ids.get(kind) ?? 0) + 1;
    ids.set(kind, id);
    return `acun-${kind}-${id}`;
};

/**
 * @param {HTMLElement} root
 * @param {{ kind: 'dropdown' | 'popover', attribute: string }} options
 * @returns {Popup}
 */
export function createPopup(root, { kind, attribute }) {
    const trigger = ownPart(root, attribute, `${kind}-trigger`);
    const content = ownPart(root, attribute, `${kind}-content`);
    const control = /** @type {HTMLElement | null} */ (trigger?.querySelector('button, a[href]') ?? null);
    const preferred = content?.dataset.side === 'top' ? 'top' : 'bottom';

    if (content && !content.id) content.id = nextId(kind);
    if (control && content) {
        control.setAttribute('aria-controls', content.id);
        if (!control.hasAttribute('aria-expanded')) control.setAttribute('aria-expanded', 'false');
        plugins.own(control, 'aria-expanded');
    }
    if (content) plugins.own(content, 'hidden', 'data-open', 'data-side');
    plugins.own(root, 'data-open');

    let opened = false;
    let cancelLeave = () => {};
    let stopFollowing = () => {};
    /** @type {(() => void)[]} */
    const stop = [];

    /** @param {PopupReason} reason */
    const announce = (reason) => {
        control?.setAttribute('aria-expanded', opened ? 'true' : 'false');
        dispatch(`acun:${kind}:${opened ? 'opened' : 'closed'}`, opened ? {} : { reason }, root.isConnected ? root : window);
    };

    // While open: a click outside closes it, and so does Escape with the focus elsewhere on the page.
    const onDocumentClick = (/** @type {MouseEvent} */ event) => {
        if (!root.contains(/** @type {Node | null} */ (event.target))) popup.close({}, 'user');
    };
    const onDocumentKeydown = (/** @type {KeyboardEvent} */ event) => {
        if (event.key === 'Escape' && !root.contains(/** @type {Node | null} */ (event.target))) popup.close({}, 'user');
    };
    const listen = (/** @type {boolean} */ on) => {
        if (on) {
            document.addEventListener('click', onDocumentClick);
            document.addEventListener('keydown', onDocumentKeydown);
        } else {
            document.removeEventListener('click', onDocumentClick);
            document.removeEventListener('keydown', onDocumentKeydown);
        }
    };

    /** @type {Popup} */
    const popup = {
        trigger,
        content,
        control,

        isOpen: () => opened,

        open() {
            if (!content) return false;
            if (opened) return true;

            opened = true;
            cancelLeave();
            enter([content], [content, root]);
            stopFollowing = follow(content, trigger ?? root, preferred);
            listen(true);
            announce('user');
            return true;
        },

        close({ refocus = false } = {}, reason = 'command') {
            if (!opened || !content) return;

            opened = false;
            stopFollowing();
            listen(false);
            cancelLeave = leave([content], [content, root]);
            if (refocus) control?.focus();
            announce(reason);
        },

        toggle() {
            if (opened) popup.close({}, 'user');
            else popup.open();
        },

        on(target, type, handler) {
            if (!target) return;
            target.addEventListener(type, handler);
            stop.push(() => target.removeEventListener(type, handler));
        },

        destroy() {
            stop.splice(0).forEach((undo) => undo());
            stopFollowing();
            cancelLeave();
            listen(false);
            if (opened) {
                opened = false;
                announce('removed');
            }
        },
    };

    popup.on(trigger, 'click', () => popup.toggle());
    // Escape inside an open panel closes only the panel, not a dialog it sits in.
    popup.on(root, 'keydown', (/** @type {KeyboardEvent} */ event) => {
        if (event.key !== 'Escape' || !opened) return;
        event.stopPropagation();
        popup.close({ refocus: true }, 'user');
    });
    popup.on(root, 'focusout', (/** @type {FocusEvent} */ event) => {
        const next = /** @type {Node | null} */ (event.relatedTarget);
        if (opened && next && !root.contains(next)) popup.close({}, 'user');
    });

    return popup;
}
