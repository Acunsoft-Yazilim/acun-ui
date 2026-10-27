// The page's appearance choices without Alpine: the mode (light, dark, system), the primary colour, the
// skin, the content width, the dark menu in the light mode (semi-dark), the menu's collapse and the navbar
// mode. Started once, on the first page with acun:layout.head (its config island). Every choice is applied
// to the document at once, kept in the browser per scope (localStorage 'acun-ui:{scope}', so two products
// on one origin never share a colour or a mode) and saved to the account when the page has a preference
// address. AcunUI.appearance is the public API; the Alpine stores of the layouts (appearance/stores.js)
// and the React and Vue adapters are views of it (subscribe).

import { csrfHeader } from '../core/url.js';
import {
    SEMI_DARK_SELECTOR,
    applyColor,
    applyContentWidth,
    applyMode,
    applySemiDark,
    applySidebar,
    applySkin,
    clearStored,
    initialPreferences,
    isDark,
    themeOf,
    writeStored,
} from './theme.js';

/**
 * The choices (AcunUI.appearance.get()).
 *
 * @typedef {object} AppearanceState
 * @property {'light' | 'dark' | 'system'} mode
 * @property {string} brandColor        a preset name or #rrggbb
 * @property {string} skin              default, bordered…
 * @property {string} contentWidth      full or boxed
 * @property {string} navbarMode        fixed, static, hidden
 * @property {boolean} sidebarCollapsed
 * @property {boolean} semiDark
 */

/** @typedef {keyof AppearanceState} AppearanceKey */
/** @typedef {(state: AppearanceState, changed: AppearanceKey | 'settings') => void} AppearanceListener */
/** @typedef {Record<string, any>} AppearanceSettings  the config island of acun:layout.head */

/** @type {AppearanceSettings | null} */
let settings = null;
/** @type {AppearanceState | null} */
let state = null;
/** @type {Set<AppearanceListener>} */
const listeners = new Set();

const scopeOf = () => settings?.scope || 'app';

/** @param {Record<string, unknown>} values */
const remember = (values) => writeStored(settings?.scope, values);

/**
 * Saves a choice to the account in the background: the appearance has already changed, and it stays
 * in this session even if the request fails. The CSRF token goes only to an address on this origin.
 *
 * @param {Record<string, unknown>} data
 */
function savePreference(data) {
    const url = settings?.preferenceUrl;
    if (!url || typeof fetch !== 'function') return;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...csrfHeader(url) },
        body: JSON.stringify(data),
    }).catch(() => {});
}

/** @param {AppearanceKey | 'settings'} changed */
function notify(changed) {
    if (!state) return;
    const snapshot = { ...state };
    listeners.forEach((listener) => listener(snapshot, changed));
}

const applySemiDarkMenus = () => document.querySelectorAll(SEMI_DARK_SELECTOR)
    .forEach((menu) => applySemiDark(menu, state?.semiDark ?? false, themeOf(settings)));

const applyNavbars = () => document.querySelectorAll('[data-acun-navbar]')
    .forEach((element) => (/** @type {HTMLElement} */ (element).dataset.navbar = state?.navbarMode ?? 'fixed'));

/**
 * Changes one choice: applied, kept in the browser and (save) saved to the account.
 *
 * @template {AppearanceKey} K
 * @param {K} key
 * @param {AppearanceState[K]} value
 * @param {{ apply: () => void, stored: Record<string, unknown>, saved: Record<string, unknown> | null }} effects
 */
function change(key, value, { apply, stored, saved }) {
    // A page without acun:layout.head (plain HTML, React, Vue): the first change starts the choices from the
    // defaults, so the setters work there too (they did nothing before).
    if (!state) startAppearance({});
    if (!state) return;

    state[key] = value;
    apply();
    remember(stored);
    if (saved) savePreference(saved);
    notify(key);
}

/** Applies every choice to the document (the first start, and after wire:navigate rewrote <html>). */
function applyAll() {
    if (!state) return;

    applySidebar(state.sidebarCollapsed);
    applyMode(state.mode, themeOf(settings));
    applyColor(state.brandColor, settings);
    applySkin(state.skin);
    applyContentWidth(state.contentWidth);
    applySemiDarkMenus();
}

export const appearance = {
    /** Whether the page has acun:layout.head (the choices exist). */
    get ready() {
        return state !== null;
    },

    /** The page's storage scope (localStorage 'acun-ui:{scope}'): panel, shop, app… */
    get scope() {
        return scopeOf();
    },

    /** @returns {AppearanceState | null} the choices now (a copy); null on a page without acun:layout.head until a setter starts them from the defaults */
    get() {
        return state ? { ...state } : null;
    },

    /**
     * Calls listener after every change (and when a page of another scope brings its own choices).
     *
     * @param {AppearanceListener} listener
     * @returns {() => void} stops listening
     */
    subscribe(listener) {
        listeners.add(listener);
        return () => {
            listeners.delete(listener);
        };
    },

    /** @param {'light' | 'dark' | 'system'} mode */
    setMode(mode) {
        change('mode', mode, { apply: () => applyMode(mode, themeOf(settings)), stored: { mode }, saved: { mode } });
    },

    /** The navbar button: the opposite of the mode on screen. */
    toggleMode() {
        appearance.setMode(isDark(document.documentElement) ? 'light' : 'dark');
    },

    /** @param {string} color a preset (default, green…) or #rrggbb */
    setBrandColor(color) {
        change('brandColor', color, { apply: () => applyColor(color, settings), stored: { brandColor: color }, saved: { brand: color } });
    },

    /** The saved colour is forgotten (the next page starts from the page's or the default colour). */
    forgetBrandColor() {
        remember({ brandColor: null });
    },

    /** @param {string} skin */
    setSkin(skin) {
        change('skin', skin, { apply: () => applySkin(skin), stored: { skin }, saved: { skin } });
    },

    /** @param {string} width full or boxed */
    setContentWidth(width) {
        change('contentWidth', width, { apply: () => applyContentWidth(width), stored: { contentWidth: width }, saved: { content: width } });
    },

    /** @param {boolean} on */
    setSemiDark(on) {
        change('semiDark', on, { apply: applySemiDarkMenus, stored: { semiDark: on }, saved: { semiDark: on } });
    },

    /**
     * @param {boolean} collapsed
     * @param {{ save?: boolean }} [options]  save: false keeps it in the browser only
     */
    setSidebarCollapsed(collapsed, { save = true } = {}) {
        const mode = collapsed ? 'collapsed' : 'expanded';
        change('sidebarCollapsed', collapsed, { apply: () => applySidebar(collapsed), stored: { sidebar: mode }, saved: save ? { sidebar: mode } : null });
    },

    /** @param {string} mode fixed, static or hidden */
    setNavbarMode(mode) {
        change('navbarMode', mode, { apply: applyNavbars, stored: { navbar: mode }, saved: { navbar: mode } });
    },

    /** Back to the application's defaults (config/acun-ui.php): the scope's saved choices are forgotten. */
    reset() {
        if (!state) return;
        const values = settings?.defaults ?? {};

        appearance.setMode(values.mode || 'system');
        appearance.setBrandColor(values.brand_color || 'default');
        appearance.setSidebarCollapsed(values.sidebar_mode === 'collapsed');
        appearance.setNavbarMode(values.navbar_mode || 'fixed');
        appearance.setSkin(values.skin || 'default');
        appearance.setContentWidth(values.content_width || 'full');
        appearance.setSemiDark(values.semi_dark === true);
        clearStored(settings?.scope);
    },

    /** Applies every choice to the document again. */
    apply: applyAll,
};

/**
 * Starts the choices from a page's settings (the runtime, once; later calls return the same state).
 *
 * @param {AppearanceSettings} config
 */
export function startAppearance(config) {
    if (state) return appearance;

    settings = config;
    state = /** @type {AppearanceState} */ (initialPreferences(config));
    // "system" follows the operating system switching while the page is open.
    window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', () => {
        if (state?.mode === 'system') applyMode('system', themeOf(settings));
    });

    return appearance;
}

/**
 * A newer page's settings (wire:navigate): a page of another scope starts from that scope's choices; then
 * every choice goes back on the document, which the navigation rewrote.
 *
 * @param {AppearanceSettings | null} next
 */
export function followAppearance(next) {
    if (!state) return;

    const page = next ?? settings ?? {};
    const adopt = (page.scope ?? 'app') !== (settings?.scope ?? 'app');
    settings = page;
    if (adopt) Object.assign(state, initialPreferences(page));
    applyAll();
    if (adopt) notify('settings');
}
