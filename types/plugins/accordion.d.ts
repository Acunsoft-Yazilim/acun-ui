import type { PluginDefinition } from '../core/plugins.js';
export type AccordionInstance = {
    /**
     * the root (data-acun-accordion)
     */
    element: HTMLElement;
    /**
     * the open items' names
     */
    opened: string[];
    isItemOpen: (name: string) => boolean;
    /**
     * open: true opens, false closes, none switches
     */
    toggleItem: (name: string, open?: boolean) => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const accordionPlugin: PluginDefinition;
