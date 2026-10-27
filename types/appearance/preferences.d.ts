export type AppearanceState = {
    mode: 'light' | 'dark' | 'system';
    /**
     * a preset name or #rrggbb
     */
    brandColor: string;
    /**
     * default, bordered…
     */
    skin: string;
    /**
     * full or boxed
     */
    contentWidth: string;
    /**
     * fixed, static, hidden
     */
    navbarMode: string;
    sidebarCollapsed: boolean;
    semiDark: boolean;
};
export type AppearanceKey = keyof AppearanceState;
export type AppearanceListener = (state: AppearanceState, changed: AppearanceKey | 'settings') => void;
export type AppearanceSettings = Record<string, any>;
/** Applies every choice to the document (the first start, and after wire:navigate rewrote <html>). */
declare function applyAll(): void;
export declare const appearance: {
    /** Whether the page has acun:layout.head (the choices exist). */
    readonly ready: boolean;
    /** The page's storage scope (localStorage 'acun-ui:{scope}'): panel, shop, app… */
    readonly scope: any;
    /** @returns {AppearanceState | null} the choices now (a copy), null on a page without acun:layout.head */
    get(): AppearanceState | null;
    /**
     * Calls listener after every change (and when a page of another scope brings its own choices).
     *
     * @param {AppearanceListener} listener
     * @returns {() => void} stops listening
     */
    subscribe(listener: AppearanceListener): () => void;
    /** @param {'light' | 'dark' | 'system'} mode */
    setMode(mode: 'light' | 'dark' | 'system'): void;
    /** The navbar button: the opposite of the mode on screen. */
    toggleMode(): void;
    /** @param {string} color a preset (default, green…) or #rrggbb */
    setBrandColor(color: string): void;
    /** The saved colour is forgotten (the next page starts from the page's or the default colour). */
    forgetBrandColor(): void;
    /** @param {string} skin */
    setSkin(skin: string): void;
    /** @param {string} width full or boxed */
    setContentWidth(width: string): void;
    /** @param {boolean} on */
    setSemiDark(on: boolean): void;
    /**
     * @param {boolean} collapsed
     * @param {{ save?: boolean }} [options]  save: false keeps it in the browser only
     */
    setSidebarCollapsed(collapsed: boolean, { save }?: {
        save?: boolean;
    }): void;
    /** @param {string} mode fixed, static or hidden */
    setNavbarMode(mode: string): void;
    /** Back to the application's defaults (config/acun-ui.php): the scope's saved choices are forgotten. */
    reset(): void;
    /** Applies every choice to the document again. */
    apply: typeof applyAll;
};
/**
 * Starts the choices from a page's settings (the runtime, once; later calls return the same state).
 *
 * @param {AppearanceSettings} config
 */
export declare function startAppearance(config: AppearanceSettings): {
    /** Whether the page has acun:layout.head (the choices exist). */
    readonly ready: boolean;
    /** The page's storage scope (localStorage 'acun-ui:{scope}'): panel, shop, app… */
    readonly scope: any;
    /** @returns {AppearanceState | null} the choices now (a copy), null on a page without acun:layout.head */
    get(): AppearanceState | null;
    /**
     * Calls listener after every change (and when a page of another scope brings its own choices).
     *
     * @param {AppearanceListener} listener
     * @returns {() => void} stops listening
     */
    subscribe(listener: AppearanceListener): () => void;
    /** @param {'light' | 'dark' | 'system'} mode */
    setMode(mode: 'light' | 'dark' | 'system'): void;
    /** The navbar button: the opposite of the mode on screen. */
    toggleMode(): void;
    /** @param {string} color a preset (default, green…) or #rrggbb */
    setBrandColor(color: string): void;
    /** The saved colour is forgotten (the next page starts from the page's or the default colour). */
    forgetBrandColor(): void;
    /** @param {string} skin */
    setSkin(skin: string): void;
    /** @param {string} width full or boxed */
    setContentWidth(width: string): void;
    /** @param {boolean} on */
    setSemiDark(on: boolean): void;
    /**
     * @param {boolean} collapsed
     * @param {{ save?: boolean }} [options]  save: false keeps it in the browser only
     */
    setSidebarCollapsed(collapsed: boolean, { save }?: {
        save?: boolean;
    }): void;
    /** @param {string} mode fixed, static or hidden */
    setNavbarMode(mode: string): void;
    /** Back to the application's defaults (config/acun-ui.php): the scope's saved choices are forgotten. */
    reset(): void;
    /** Applies every choice to the document again. */
    apply: typeof applyAll;
};
/**
 * A newer page's settings (wire:navigate): a page of another scope starts from that scope's choices; then
 * every choice goes back on the document, which the navigation rewrote.
 *
 * @param {AppearanceSettings | null} next
 */
export declare function followAppearance(next: AppearanceSettings | null): void;
export {};
