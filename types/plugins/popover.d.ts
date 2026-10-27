import type { PluginDefinition } from '../core/plugins.js';
export type PopoverInstance = {
    /**
     * the root (data-acun-popover)
     */
    element: HTMLElement;
    /**
     * the bubble
     */
    content: HTMLElement | null;
    isOpen: boolean;
    open: () => void;
    close: (options?: {
        refocus?: boolean;
        notify?: boolean;
    }) => void;
    toggle: () => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const popoverPlugin: PluginDefinition;
