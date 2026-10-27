import { VERSION } from './core/runtime.js';
import { EVENTS } from './core/events.js';
import { focusable } from './core/dom.js';
import { destroy, init, observe } from './core/plugins.js';
import { focusFirst, trapTab } from './behavior/focus-trap.js';
import { rememberFocus } from './behavior/focus-restore.js';
import { syncSelectValues } from './behavior/select-sync.js';
import { skipDetachedMorphs } from './behavior/morph-guard.js';
import { isDark, tokenColor } from './appearance/theme.js';
import { modalPlugin } from './plugins/modal.js';
import { drawerPlugin } from './plugins/drawer.js';
import { dropdownPlugin } from './plugins/dropdown.js';
import { popoverPlugin } from './plugins/popover.js';
import { tooltipPlugin } from './plugins/tooltip.js';
import { tabsPlugin } from './plugins/tabs.js';
import { accordionPlugin } from './plugins/accordion.js';
import { collapsePlugin } from './plugins/collapse.js';
import { toastsPlugin } from './plugins/toast.js';
import { copyPlugin } from './plugins/copy.js';
import { inlineConfirmPlugin } from './plugins/inline-confirm.js';
import { dismissiblePlugin } from './plugins/dismissible.js';
import { cardPlugin } from './plugins/card.js';
import { revealPlugin } from './plugins/reveal.js';
import { searchPlugin } from './plugins/search.js';
import { scrollShadowsPlugin } from './plugins/scroll-shadows.js';
import { downloadTextPlugin } from './plugins/download-text.js';
import { tableScrollPlugin } from './plugins/table-scroll.js';
import { drawer } from './components/drawer.js';
import { modal } from './components/modal.js';
export type Plugins = import('./core/plugins.js').Plugins;
export type PluginDefinition = import('./core/plugins.js').PluginDefinition;
export type PluginInstance = import('./core/plugins.js').PluginInstance;
export type PluginContext = import('./core/plugins.js').PluginContext;
export type OpenOptions = import('./core/plugins.js').OpenOptions;
export type ModalInstance = import('./plugins/modal.js').ModalInstance;
export type DrawerInstance = import('./plugins/drawer.js').DrawerInstance;
export type OverlayOptions = import('./behavior/overlay-manager.js').OverlayOptions;
export type Commands = import('./core/events.js').Commands;
/** @import { AcunWindow, AlpineLike } from './core/types.js' */
/** @typedef {import('./core/plugins.js').Plugins} Plugins */
/** @typedef {import('./core/plugins.js').PluginDefinition} PluginDefinition */
/** @typedef {import('./core/plugins.js').PluginInstance} PluginInstance */
/** @typedef {import('./core/plugins.js').PluginContext} PluginContext */
/** @typedef {import('./core/plugins.js').OpenOptions} OpenOptions */
/** @typedef {import('./plugins/modal.js').ModalInstance} ModalInstance */
/** @typedef {import('./plugins/drawer.js').DrawerInstance} DrawerInstance */
/** @typedef {import('./behavior/overlay-manager.js').OverlayOptions} OverlayOptions */
/** @typedef {import('./core/events.js').Commands} Commands */
declare const api: {
    events: {
        drawer: {
            open: string;
            close: string;
            toggle: string;
            opened: string;
            closed: string;
        };
        modal: {
            open: string;
            close: string;
            opened: string;
            closed: string;
        };
    };
    scrollLock: {
        lock(): void;
        unlock(): void;
        reset(): void;
        readonly count: number;
        readonly locked: boolean;
    };
    overlays: {
        open(element: HTMLElement | null | undefined, options?: import("./behavior/overlay-manager.js").OverlayOptions): void;
        close(element: Element | null | undefined): void;
        isOpen(element: Element): boolean;
        top(): HTMLElement | null;
        readonly count: number;
    };
    focus: {
        first: typeof focusFirst;
        trap: typeof trapTab;
        remember: typeof rememberFocus;
        focusable: typeof focusable;
    };
    drawer: import("./core/events.js").Commands & {
        toggle: (name: string) => void;
    };
    modal: import("./core/events.js").Commands;
    plugins: import("./core/plugins.js").Plugins;
    init: typeof init;
    destroy: typeof destroy;
    observe: typeof observe;
    components: {
        define(name: string, load: () => unknown, { defer }?: {
            defer?: boolean;
        }): void;
        load(name: string): Promise<void>;
        isLoaded(name: string): boolean;
    };
    load: (name: string) => Promise<void>;
    appearance: {
        readonly ready: boolean;
        readonly scope: any;
        get(): import("./appearance/preferences.js").AppearanceState | null;
        subscribe(listener: import("./appearance/preferences.js").AppearanceListener): () => void;
        setMode(mode: 'light' | 'dark' | 'system'): void;
        toggleMode(): void;
        setBrandColor(color: string): void;
        forgetBrandColor(): void;
        setSkin(skin: string): void;
        setContentWidth(width: string): void;
        setSemiDark(on: boolean): void;
        setSidebarCollapsed(collapsed: boolean, { save }?: {
            save?: boolean;
        }): void;
        setNavbarMode(mode: string): void;
        reset(): void;
        apply: () => void;
    };
    isDark: typeof isDark;
    tokenColor: typeof tokenColor;
};
export type AcunUIRuntime = typeof api & {
    version: string;
    initialized: true;
};
declare const liveComponents: {
    define(name: string, load: () => unknown, { defer }?: {
        defer?: boolean;
    }): void;
    load(name: string): Promise<void>;
    isLoaded(name: string): boolean;
};
declare const livePlugins: import("./core/plugins.js").Plugins;
declare const liveScrollLock: {
    lock(): void;
    unlock(): void;
    reset(): void;
    readonly count: number;
    readonly locked: boolean;
};
declare const liveOverlays: {
    open(element: HTMLElement | null | undefined, options?: import("./behavior/overlay-manager.js").OverlayOptions): void;
    close(element: Element | null | undefined): void;
    isOpen(element: Element): boolean;
    top(): HTMLElement | null;
    readonly count: number;
};
declare const liveFocusFirst: typeof focusFirst;
declare const liveTrapTab: typeof trapTab;
declare const liveRememberFocus: typeof rememberFocus;
declare const liveFocusable: typeof focusable;
declare const liveInit: typeof init;
declare const liveDestroy: typeof destroy;
declare const liveObserve: typeof observe;
export { VERSION, EVENTS, liveComponents as components, liveScrollLock as scrollLock, liveOverlays as overlays, liveFocusFirst as focusFirst, liveTrapTab as trapTab, liveRememberFocus as rememberFocus, liveFocusable as focusable, livePlugins as plugins, liveInit as init, liveDestroy as destroy, liveObserve as observe, modalPlugin, drawerPlugin, dropdownPlugin, popoverPlugin, tooltipPlugin, tabsPlugin, accordionPlugin, collapsePlugin, toastsPlugin, copyPlugin, inlineConfirmPlugin, dismissiblePlugin, cardPlugin, revealPlugin, searchPlugin, scrollShadowsPlugin, downloadTextPlugin, tableScrollPlugin, drawer, modal, syncSelectValues, skipDetachedMorphs, isDark, tokenColor, };
