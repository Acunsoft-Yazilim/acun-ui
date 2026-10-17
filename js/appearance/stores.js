// The Alpine stores of the panel layouts: sidebar, navbar, customizer, brand and
// appearance. Registered once, on the first page with acun:layout.head (its config island).

import { readConfig } from '../core/config.js';
import { csrfHeader } from '../core/url.js';
import { applyBrand, applyContentWidth, applySidebar, applySkin, applyTheme, initialPreferences, removeLocal, writeLocal } from './theme.js';

export function registerStores(Alpine, config) {
    // The page's settings: the preference address, the defaults and the colour presets. A page
    // reached with wire:navigate brings its own island, and its settings are used from then on
    // (the first page may have had no preference address, e.g. a sign-in page).
    let settings = config;
    const defaults = () => settings.defaults ?? {};
    const presets = () => settings.brandPresets ?? [];
    const start = initialPreferences(config);

    // Saves instantly applied UI preferences (theme, menu collapse, primary colour) to
    // the account. The appearance has already changed, so the request runs in the
    // background; even if it fails, the appearance in this session is kept. The CSRF
    // token goes only to an address on this origin.
    function savePreference(data) {
        if (!settings.preferenceUrl) return;

        fetch(settings.preferenceUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...csrfHeader(settings.preferenceUrl),
            },
            body: JSON.stringify(data),
        }).catch(() => {});
    }

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
            this.setCollapsed(collapsed);
            savePreference({ sidebar: collapsed ? 'collapsed' : 'expanded' });
        },

        /** Applied and kept in the browser only. */
        setCollapsed(value) {
            this.collapsed = value;
            applySidebar(value);
            writeLocal('sidebarCollapsed', value ? 'true' : 'false');
        },
    });

    Alpine.store('navbar', {
        mode: start.navbarMode,

        set(value) {
            this.mode = value;
            writeLocal('navbarMode', value);
            savePreference({ navbar: value });
        },
    });

    // Appearance customizer: skin, content width and dark menu in the light theme (semi-dark).
    Alpine.store('customizer', {
        skin: start.skin,
        contentWidth: start.contentWidth,
        semiDark: start.semiDark,

        init() {
            this.apply();
        },

        setSkin(value) {
            this.skin = value;
            applySkin(value);
            writeLocal('skin', value);
            savePreference({ skin: value });
        },

        setContentWidth(value) {
            this.contentWidth = value;
            applyContentWidth(value);
            writeLocal('contentWidth', value);
            savePreference({ content: value });
        },

        setSemiDark(value) {
            this.semiDark = value;
            this.apply();
            writeLocal('semiDark', value ? 'true' : 'false');
            savePreference({ semiDark: value });
        },

        apply() {
            applySkin(this.skin);
            applyContentWidth(this.contentWidth);
            document.querySelectorAll('.panel-sidebar').forEach((menu) => menu.classList.toggle('dark', this.semiDark));
        },

        // Resets all appearance preferences to the application's defaults (config/acun-ui.php).
        reset() {
            ['appearance', 'brandColor', 'sidebarCollapsed', 'navbarMode', 'skin', 'contentWidth', 'semiDark'].forEach(removeLocal);
            const values = defaults();
            Alpine.store('appearance').set(values.theme || 'system');
            Alpine.store('brand').set(values.brand_color || 'default');
            Alpine.store('sidebar').set(values.sidebar_mode === 'collapsed');
            Alpine.store('navbar').set(values.navbar_mode || 'fixed');
            this.setSkin(values.skin || 'default');
            this.setContentWidth(values.content_width || 'full');
            this.setSemiDark(values.semi_dark === true);
        },
    });

    Alpine.store('brand', {
        color: start.brandColor,

        set(value) {
            this.color = value;
            applyBrand(value, presets());
            writeLocal('brandColor', value);
            savePreference({ brand: value });
        },
    });

    Alpine.store('appearance', {
        value: start.theme,

        init() {
            this.apply();
            // "system" follows the operating system switching while the page is open.
            window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', () => {
                if (this.value === 'system') this.apply();
            });
        },

        set(value) {
            this.value = value;
            writeLocal('appearance', value);
            this.apply();
            savePreference({ theme: value });
        },

        // Navbar button: switches to the opposite of the theme on screen and saves the preference.
        toggle() {
            this.set(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
        },

        apply() {
            applyTheme(this.value);
        },
    });

    // On navigation Livewire writes the target page's RAW attributes to <html> (removing
    // those not in the server HTML) - the theme went back to light, the menu to expanded
    // and the brand colour to default. All of them are restored from the stores.
    function applyDocumentState() {
        settings = readConfig() ?? settings;
        applySidebar(Alpine.store('sidebar').collapsed);
        Alpine.store('appearance').apply();
        applyBrand(Alpine.store('brand').color, presets());
        Alpine.store('customizer').apply();
    }

    // onSwap: runs in the SAME step the new page is put into the DOM, before the browser
    // paints ('livewire:navigated' fires a frame later - a flash in dark mode).
    document.addEventListener('livewire:navigating', (event) => {
        if (typeof event.detail?.onSwap === 'function') event.detail.onSwap(applyDocumentState);
    });

    document.addEventListener('livewire:navigated', () => {
        Alpine.store('sidebar').hover = false;
        Alpine.store('sidebar').hoverSuppressed = true;
        applyDocumentState();
    });
}
