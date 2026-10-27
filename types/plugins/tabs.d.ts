import type { PluginDefinition } from '../core/plugins.js';
export type TabsInstance = {
    /**
     * the root (data-acun-tabs)
     */
    element: HTMLElement;
    /**
     * the open tab
     */
    value: string | null;
    /**
     * opens a tab (an empty value: the first enabled)
     */
    select: (value: string | null | undefined, options?: {
        focus?: boolean;
    }) => void;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const tabsPlugin: PluginDefinition;
