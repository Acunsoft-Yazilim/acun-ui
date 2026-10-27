import type { AlpineLike } from '../core/types.js';
/**
 * The scope of a dropdown root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function dropdownScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        open: boolean;
        /** @param {1 | -1 | 'first' | 'last'} [to] */
        openMenu(to?: 1 | -1 | 'first' | 'last'): void;
        /** @param {boolean} [refocus] */
        closeMenu(refocus?: boolean): void;
        /** @param {1 | -1 | 'first' | 'last'} to */
        focusItem(to: 1 | -1 | 'first' | 'last'): void;
        /** @returns {HTMLElement[]} */
        items(): HTMLElement[];
    };
    stop(): void;
};
