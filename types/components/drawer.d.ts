import type { AlpineLike } from '../core/types.js';
/**
 * The scope of a drawer root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export declare function drawerScope(root: HTMLElement, Alpine: AlpineLike): {
    scope: {
        openPanel(): void;
        close(): void;
        register(): void;
        syncDock(): void;
        followLink(): void;
        /** @returns {HTMLElement[]} */
        focusableElements(): HTMLElement[];
        rendered: boolean;
        entered: boolean;
        opened: boolean;
        model: boolean;
        overlay: boolean;
    };
    stop(): void;
};
/**
 * Alpine.data('acunDrawer'): deprecated, see above.
 *
 * @param {{ name?: string, docked?: boolean, lockScroll?: boolean }} [options]
 */
export declare function drawer({ name, docked, lockScroll }?: {
    name?: string;
    docked?: boolean;
    lockScroll?: boolean;
}): {
    rendered: boolean;
    entered: boolean;
    opened: boolean;
    model: boolean;
    overlay: boolean;
    /** @this {AlpineMagics & Record<string, any>} */
    init(this: AlpineMagics & Record<string, any>): void;
    destroy(): void;
    openPanel(): void;
    register(): void;
    syncDock(): void;
    close(): void;
    followLink(): void;
    /** @returns {HTMLElement[]} */
    focusableElements(): HTMLElement[];
};
export type AlpineMagics = {
    $el: HTMLElement;
    $watch: (property: string, callback: (value: any) => void) => void;
    $nextTick: (callback: () => void) => void;
};
/** @typedef {{ $el: HTMLElement, $watch: (property: string, callback: (value: any) => void) => void, $nextTick: (callback: () => void) => void }} AlpineMagics */
