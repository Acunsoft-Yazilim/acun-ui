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
export declare function passkey({ mode, optionsUrl, actionUrl, optionsMethod, actionMethod, optionsGet, nameField, }?: {
    actionMethod?: null | undefined;
    actionUrl?: null | undefined;
    mode?: string | undefined;
    nameField?: null | undefined;
    optionsGet?: boolean | undefined;
    optionsMethod?: null | undefined;
    optionsUrl?: null | undefined;
}): {
    supported: boolean;
    busy: boolean;
    error: string;
    init(): void;
    run(): Promise<void>;
    options(): any;
    send(credential: any): any;
};
