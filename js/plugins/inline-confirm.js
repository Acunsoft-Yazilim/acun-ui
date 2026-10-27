// acun:inline-confirm, a destructive action that asks in place, in plain JavaScript (no Alpine). Markup:
//
//     <span data-acun-inline-confirm class="inline-flex items-center gap-2">
//         <button type="button" data-slot="inline-confirm-ask">Delete</button>
//         <span data-slot="inline-confirm-question" role="group" aria-label="Are you sure?" hidden>
//             Are you sure? <button type="button" data-slot="inline-confirm-confirm" wire:click="delete(5)">Delete</button>
//             <button type="button" data-slot="inline-confirm-cancel">Cancel</button>
//         </span>
//     </span>
//
// The first button turns into the question (the focus moves to the confirm button); only the confirm button runs
// the action. The confirm button, the cancel button, Escape (only the question, not a dialog around it) or a click
// elsewhere put the first button back; the focus comes back to it when it was in the question, so it is never lost.

import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

const ATTRIBUTE = 'data-acun-inline-confirm';

/**
 * @param {HTMLElement} root
 */
function createInlineConfirm(root) {
    const ask = ownPart(root, ATTRIBUTE, 'inline-confirm-ask');
    const question = ownPart(root, ATTRIBUTE, 'inline-confirm-question');
    const confirm = ownPart(root, ATTRIBUTE, 'inline-confirm-confirm');
    const cancel = ownPart(root, ATTRIBUTE, 'inline-confirm-cancel');
    if (ask) plugins.own(ask, 'hidden');
    if (question) plugins.own(question, 'hidden');

    const asking = () => !!question && !question.hidden;

    /** @param {boolean} on */
    const set = (on) => {
        if (!ask || !question) return;
        const hadFocus = question.contains(document.activeElement);
        question.hidden = !on;
        ask.hidden = on;
        if (on) confirm?.focus();
        else if (hadFocus || document.activeElement === document.body) ask.focus();
        if (on) document.addEventListener('click', onOutside, true);
        else document.removeEventListener('click', onOutside, true);
    };

    const onOutside = (/** @type {MouseEvent} */ event) => {
        if (!root.contains(/** @type {Node | null} */ (event.target))) set(false);
    };
    const onAsk = () => set(true);
    // The action itself is the confirm button's own (wire:click…); the question then goes.
    const onConfirm = () => queueMicrotask(() => set(false));
    const onCancel = () => set(false);
    const onKeydown = (/** @type {KeyboardEvent} */ event) => {
        if (event.key !== 'Escape' || !asking()) return;
        event.stopPropagation();
        set(false);
    };

    ask?.addEventListener('click', onAsk);
    confirm?.addEventListener('click', onConfirm);
    cancel?.addEventListener('click', onCancel);
    root.addEventListener('keydown', onKeydown);

    return {
        element: root,
        destroy() {
            ask?.removeEventListener('click', onAsk);
            confirm?.removeEventListener('click', onConfirm);
            cancel?.removeEventListener('click', onCancel);
            root.removeEventListener('keydown', onKeydown);
            document.removeEventListener('click', onOutside, true);
        },
    };
}

/** @type {PluginDefinition} */
export const inlineConfirmPlugin = {
    attribute: ATTRIBUTE,
    mount: createInlineConfirm,
};
