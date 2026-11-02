import type { PluginDefinition } from '../core/plugins.js';
export type ThemeSwitcherInstance = {
    element: HTMLElement;
    /**
     * the drawer opens again on the next page
     */
    keepOpen: () => void;
    /**
     * back to the defaults (the scope's saved choices are forgotten)
     */
    reset: () => void;
    destroy: () => void;
};
/**
 * The Alpine scope of the 2.x markup: keepOpen() and reset() in a section of your own.
 *
 * @param {HTMLElement} root
 */
export declare function themeSwitcherScope(root: HTMLElement): {
    scope: {
        keepOpen: () => void | undefined;
        reset: () => void | undefined;
    };
    stop(): void;
};
/** @type {PluginDefinition} */
export declare const themeSwitcherPlugin: PluginDefinition;
