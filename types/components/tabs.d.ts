import type { AlpineLike } from '../core/types.js';
/**
 * The scope of a tabs root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function tabsScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        tab: string | null;
        model: string | null;
        /** @param {string} wanted */
        selected(wanted: string): boolean;
    };
    stop(): void;
};
