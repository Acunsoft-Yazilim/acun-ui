import type { PluginDefinition } from '../core/plugins.js';
export type ToastData = {
    id: number;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    link: string | null;
    linkLabel: string | null;
    /**
     * '' downloads with the server's name, a text names the file, null: a plain link
     */
    download: string | null;
};
export type ToastsInstance = {
    element: HTMLElement;
    /**
     * the toasts on screen
     */
    toasts: ToastData[];
    timers: Map<number, {
        left: number;
        since?: number;
        handle: ReturnType<typeof setTimeout> | null;
    }>;
    push: (toast: Record<string, unknown>) => void;
    dismiss: (id: number) => void;
    pause: (id: number) => void;
    resume: (id: number) => void;
    destroy: () => void;
};
/**
 * The types of Acun\Ui\Support\Toast: the palette's names work too (danger is error), in any case; anything else is info.
 *
 * @param {unknown} type
 * @returns {ToastData['type']}
 */
export declare function toastType(type: unknown): ToastData['type'];
/**
 * The rule of Acun\Ui\Support\Url: only http, https, mailto, tel and relative links; a protocol-relative //host
 * link only to this site.
 *
 * @param {unknown} url
 * @returns {string | null}
 */
export declare function safeLink(url: unknown): string | null;
/** @type {PluginDefinition} */
export declare const toastsPlugin: PluginDefinition;
