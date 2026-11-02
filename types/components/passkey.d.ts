/** The browser can create and use passkeys. */
export declare const passkeysSupported: () => boolean;
/**
 * The rule of Acun\Ui\Support\Url (and of acun:toast-container's links) for the redirect address:
 * http, https, mailto, tel and relative addresses; javascript:, data:… are not, and a
 * protocol-relative //host one only on this origin. The address, or null.
 */
export declare function safeUrl(url: any): string | null;
/** PublicKeyCredentialCreationOptions from the server's JSON (the browser's own parser when it has one). */
export declare function creationOptions(json: any): any;
/** PublicKeyCredentialRequestOptions from the server's JSON. */
export declare function requestOptions(json: any): any;
/** The credential as WebAuthn JSON for the server (credential.toJSON() where the browser has it). */
export declare function credentialJson(credential: any): any;
/**
 * The whole exchange of one button press: the options, the browser's window, the answer to the
 * server. Returns the server's answer; throws what failed (a cancelled window: NotAllowedError or
 * AbortError). wire: the Livewire component around the button ($wire), for the method steps.
 */
export declare function runPasskey({ mode, optionsUrl, actionUrl, optionsMethod, actionMethod, optionsGet, nameField, }?: {
    actionMethod?: null | undefined;
    actionUrl?: null | undefined;
    mode?: string | undefined;
    nameField?: null | undefined;
    optionsGet?: boolean | undefined;
    optionsMethod?: null | undefined;
    optionsUrl?: null | undefined;
}, wire?: null): Promise<any>;
/**
 * After the exchange: the events from element, a safe redirect address followed (URL mode), and the
 * message to show ('' for a cancelled window).
 *
 * @param {object} config
 * @param {HTMLElement} element
 * @param {any} [wire]
 * @returns {Promise<string>}
 */
export declare function pressPasskey(config: object, element: HTMLElement, wire?: any): Promise<string>;
/** Deprecated (3.0): the Alpine data of the 2.x markup (x-data="acunPasskey(…)"); the plugin runs the button now. */
export declare function passkey(config?: {}): {
    supported: boolean;
    busy: boolean;
    error: string;
    init(): void;
    run(): Promise<void>;
};
