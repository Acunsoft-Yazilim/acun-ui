import type { PluginDefinition } from '../core/plugins.js';
export type CommandPaletteInstance = {
    element: HTMLElement;
    isOpen: boolean;
    open: () => void;
    close: () => void;
    toggle: () => void;
    /**
     * shows the options that match (the field's value)
     */
    filter: (query: string) => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const commandPalettePlugin: PluginDefinition;
