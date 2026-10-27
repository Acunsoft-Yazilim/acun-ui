import type { AlpineLike } from '../core/types.js';
/**
 * The scope of an accordion root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function accordionScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        open: string[];
        seen: string[];
        /** @param {string} name */
        isOpen(name: string): boolean;
        /** @param {string} name */
        wasOpened(name: string): boolean;
        /** @param {string} name */
        toggle(name: string): void;
    };
    stop: () => void;
};
/**
 * The scope of a collapse root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function collapseScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        open: boolean;
    };
    stop(): void;
};
