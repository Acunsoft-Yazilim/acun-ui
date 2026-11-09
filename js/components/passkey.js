// @ts-nocheck: not type-checked yet; typed progressively (ARCHITECTURE.md, ADR-008).
//
// acun:passkey-button's exchange, in a chunk of its own: js/plugins/passkey.js loads it on the first
// press (the 2.x markup's Alpine data, acunPasskey, is here too).
//
// Adds a passkey to the signed-in account (mode: register) or signs in with one (mode: login).
// The kit runs only the browser's part (navigator.credentials). The two server steps are the
// application's, whatever WebAuthn library it uses: the options, as WebAuthn JSON (base64url
// fields, optionally under publicKey), and the check of the answer. URLs carry them (fetch,
// JSON, the CSRF token of <meta name="csrf-token">, sent only to this origin) or methods of the
// Livewire component around the button ($wire.call: the options method returns the JSON, the
// action method receives the credential and the name). A redirect address in the sign-in answer
// is followed only when it is safe (Acun\Ui\Support\Url's rule: no javascript:, no other site's //host).

import { dispatch } from '../core/events.js';
import { csrfHeader, isSameOrigin } from '../core/url.js';

/** The browser can create and use passkeys. */
export const passkeysSupported = () => typeof window !== 'undefined'
    && typeof window.PublicKeyCredential === 'function'
    && typeof navigator !== 'undefined'
    && !!navigator.credentials;

const toBuffer = (value) => {
    const base64 = String(value).replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=');

    return Uint8Array.from(atob(padded), (character) => character.charCodeAt(0)).buffer;
};

const toBase64url = (buffer) => {
    let binary = '';
    new Uint8Array(buffer).forEach((byte) => { binary += String.fromCharCode(byte); });

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
};

/**
 * The rule of Acun\Ui\Support\Url (and of acun:toast-container's links) for the redirect address:
 * http, https, mailto, tel and relative addresses; javascript:, data:… are not, and a
 * protocol-relative //host one only on this origin. The address, or null.
 */
export function safeUrl(url) {
    if (typeof url !== 'string' || url === '') return null;

    // Browsers drop tabs and line breaks anywhere and control characters/spaces at the start.
    const normalized = url.replace(/[\t\n\r]/g, '').replace(/^[\x00-\x20]+/, '');
    // They read a backslash as a slash too: /\host and \\host are //host.
    if (/^[\\/]{2}/.test(normalized)) return isSameOrigin(normalized) ? url : null;

    const scheme = normalized.match(/^([a-z][a-z0-9+.-]*):/i)?.[1].toLowerCase();

    return !scheme || ['http', 'https', 'mailto', 'tel'].includes(scheme) ? url : null;
}

const withIds = (list) => (list ?? []).map((item) => ({ ...item, id: toBuffer(item.id) }));

/** PublicKeyCredentialCreationOptions from the server's JSON (the browser's own parser when it has one). */
export function creationOptions(json) {
    const options = json?.publicKey ?? json;
    if (typeof window.PublicKeyCredential?.parseCreationOptionsFromJSON === 'function') {
        return window.PublicKeyCredential.parseCreationOptionsFromJSON(options);
    }

    return {
        ...options,
        challenge: toBuffer(options.challenge),
        user: { ...options.user, id: toBuffer(options.user.id) },
        excludeCredentials: withIds(options.excludeCredentials),
    };
}

/** PublicKeyCredentialRequestOptions from the server's JSON. */
export function requestOptions(json) {
    const options = json?.publicKey ?? json;
    if (typeof window.PublicKeyCredential?.parseRequestOptionsFromJSON === 'function') {
        return window.PublicKeyCredential.parseRequestOptionsFromJSON(options);
    }

    return { ...options, challenge: toBuffer(options.challenge), allowCredentials: withIds(options.allowCredentials) };
}

/** The credential as WebAuthn JSON for the server (credential.toJSON() where the browser has it). */
export function credentialJson(credential) {
    if (typeof credential.toJSON === 'function') return credential.toJSON();

    const answer = credential.response;
    const response = { clientDataJSON: toBase64url(answer.clientDataJSON) };

    if (answer.attestationObject) {
        response.attestationObject = toBase64url(answer.attestationObject);
        response.transports = answer.getTransports?.() ?? [];
    }

    if (answer.authenticatorData) {
        response.authenticatorData = toBase64url(answer.authenticatorData);
        response.signature = toBase64url(answer.signature);
        response.userHandle = answer.userHandle ? toBase64url(answer.userHandle) : null;
    }

    return {
        id: credential.id,
        rawId: toBase64url(credential.rawId),
        type: credential.type,
        authenticatorAttachment: credential.authenticatorAttachment ?? null,
        clientExtensionResults: credential.getClientExtensionResults?.() ?? {},
        response,
    };
}

const jsonHeaders = (url) => ({
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...csrfHeader(url),
});

async function requestJson(url, { method = 'POST', body } = {}) {
    const response = await fetch(url, {
        method,
        headers: jsonHeaders(url),
        credentials: 'same-origin',
        body: method === 'GET' ? undefined : JSON.stringify(body ?? {}),
    });

    if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`);
        error.name = 'HttpError';
        error.status = response.status;
        error.body = await response.json().catch(() => null);
        throw error;
    }

    return response.headers.get('content-type')?.includes('json') ? response.json() : {};
}

// The user closed the browser's passkey window or it timed out: no message, they can try again.
const CANCELLED = ['NotAllowedError', 'AbortError'];

/**
 * The whole exchange of one button press: the options, the browser's window, the answer to the
 * server. Returns the server's answer; throws what failed (a cancelled window: NotAllowedError or
 * AbortError). wire: the Livewire component around the button ($wire), for the method steps.
 */
export async function runPasskey({
    mode = 'login',
    optionsUrl = null,
    actionUrl = null,
    optionsMethod = null,
    actionMethod = null,
    optionsGet = false,
    nameField = null,
} = {}, wire = null) {
    const json = optionsMethod ? await wire.call(optionsMethod) : await requestJson(optionsUrl, { method: optionsGet ? 'GET' : 'POST' });
    const credential = mode === 'register'
        ? await navigator.credentials.create({ publicKey: creationOptions(json) })
        : await navigator.credentials.get({ publicKey: requestOptions(json) });
    const answer = credentialJson(credential);
    const name = nameField ? document.querySelector(nameField)?.value?.trim() || null : null;

    return actionMethod ? wire.call(actionMethod, answer, name) : requestJson(actionUrl, { body: name === null ? answer : { ...answer, name } });
}

/**
 * After the exchange: the events from element, a safe redirect address followed (URL mode), and the
 * message to show ('' for a cancelled window).
 *
 * @param {object} config
 * @param {HTMLElement} element
 * @param {any} [wire]
 * @returns {Promise<string>}
 */
export async function pressPasskey(config, element, wire = null) {
    const mode = config.mode ?? 'login';
    try {
        const result = await runPasskey(config, wire);
        dispatch('acun:passkey:done', { mode, result: result ?? null }, element);
        // Only to this site: the server's answer may not send the visitor elsewhere.
        const redirect = config.actionUrl && isSameOrigin(result?.redirect) ? safeUrl(result?.redirect) : null;
        if (redirect) window.location.assign(redirect);

        return '';
    } catch (error) {
        const name = error?.name ?? 'Error';
        dispatch('acun:passkey:failed', { mode, error: name, message: error?.message ?? '', status: error?.status ?? null }, element);

        return CANCELLED.includes(name) ? '' : error?.body?.message ?? element.dataset.errorText ?? String(error?.message ?? name);
    }
}

/** Deprecated (3.0): the Alpine data of the 2.x markup (x-data="acunPasskey(…)"); the plugin runs the button now. */
export function passkey(config = {}) {
    return {
        supported: true,
        busy: false,
        error: '',

        init() {
            this.supported = passkeysSupported();
        },

        async run() {
            if (this.busy || !this.supported) return;

            this.busy = true;
            this.error = '';
            this.error = await pressPasskey(config, this.$el, this.$wire);
            this.busy = false;
        },
    };
}
