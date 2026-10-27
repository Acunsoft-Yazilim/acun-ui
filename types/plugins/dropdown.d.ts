import type { OpenOptions, PluginDefinition } from '../core/plugins.js';
export type DropdownInstance = {
    /**
     * the root (data-acun-dropdown)
     */
    element: HTMLElement;
    /**
     * the menu
     */
    content: HTMLElement | null;
    isOpen: boolean;
    /**
     * focus: the item to focus once open
     */
    open: (options?: OpenOptions & {
        focus?: 1 | -1 | 'first' | 'last';
    }) => void;
    /**
     * refocus: the focus goes back to the trigger
     */
    close: (options?: {
        refocus?: boolean;
        notify?: boolean;
    }) => void;
    toggle: () => void;
    /**
     * the menu's links and enabled buttons, in order
     */
    items: () => HTMLElement[];
    focusItem: (to: 1 | -1 | 'first' | 'last') => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const dropdownPlugin: PluginDefinition;
