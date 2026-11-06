// The browser's own validation bubbles ("Please fill out this field.") in the page's language, for the kit's controls
// ([data-acun-control]). The messages come from acun:layout.head (validation in the runtime settings), written with the
// application's translations; a page without them, or a field whose application set its own message
// (setCustomValidity), is left to the browser. The messages of a form go when any of its fields changes (a radio group
// is fixed by choosing another radio; a field filled by code or a hidden field behind an editor counts through its
// form) and before the form is sent again (its submit button, Enter in a field), so a stale one never blocks it. The
// browser reads the message when it shows the bubble, so it cannot simply be taken away right after.

import { readConfig } from '../core/config.js';

// The order the browser reports them in: the first one that holds is the message.
const FLAGS = /** @type {const} */ (['valueMissing', 'typeMismatch', 'badInput', 'patternMismatch', 'tooLong', 'tooShort', 'rangeUnderflow', 'rangeOverflow', 'stepMismatch']);

/** @type {Set<HTMLInputElement>} the fields that carry one of these messages */
const ours = new Set();

/** Takes the messages off the fields of the form (or the element) an event came from. */
function clearAround(/** @type {EventTarget | null} */ target) {
    if (ours.size === 0 || !(target instanceof Element)) return;
    const form = /** @type {HTMLFormElement | null} */ (/** @type {any} */ (target).form ?? target.closest('form'));
    for (const field of [...ours]) {
        if (field.isConnected && field !== target && !(form && field.form === form) && !target.contains(field)) continue;
        ours.delete(field);
        field.setCustomValidity('');
    }
}

/**
 * @param {HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement} field
 * @param {Record<string, string>} messages
 */
function messageFor(field, messages) {
    const flag = FLAGS.find((name) => field.validity[name]);
    if (!flag) return '';
    const type = field.type;
    // A box to tick and a choice to make read differently from a box to fill; an e-mail or web address from other types.
    const key = flag === 'valueMissing' && (type === 'checkbox' || type === 'radio' || field.localName === 'select')
        ? (type === 'checkbox' ? 'valueMissingCheckbox' : 'valueMissingChoice')
        : flag === 'typeMismatch' && (type === 'email' || type === 'url') ? `typeMismatch${type === 'email' ? 'Email' : 'Url'}` : flag;
    const text = (messages[key] ?? messages[flag] ?? '')
        .replace(':minlength', String(field instanceof HTMLSelectElement ? '' : field.minLength))
        .replace(':maxlength', String(field instanceof HTMLSelectElement ? '' : field.maxLength))
        .replace(':min', 'min' in field ? field.min : '')
        .replace(':max', 'max' in field ? field.max : '');

    // A pattern's title says what is asked for, as the browser shows it.
    return flag === 'patternMismatch' && field.title ? `${text} ${field.title}` : text;
}

/** @param {Document} [doc] */
export function startValidationMessages(doc = document) {
    doc.addEventListener('invalid', (event) => {
        const field = /** @type {HTMLInputElement} */ (event.target);
        if (!field?.matches?.('[data-acun-control]') || !field.validity) return;
        // The application's own message stays.
        if (field.validity.customError && !ours.has(field)) return;
        const messages = readConfig(doc)?.validation;
        if (!messages) return;

        field.setCustomValidity('');
        const text = messageFor(field, messages);
        if (!text) return;
        field.setCustomValidity(text);
        ours.add(field);
    }, true);

    const clear = (/** @type {Event} */ event) => clearAround(event.target);
    doc.addEventListener('input', clear, true);
    doc.addEventListener('change', clear, true);
    // Before the browser checks the form again: its submit button, or Enter in one of its fields.
    doc.addEventListener('click', (event) => {
        const button = /** @type {Element | null} */ (event.target instanceof Element ? event.target.closest('button, input[type="submit"], input[type="image"]') : null);
        if (button && /** @type {HTMLButtonElement} */ (button).type === 'submit') clearAround(button);
    }, true);
    doc.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target instanceof HTMLInputElement) clearAround(event.target);
    }, true);
}
