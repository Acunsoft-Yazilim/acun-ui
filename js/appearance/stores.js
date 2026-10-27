// @ts-nocheck: not type-checked yet; typed progressively (ARCHITECTURE.md, ADR-008). Its exports used
// by the public API carry JSDoc types for the declaration files.
//
// The Alpine stores of the layouts: sidebar, navbar, customizer, brand and appearance. They are a view of
// the appearance choices (appearance/preferences.js, AcunUI.appearance, which needs no Alpine): their
// methods change the choices there, and their fields follow every change, whoever made it. Registered
// once, on the first page with acun:layout.head (its config island).

import { appearance } from './preferences.js';

/**
 * @param {import('../core/types.js').AlpineLike} Alpine
 * @param {typeof appearance} choices
 */
export function registerStores(Alpine, choices = appearance) {
    const start = choices.get();

    Alpine.store('sidebar', {
        collapsed: start.sidebarCollapsed,

        // Kept in the store (not in the aside's own x-data): the aside's Alpine state can
        // become inconsistent across wire:navigate transitions; the store lives independently
        // of the DOM, so it is not affected by them.
        hover: false,

        // After a menu item is clicked and the DOM is morphed, the browser may fire a fake
        // mouseenter - this flag suppresses hover until the mouse REALLY moves (mousemove).
        hoverSuppressed: false,

        // Opens and closes immediately; any delay made the collapsed menu feel sluggish.
        // No effect unless the menu is collapsed.
        openHover() {
            if (!this.hoverSuppressed) this.hover = true;
        },

        closeHover() {
            this.hover = false;
        },

        toggle() {
            this.set(!this.collapsed);
        },

        /** The user's choice (the menu button, the customizer): applied, kept in the browser and saved to the account. */
        set(collapsed) {
            choices.setSidebarCollapsed(collapsed);
        },

        /** Applied and kept in the browser only. */
        setCollapsed(value) {
            choices.setSidebarCollapsed(value, { save: false });
        },
    });

    Alpine.store('navbar', {
        mode: start.navbarMode,

        set(value) {
            choices.setNavbarMode(value);
        },
    });

    // Appearance customizer: skin, content width and the dark menu in the light mode (semi-dark).
    Alpine.store('customizer', {
        skin: start.skin,
        contentWidth: start.contentWidth,
        semiDark: start.semiDark,

        init() {
            this.apply();
        },

        setSkin(value) {
            choices.setSkin(value);
        },

        setContentWidth(value) {
            choices.setContentWidth(value);
        },

        setSemiDark(value) {
            choices.setSemiDark(value);
        },

        apply() {
            choices.apply();
        },

        // Back to the application's defaults (config/acun-ui.php): the scope's saved choices are forgotten.
        reset() {
            choices.reset();
        },
    });

    Alpine.store('brand', {
        color: start.brandColor,

        set(value) {
            choices.setBrandColor(value);
        },

        // The saved colour is forgotten (the next page starts from the page's or the default colour).
        forget() {
            choices.forgetBrandColor();
        },
    });

    Alpine.store('appearance', {
        // The mode: light, dark or system.
        value: start.mode,

        // The page's storage scope (localStorage 'acun-ui:{scope}'): panel, shop, app…
        get scope() {
            return choices.scope;
        },

        init() {
            this.apply();
        },

        set(value) {
            choices.setMode(value);
        },

        // Navbar button: switches to the opposite of the mode on screen and saves the choice.
        toggle() {
            choices.toggleMode();
        },

        apply() {
            choices.apply();
        },
    });

    // The stores' fields follow the choices, whoever changed them (a store, AcunUI.appearance, another scope's page).
    choices.subscribe((state) => {
        Object.assign(Alpine.store('sidebar'), { collapsed: state.sidebarCollapsed });
        Object.assign(Alpine.store('navbar'), { mode: state.navbarMode });
        Object.assign(Alpine.store('customizer'), { skin: state.skin, contentWidth: state.contentWidth, semiDark: state.semiDark });
        Object.assign(Alpine.store('brand'), { color: state.brandColor });
        Object.assign(Alpine.store('appearance'), { value: state.mode });
    });

    // After a wire:navigate page change the menu's hover starts closed until the mouse really moves.
    document.addEventListener('livewire:navigated', () => {
        Alpine.store('sidebar').hover = false;
        Alpine.store('sidebar').hoverSuppressed = true;
    });
}
