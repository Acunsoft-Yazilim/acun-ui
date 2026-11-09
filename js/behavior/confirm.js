// AcunUI.confirm(): the kit's confirmation dialog (acun:confirmation-dialog's look: an alertdialog with a title, a
// question, Cancel and a confirm button) from JavaScript, as a promise of the answer. Packages built on the runtime
// use it instead of the browser's confirm(); they find it on window.AcunUI.
//
//     const yes = await AcunUI.confirm({ title: 'Delete the order?', text: 'This cannot be undone.', confirmLabel: 'Delete', tone: 'danger' });
//
// The dialog is the modal plugin's (focus, Tab, Escape, the scroll lock, the focus given back), built on the page for
// the question and removed after the answer. Confirm resolves true; Cancel, the X, Escape and the backdrop false.
// Questions asked while one is open wait their turn. Without a document (a server render) the answer is false.
// The defaults are English: a page in another language passes its texts (closeLabel names the X).

import { plugins } from '../core/plugins.js';

/** @import { ModalInstance } from '../plugins/modal.js' */

/**
 * @typedef {object} ConfirmRequest
 * @property {string} [title]         the dialog's name; default "Are you sure?"
 * @property {string} [text]          the question
 * @property {string} [confirmLabel]  default "Confirm"
 * @property {string} [cancelLabel]   default "Cancel"
 * @property {string} [closeLabel]    the X's name; default "Close"
 * @property {'default' | 'danger'} [tone]  the confirm button's look: the primary colour, or red for what destroys
 */

// The defaults; a page in another language passes its own (a Livewire view passes the translated texts).
const TEXTS = { title: 'Are you sure?', confirmLabel: 'Confirm', cancelLabel: 'Cancel', close: 'Close' };

// acun:button's classes (the white, primary and danger variants), as acun:confirmation-dialog prints them.
const BUTTON = 'inline-flex items-center justify-center rounded-sm border font-semibold transition-[color,background-color,border-color,box-shadow,transform] duration-180 ease-out cursor-pointer select-none active:not-disabled:translate-y-px focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 aria-disabled:pointer-events-none aria-disabled:opacity-60 motion-reduce:transition-none motion-reduce:active:translate-y-0 px-3.5 py-2 text-sm gap-2';
const TONES = {
    white: 'border-line-strong bg-field text-body hover:not-disabled:border-line-hover hover:not-disabled:bg-field-hover focus-visible:outline-focus',
    default: 'border-primary bg-primary text-on-primary hover:not-disabled:border-primary-hover hover:not-disabled:bg-primary-hover focus-visible:outline-focus',
    danger: 'border-danger bg-danger text-on-danger hover:not-disabled:border-danger-hover hover:not-disabled:bg-danger-hover focus-visible:outline-danger-ink',
};

let queue = Promise.resolve();
let count = 0;

/**
 * @param {string} tag
 * @param {Record<string, string>} attributes
 * @param {(Node | string)[]} [children]
 */
function element(tag, attributes, children = []) {
    const node = document.createElement(tag);
    for (const [name, value] of Object.entries(attributes)) node.setAttribute(name, value);
    node.append(...children);

    return node;
}

/** @param {ConfirmRequest} request */
function build(request) {
    const texts = TEXTS;
    const id = `acun-confirm-${++count}`;
    const tone = request.tone === 'danger' ? 'danger' : 'default';
    const close = element('button', { type: 'button', 'data-acun-close': '', 'aria-label': request.closeLabel || texts.close, class: 'cursor-pointer text-muted hover:text-danger-ink' });
    close.innerHTML = '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>';
    const confirm = element('button', { type: 'button', 'data-acun-confirm': '', class: `${BUTTON} ${TONES[tone]}` }, [request.confirmLabel || texts.confirmLabel]);
    const panel = element('div', { 'data-slot': 'modal-panel', role: 'alertdialog', 'aria-modal': 'true', 'aria-labelledby': `${id}-title`, tabindex: '-1', class: 'relative z-10 flex max-h-[85vh] w-full flex-col sm:max-w-sm overflow-hidden rounded-sm bg-surface shadow-xl scale-95 opacity-0 transition-[opacity,scale] duration-150 ease-in data-open:scale-100 data-open:opacity-100 data-open:duration-200 data-open:ease-out motion-reduce:transition-none' }, [
        element('div', { class: 'flex flex-none items-center justify-between border-b border-line px-5 py-4' }, [
            element('h3', { id: `${id}-title`, class: 'text-base font-semibold text-ink' }, [request.title || texts.title]),
            close,
        ]),
        ...(request.text ? [element('div', { class: 'flex-1 overflow-y-auto p-5' }, [element('p', { id: `${id}-description`, class: 'text-sm text-dim' }, [request.text])])] : []),
        element('div', { class: 'flex flex-none items-center justify-end gap-2 border-t border-line bg-sunken/50 px-5 py-4' }, [
            element('button', { type: 'button', 'data-acun-close-now': '', class: `${BUTTON} ${TONES.white}` }, [request.cancelLabel || texts.cancelLabel]),
            confirm,
        ]),
    ]);
    if (request.text) panel.setAttribute('aria-describedby', `${id}-description`);

    const root = element('div', { 'data-acun-modal': id, hidden: '', class: 'pointer-events-none fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 data-open:pointer-events-auto' }, [
        element('div', { 'data-slot': 'modal-backdrop', 'data-acun-close': '', 'aria-hidden': 'true', class: 'ui-backdrop fixed inset-0 opacity-0 transition-opacity duration-150 ease-in data-open:opacity-100 data-open:ease-out motion-reduce:transition-none' }),
        panel,
    ]);

    return { root, confirm };
}

/**
 * Asks with the kit's confirmation dialog; resolves true when the user confirms.
 *
 * @param {ConfirmRequest} [request]
 * @returns {Promise<boolean>}
 */
export function confirm(request = {}) {
    if (typeof document === 'undefined') return Promise.resolve(false);

    const ask = () => new Promise((resolve) => {
        const { root, confirm: button } = build(request);
        let answer = false;
        document.body.append(root);
        const modal = /** @type {ModalInstance | null} */ (/** @type {unknown} */ (plugins.mount(root, 'modal')));
        if (!modal) {
            root.remove();
            resolve(false);
            return;
        }
        const id = root.getAttribute('data-acun-modal');
        let done = false;
        const finish = (/** @type {boolean} */ value, /** @type {number} */ after) => {
            if (done) return;
            done = true;
            window.removeEventListener('acun:modal:closed', onRemoved);
            document.removeEventListener('livewire:navigating', onLeave);
            // After the closing fade (the modal hides its root when it ends).
            setTimeout(() => {
                plugins.unmount(root);
                root.remove();
            }, after);
            resolve(value);
        };
        // Taken out of the page while open (wire:navigate swapped the body, a back step): the modal tells the window;
        // the answer is no, and the next confirm() is not kept waiting.
        const onRemoved = (/** @type {Event} */ event) => {
            const detail = /** @type {CustomEvent} */ (event).detail;
            if (detail?.name === id && detail?.reason === 'removed') finish(false, 0);
        };
        // Leaving the page with wire:navigate: no answer, and no dialog kept in the snapshot of the page left.
        const onLeave = () => {
            modal.close();
            finish(false, 0);
        };
        button.addEventListener('click', () => {
            answer = true;
            modal.close();
        });
        root.addEventListener('acun:modal:closed', () => finish(answer, 300), { once: true });
        window.addEventListener('acun:modal:closed', onRemoved);
        document.addEventListener('livewire:navigating', onLeave);
        modal.open();
    });

    const answer = queue.then(ask);
    queue = answer.then(() => undefined, () => undefined);

    return /** @type {Promise<boolean>} */ (answer);
}
