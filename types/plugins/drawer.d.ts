import type { OpenOptions, PluginDefinition } from '../core/plugins.js';
export type DrawerInstance = {
    /**
     * the root (data-acun-drawer)
     */
    element: HTMLElement;
    /**
     * the sliding panel
     */
    panel: HTMLElement;
    /**
     * beside the content from lg up (data-docked)
     */
    docked: boolean;
    name: string | null;
    isOpen: boolean;
    /**
     * modal now (a docked panel only below lg)
     */
    overlay: boolean;
    open: (options?: OpenOptions) => void;
    close: () => void;
    toggle: (options?: OpenOptions) => void;
    /**
     * the panel's elements that Tab reaches
     */
    focusables: () => HTMLElement[];
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const drawerPlugin: PluginDefinition;
