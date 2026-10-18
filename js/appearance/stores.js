// The Alpine stores of the layouts: sidebar, navbar, customizer, brand and appearance.
// Registered once, on the first page with acun:layout.head (its config island). The choices
// are kept per scope (localStorage 'acun-ui:{scope}'), so two products on one origin never
// share a colour or a mode.

import { readConfig } from '../core/config.js';
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

export function registerStores(Alpine, config) {
    // The page's settings: the scope, the theme pair, the colours, the preference address and the
    // defaults. A page reached with wire:navigate brings its own island, and its settings are used
    // from then on (the first page may have had no preference address, e.g. a sign-in page).
    let settings = config;
    const defaults = () => settings.defaults ?? {};
    const scope = () => settings.scope;
    const remember = (values) => writeStored(scope(), values);
    const start = initialPreferences(config);

    // Saves instantly applied UI preferences (mode, menu collapse, primary colour) to
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
            remember({ sidebar: value ? 'collapsed' : 'expanded' });
        },
    });

    Alpine.store('navbar', {
        mode: start.navbarMode,

        set(value) {
            this.mode = value;
            remember({ navbar: value });
            savePreference({ navbar: value });
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
            this.skin = value;
            applySkin(value);
            remember({ skin: value });
            savePreference({ skin: value });
        },

        setContentWidth(value) {
            this.contentWidth = value;
            applyContentWidth(value);
            remember({ contentWidth: value });
            savePreference({ content: value });
        },

        setSemiDark(value) {
            this.semiDark = value;
            this.apply();
            remember({ semiDark: value });
            savePreference({ semiDark: value });
        },

        apply() {
            applySkin(this.skin);
            applyContentWidth(this.contentWidth);
            document.querySelectorAll(SEMI_DARK_SELECTOR).forEach((menu) => applySemiDark(menu, this.semiDark, themeOf(settings)));
        },

        // Back to the application's defaults (config/acun-ui.php): the scope's saved choices are forgotten.
        reset() {
            const values = defaults();
            Alpine.store('appearance').set(values.mode || 'system');
            Alpine.store('brand').set(values.brand_color || 'default');
            Alpine.store('sidebar').set(values.sidebar_mode === 'collapsed');
            Alpine.store('navbar').set(values.navbar_mode || 'fixed');
            this.setSkin(values.skin || 'default');
            this.setContentWidth(values.content_width || 'full');
            this.setSemiDark(values.semi_dark === true);
            clearStored(scope());
        },
    });

    Alpine.store('brand', {
        color: start.brandColor,

        set(value) {
            this.color = value;
            applyColor(value, settings);
            remember({ brandColor: value });
            savePreference({ brand: value });
        },

        // The saved colour is forgotten (the next page starts from the page's or the default colour).
        forget() {
            remember({ brandColor: null });
        },
    });

    Alpine.store('appearance', {
        // The mode: light, dark or system.
        value: start.mode,

        // The page's storage scope (localStorage 'acun-ui:{scope}'): panel, shop, app…
        get scope() {
            return scope() || 'app';
        },

        init() {
            this.apply();
            // "system" follows the operating system switching while the page is open.
            window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', () => {
                if (this.value === 'system') this.apply();
            });
        },

        set(value) {
            this.value = value;
            remember({ mode: value });
            this.apply();
            savePreference({ mode: value });
        },

        // Navbar button: switches to the opposite of the mode on screen and saves the choice.
        toggle() {
            this.set(isDark(document.documentElement) ? 'light' : 'dark');
        },

        apply() {
            applyMode(this.value, themeOf(settings));
        },
    });

    // A page of another scope (reached with wire:navigate) starts from that scope's choices.
    function adopt(next) {
        const values = initialPreferences(next);
        Object.assign(Alpine.store('sidebar'), { collapsed: values.sidebarCollapsed });
        Object.assign(Alpine.store('navbar'), { mode: values.navbarMode });
        Object.assign(Alpine.store('customizer'), { skin: values.skin, contentWidth: values.contentWidth, semiDark: values.semiDark });
        Object.assign(Alpine.store('brand'), { color: values.brandColor });
        Object.assign(Alpine.store('appearance'), { value: values.mode });
    }

    // On navigation Livewire writes the target page's RAW attributes to <html> (removing
    // those not in the server HTML): data-theme went back to the light member, the menu to
    // expanded and the user's colour away. All of them are restored from the stores.
    function applyDocumentState() {
        const next = readConfig() ?? settings;
        if ((next.scope ?? 'app') !== (settings.scope ?? 'app')) adopt(next);
        settings = next;

        applySidebar(Alpine.store('sidebar').collapsed);
        Alpine.store('appearance').apply();
        applyColor(Alpine.store('brand').color, settings);
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
