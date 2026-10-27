import type { AlpineLike } from '../core/types.js';
/**
 * The scope of a popover root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function popoverScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        open: boolean;
        /** @param {boolean} [refocus] */
        closePopover(refocus?: boolean): void;
    };
    stop(): void;
};
