// acun:copy-button, in plain JavaScript (no Alpine). Markup:
//
//     <span class="group/copy contents" data-acun-copy data-state="idle" data-timeout="2000" data-copied-label="Copied" data-failed-label="Could not copy">
//         <button type="button" data-copy-text="TR33 0006…">…the idle / copied / failed icon and text, shown by group-data-[state=…]/copy: classes…</button>
//         <span class="sr-only" aria-live="polite" data-slot="copy-status"></span>
//     </span>
//
// A click copies data-copy-text, or the value (a field) or the text of the element data-copy-target names, read at
// click time (a Livewire re-render that changes them is picked up). The Clipboard API when the page has it, else a
// selected textarea and execCommand (plain http, some iframes). The root's data-state shows copied or failed for
// data-timeout ms, and a screen reader hears it (the status line). After a copy the button sends a bubbling
// 'copied' event ({ text }).

/** @import { PluginDefinition } from '../core/plugins.js' */

const ATTRIBUTE = 'data-acun-copy';

/** @param {HTMLElement} button */
function sourceOf(button) {
    if (button.dataset.copyText !== undefined) return button.dataset.copyText;
    try {
        const element = button.dataset.copyTarget ? document.querySelector(button.dataset.copyTarget) : null;
        if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement) return element.value;
        return element ? (element.textContent ?? '').trim() : null;
    } catch {
        return null;
    }
}

/**
 * Without the Clipboard API: a selected textarea beside the button (a focus trap around it keeps the focus) and execCommand.
 *
 * @param {string} text
 * @param {HTMLElement} button
 */
function copyWithSelection(text, button) {
    const area = document.createElement('textarea');
    area.value = text;
    area.readOnly = true;
    area.tabIndex = -1;
    area.setAttribute('aria-hidden', 'true');
    area.style.cssText = 'position: fixed; top: 0; left: 0; width: 1px; height: 1px; padding: 0; border: 0; opacity: 0;';
    button.after(area);
    area.select();
    area.setSelectionRange(0, text.length);
    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }
    area.remove();
    button.focus();
    return copied;
}

/**
 * @param {HTMLElement} root
 */
function createCopy(root) {
    /** @type {ReturnType<typeof setTimeout> | undefined} */
    let timer;
    const status = root.querySelector('[data-slot="copy-status"]');

    /** @param {'idle' | 'copied' | 'failed'} state */
    const show = (state) => {
        root.dataset.state = state;
        if (status) status.textContent = state === 'copied' ? (root.dataset.copiedLabel ?? '') : state === 'failed' ? (root.dataset.failedLabel ?? '') : '';
    };

    const onClick = async (/** @type {MouseEvent} */ event) => {
        const button = /** @type {HTMLElement | null} */ (root.querySelector('button'));
        if (!button || !button.contains(/** @type {Node | null} */ (event.target))) return;

        const text = sourceOf(button);
        let copied = false;
        if (text !== null) {
            try {
                if (!window.isSecureContext || !navigator.clipboard) throw new Error('Clipboard API unavailable');
                await navigator.clipboard.writeText(text);
                copied = true;
            } catch {
                copied = copyWithSelection(text, button);
            }
        }
        show(copied ? 'copied' : 'failed');
        clearTimeout(timer);
        timer = setTimeout(() => show('idle'), Number(root.dataset.timeout) || 2000);
        if (copied) button.dispatchEvent(new CustomEvent('copied', { detail: { text }, bubbles: true }));
    };
    root.addEventListener('click', onClick);

    return {
        element: root,
        destroy() {
            clearTimeout(timer);
            root.removeEventListener('click', onClick);
        },
    };
}

/** @type {PluginDefinition} */
export const copyPlugin = {
    attribute: ATTRIBUTE,
    mount: createCopy,
};
