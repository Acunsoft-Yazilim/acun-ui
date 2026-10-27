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
 * The components in root (the first page: before Alpine reaches them) start loading; only one name's with only.
 *
 * @param {ParentNode} [root]
 * @param {string} [only]
 */
export declare function loadComponentsOnPage(root?: ParentNode, only?: string): void;
/**
 * Called once by the runtime: the components on the page, and (observe) the ones added later, start loading
 * without Alpine. On a page with Alpine the init interceptor above also defers their elements; a module still
 * loads once.
 *
 * @param {{ observe?: boolean }} [options]
 */
export declare function watchLazyComponents({ observe }?: {
    observe?: boolean;
}): void;
