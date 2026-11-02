// acun:passkey-button, in plain JavaScript (no Alpine). Markup (the component renders it):
//
//     <div data-acun-passkey data-mode="login" data-options-url="/passkeys/login/options" data-action-url="/passkeys/login"
//          data-error-text="The passkey could not be used. Please try again.">
//         <button type="button" data-slot="passkey-button" aria-busy="false">Sign in with a passkey</button>
//         <p data-slot="passkey-unsupported" hidden>This browser does not support passkeys.</p>
//         <p data-slot="passkey-error" role="alert" hidden></p>
//     </div>
//
// The two steps are URLs (data-options-url, data-action-url; data-options-get for a GET) or methods of the
// Livewire component around it (data-options-method, data-action-method); data-name-field: a field whose
// value is the new passkey's name. The exchange (WebAuthn JSON, fetch, the redirect rule) loads on the
// first press, in a chunk of its own (components/passkey.js). A browser without passkeys gets a disabled
// button and the note. While it works the button stays focusable (aria-disabled; a press does nothing).
// Events from the root: acun:passkey:done ({ mode, result }) and acun:passkey:failed ({ mode, error, message }).

import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

const ATTRIBUTE = 'data-acun-passkey';

/**
 * @typedef {object} PasskeyInstance
 * @property {HTMLElement} element
 * @property {boolean} supported
 * @property {() => Promise<void>} run  presses the button
 * @property {() => void} destroy
 */

/** The browser can create and use passkeys. */
const supported = () => typeof window.PublicKeyCredential === 'function' && !!navigator.credentials;

/**
 * @param {HTMLElement} root
 * @returns {PasskeyInstance}
 */
function createPasskey(root) {
    const button = /** @type {HTMLButtonElement | null} */ (ownPart(root, ATTRIBUTE, 'passkey-button'));
    const note = ownPart(root, ATTRIBUTE, 'passkey-unsupported');
    const error = ownPart(root, ATTRIBUTE, 'passkey-error');
    const data = root.dataset;
    const config = {
        mode: data.mode === 'register' ? 'register' : 'login',
        optionsUrl: data.optionsUrl ?? null,
        actionUrl: data.actionUrl ?? null,
        optionsMethod: data.optionsMethod ?? null,
        actionMethod: data.actionMethod ?? null,
        optionsGet: data.optionsGet !== undefined,
        nameField: data.nameField ?? null,
    };
    const able = supported();
    let busy = false;

    if (button) {
        plugins.own(button, 'disabled', 'aria-disabled', 'aria-busy');
        button.disabled = !able;
    }
    if (note) plugins.own(note, 'hidden');
    note?.toggleAttribute('hidden', able);
    if (error) plugins.own(error, 'hidden');

    const showError = (/** @type {string} */ message) => {
        if (!error) return;
        error.textContent = message;
        error.toggleAttribute('hidden', message === '');
    };

    const run = async () => {
        if (busy || !able) return;
        busy = true;
        button?.setAttribute('aria-disabled', 'true');
        button?.setAttribute('aria-busy', 'true');
        showError('');
        try {
            const { pressPasskey } = await import('../components/passkey.js');
            // The method steps go through the Livewire component around the button.
            const id = root.closest('[wire\\:id]')?.getAttribute('wire:id');
            const livewire = /** @type {{ Livewire?: { find?: (id: string) => unknown } }} */ (/** @type {unknown} */ (window)).Livewire;
            showError(await pressPasskey(config, root, id ? livewire?.find?.(id) : null));
        } finally {
            busy = false;
            button?.removeAttribute('aria-disabled');
            button?.setAttribute('aria-busy', 'false');
        }
    };
    const onClick = () => void run();

    button?.addEventListener('click', onClick);

    return {
        element: root,
        get supported() {
            return able;
        },
        run,
        destroy() {
            button?.removeEventListener('click', onClick);
        },
    };
}

/** @type {PluginDefinition} */
export const passkeyPlugin = {
    attribute: ATTRIBUTE,
    mount: createPasskey,
};
