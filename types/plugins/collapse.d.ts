import type { PluginDefinition } from '../core/plugins.js';
export type CollapseInstance = {
    /**
     * the root (data-acun-collapse)
     */
    element: HTMLElement;
    isOpen: boolean;
    toggle: () => void;
    setOpen: (open: boolean) => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const collapsePlugin: PluginDefinition;
