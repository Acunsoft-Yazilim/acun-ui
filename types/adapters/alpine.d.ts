import type { AlpineLike } from '../core/types.js';
export type ScopeFactory = (element: HTMLElement, Alpine: AlpineLike) => {
    scope: object;
    stop: () => void;
};
/**
 * Gives every element of kind an Alpine scope (its content's expressions see it).
 *
 * @param {string} kind
 * @param {string} selector
 * @param {ScopeFactory} create
 */
export declare function scope(kind: string, selector: string, create: ScopeFactory): void;
/**
 * Called once by the runtime, before Alpine starts.
 *
 * @param {AlpineLike} Alpine
 */
export declare function connectAlpine(Alpine: AlpineLike): void;
/**
 * Calls back with true / false as the plugin on element opens and closes (its acun:{kind}:opened
 * and :closed events). Returns the function that stops.
 *
 * @param {Element} element
 * @param {'modal' | 'drawer' | 'dropdown' | 'popover'} kind
 * @param {(open: boolean) => void} callback
 * @returns {() => void}
 */
export declare function followState(element: Element, kind: 'modal' | 'drawer' | 'dropdown' | 'popover', callback: (open: boolean) => void): () => void;
