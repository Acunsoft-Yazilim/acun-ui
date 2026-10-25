import type { AlpineLike } from '../core/types.js';
import type { AlpineMagics } from './drawer.js';
/**
 * The scope of a modal root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function modalScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        show(): void;
        /** notifyServer: false when closed by an event (no close-action). */
        close(notifyServer?: boolean): void;
        requestClose(): void;
        /** @returns {HTMLElement[]} */
        focusables(): HTMLElement[];
        open: boolean;
        model: boolean;
    };
    stop(): void;
};
/**
 * Alpine.data('acunModal'): deprecated, see above.
 *
 * @param {{ name?: string, closeAction?: string | null, requestCloseEvent?: string | null }} [options]
 */
export declare function modal({ name, closeAction, requestCloseEvent }?: {
    name?: string;
    closeAction?: string | null;
    requestCloseEvent?: string | null;
}): {
    open: boolean;
    model: boolean;
    /** @this {AlpineMagics & Record<string, any>} */
    init(this: AlpineMagics & Record<string, any>): void;
    destroy(): void;
    show(): void;
    close(): void;
    requestClose(): void;
    /** @returns {HTMLElement[]} */
    focusables(): HTMLElement[];
};
