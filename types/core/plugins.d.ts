export type OpenOptions = {
    opener?: Element | null;
};
export type PluginInstance = {
    isOpen?: boolean;
    open?: (options?: OpenOptions) => void;
    close?: (options?: {
        notify?: boolean;
    }) => void;
    toggle?: (options?: OpenOptions) => void;
    requestClose?: () => void;
    destroy?: () => void;
};
export type PluginContext = {
    /**
     * the plugin's name
     */
    kind: string;
    /**
     * what plugins.mount(element, kind, options) passed
     */
    options: unknown;
    /**
     * the registry
     */
    plugins: Plugins;
};
export type Plugins = {
    /**
     * defines a plugin (the first definition of a name wins)
     */
    define: (kind: string, definition: PluginDefinition) => Plugins;
    /**
     * mounts one element by hand (idempotent)
     */
    mount: (element: Element, kind: string, options?: unknown) => PluginInstance | null;
    /**
     * unmounts one element (every plugin on it, or only kind)
     */
    unmount: (element: Element, kind?: string) => void;
    /**
     * the instance of kind on element
     */
    get: (element: Element | null | undefined, kind?: string) => PluginInstance | null;
    isMounted: (element: Element, kind?: string) => boolean;
    /**
     * the instances named name on the page
     */
    find: (name: string | null | undefined, kind?: string) => PluginInstance[];
    /**
     * the mounted instances of kind on the page
     */
    all: (kind: string) => PluginInstance[];
    has: (kind: string) => boolean;
    /**
     * the defined plugins' names
     */
    names: readonly string[];
    /**
     * the selector of every defined plugin's elements
     */
    selector: string;
    /**
     * marks attributes a plugin manages on element
     */
    own: (element: Element, ...names: string[]) => void;
    /**
     * copies the managed attributes onto a morph's new markup
     */
    keepOwned: (from: Element, to: Element) => void;
    /**
     * sets aria-expanded on the triggers of name
     */
    expand: (name: string | null, expanded: boolean) => void;
};
export type MountFunction = (element: HTMLElement, context: PluginContext) => PluginInstance | (() => void) | null | undefined | void;
export type PluginDefinition = {
    /**
     * the elements it mounts on (default: [attribute])
     */
    selector?: string;
    /**
     * the attribute whose value names an element (data-acun-open="name" finds it)
     */
    attribute?: string;
    mount: MountFunction;
    /**
     * runs once, when the plugin is defined
     */
    setup?: (context: {
        plugins: Plugins;
    }) => void;
};
export type Definition = {
    selector: string;
    attribute?: string;
    mount: MountFunction;
};
export type MountRecord = {
    instance: PluginInstance | null;
    cleanup: (() => void) | null;
};
/**
 * Mounts every plugin's elements in root (a document or an element, itself included); nothing without a DOM.
 *
 * @template {ParentNode | null} T
 * @param {T} [root]
 * @returns {T}
 */
export declare function init<T extends ParentNode | null>(root?: T): T;
/**
 * Unmounts every plugin instance in root (itself included): listeners, timers, the scroll lock it held.
 *
 * @template {ParentNode | null} T
 * @param {T} [root]
 * @returns {T}
 */
export declare function destroy<T extends ParentNode | null>(root?: T): T;
/**
 * Turns the observer on (default) or off; returns whether it is on. Off, the page's own code
 * calls AcunUI.init(element) and AcunUI.destroy(element).
 */
export declare function observe(enabled?: boolean): boolean;
/**
 * Runs once, from the runtime: mounts what the page has now (or at DOMContentLoaded while the page
 * is still parsing), starts the observer unless it is turned off, and handles the triggers.
 *
 * @param {{ observe?: boolean }} [options]
 */
export declare function start({ observe: watch }?: {
    observe?: boolean;
}): void;
/** @type {Plugins} */
export declare const plugins: Plugins;
