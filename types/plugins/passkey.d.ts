import type { PluginDefinition } from '../core/plugins.js';
export type PasskeyInstance = {
    element: HTMLElement;
    supported: boolean;
    /**
     * presses the button
     */
    run: () => Promise<void>;
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const passkeyPlugin: PluginDefinition;
