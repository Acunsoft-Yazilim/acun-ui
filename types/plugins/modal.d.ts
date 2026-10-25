import type { OpenOptions, PluginDefinition } from '../core/plugins.js';
export type ModalInstance = {
    /**
     * the root (data-acun-modal)
     */
    element: HTMLElement;
    /**
     * the dialog panel
     */
    panel: HTMLElement;
    name: string | null;
    isOpen: boolean;
    open: (options?: OpenOptions) => void;
    /**
     * notify: false when the server asked for it (no close-action)
     */
    close: (options?: {
        notify?: boolean;
    }) => void;
    toggle: (options?: OpenOptions) => void;
    /**
     * the user's close: through data-request-close-event when there is one
     */
    requestClose: () => void;
    /**
     * the panel's elements that Tab reaches
     */
    focusables: () => HTMLElement[];
    destroy: () => void;
};
/** @type {PluginDefinition} */
export declare const modalPlugin: PluginDefinition;
