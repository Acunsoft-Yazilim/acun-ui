import type { AlpineLike } from './types.js';
export declare const components: {
    /**
     * load(): imports the module and registers what it needs. defer (default true): Alpine
     * waits for it before starting the element (its x-data is in the module); false for a
     * module that only has to be on the page (event delegation, no Alpine data).
     *
     * @param {string} name
     * @param {() => unknown} load
     * @param {{ defer?: boolean }} [options]
     */
    define(name: string, load: () => unknown, { defer }?: {
        defer?: boolean;
    }): void;
    /**
     * Loads a component's module ahead of its element (once); resolves when it is ready.
     *
     * @param {string} name
     * @returns {Promise<void>}
     */
    load(name: string): Promise<void>;
    /** @param {string} name */
    isLoaded(name: string): boolean;
};
/**
 * Called once by the runtime, before Alpine starts.
 *
 * @param {AlpineLike} Alpine
 */
export declare function deferLazyComponents(Alpine: AlpineLike): void;
/**
 * The components the first page already has start loading before Alpine reaches them.
 *
 * @param {ParentNode} [root]
 */
export declare function loadComponentsOnPage(root?: ParentNode): void;
