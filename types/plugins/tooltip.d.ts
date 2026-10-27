import type { PluginDefinition } from '../core/plugins.js';
export type TooltipInstance = {
    /**
     * the root (data-acun-tooltip)
     */
    element: HTMLElement;
    /**
     * the bubble
     */
    content: HTMLElement | null;
    isOpen: boolean;
    open: () => void;
    close: (options?: {
        delay?: number;
        notify?: boolean;
    }) => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const tooltipPlugin: PluginDefinition;
