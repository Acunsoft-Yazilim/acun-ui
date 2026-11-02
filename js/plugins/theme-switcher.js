// acun:theme-switcher, in plain JavaScript (no Alpine): a view of the appearance choices (AcunUI.appearance).
//
// The navbar button (as="button") is the root itself; a click switches the mode and the status line after it
// says the new one:
//
//     <button type="button" data-acun-theme-switcher="button" data-dark="The dark theme is on." data-light="…">…</button>
//     <span class="sr-only" role="status" data-theme-switcher-status></span>
//
// The panel (as="drawer" or "inline") holds the sections. A choice is a native radio (or a checkbox) inside an
// element with data-appearance, the choice it shows and changes: mode, brandColor, skin, contentWidth, sidebar
// (expanded or collapsed), navbarMode, semiDark (a checkbox). The controls follow the choices, whoever makes
// them (another switcher, the menu button, another tab's page after wire:navigate).
//
//     <div data-acun-theme-switcher="drawer" data-name="theme-switcher">
//         <div role="radiogroup" data-appearance="mode"><input type="radio" value="dark" …></div>
//         <div role="radiogroup" data-appearance="brandColor">
//             <input type="radio" value="green" data-color="#008933"> …
//             <label data-slot="theme-switcher-custom"><span data-slot="theme-switcher-custom-swatch"></span>
//                 <span data-slot="theme-switcher-custom-value" hidden></span><input type="color" data-slot="theme-switcher-custom-input"></label>
//         </div>
//         <a href="?navbar=split" data-switcher-keep-open>…</a>
//         <button type="button" data-slot="theme-switcher-reset">Reset to defaults</button>
//         <p role="status" data-slot="theme-switcher-status" data-reset="The appearance is back to its defaults."></p>
//     </div>
//
// A link, a button or a form with data-switcher-keep-open reloads the page with a choice the server keeps (a
// theme, a header, the language): the drawer opens again at once there, without sliding in (sessionStorage
// 'acun-ui:{scope}:open' holds its name). On Alpine pages keepOpen() and reset() stay in the markup's scope.

import { appearance } from '../appearance/preferences.js';
import { isDark } from '../appearance/theme.js';
import { EVENTS, dispatch } from '../core/events.js';
import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';

/** @import { PluginDefinition } from '../core/plugins.js' */
/** @import { AppearanceState } from '../appearance/preferences.js' */

const ATTRIBUTE = 'data-acun-theme-switcher';
const HEX = /^#[0-9a-f]{6}$/i;

/**
 * Each choice: its value on the controls and what a control's value does.
 *
 * @type {Record<string, { read: (state: Partial<AppearanceState>) => string | boolean | undefined, write: (value: any) => void }>}
 */
const SETTINGS = {
    mode: { read: (state) => state.mode, write: (value) => appearance.setMode(value) },
    brandColor: { read: (state) => state.brandColor || 'default', write: (value) => appearance.setBrandColor(value) },
    skin: { read: (state) => state.skin, write: (value) => appearance.setSkin(value) },
    contentWidth: { read: (state) => state.contentWidth, write: (value) => appearance.setContentWidth(value) },
    navbarMode: { read: (state) => state.navbarMode, write: (value) => appearance.setNavbarMode(value) },
    sidebar: { read: (state) => (state.sidebarCollapsed ? 'collapsed' : 'expanded'), write: (value) => appearance.setSidebarCollapsed(value === 'collapsed') },
    semiDark: { read: (state) => state.semiDark, write: (value) => appearance.setSemiDark(value === true) },
};

/**
 * A theme switcher on the page (AcunUI.plugins.get(element, 'theme-switcher')).
 *
 * @typedef {object} ThemeSwitcherInstance
 * @property {HTMLElement} element
 * @property {() => void} keepOpen  the drawer opens again on the next page
 * @property {() => void} reset     back to the defaults (the scope's saved choices are forgotten)
 * @property {() => void} destroy
 */

const openKey = () => `acun-ui:${appearance.scope ?? 'app'}:open`;

/** @param {HTMLElement} control */
const inputsOf = (control) => /** @type {HTMLInputElement[]} */ (control.matches('input') ? [control] : [...control.querySelectorAll('input:not([type="color"])')]);

/**
 * The navbar button: light ↔ dark. The icon and its text follow the dark: variant (data-theme is written
 * before the first paint); the status line says the new mode.
 *
 * @param {HTMLElement} root
 * @returns {ThemeSwitcherInstance}
 */
function createToggle(root) {
    const onClick = () => {
        appearance.toggleMode();
        const status = root.nextElementSibling;
        if (status?.hasAttribute('data-theme-switcher-status')) status.textContent = (isDark(document.documentElement) ? root.dataset.dark : root.dataset.light) ?? '';
    };
    root.addEventListener('click', onClick);

    return {
        element: root,
        keepOpen() {},
        reset() {},
        destroy() {
            root.removeEventListener('click', onClick);
        },
    };
}

/**
 * @param {HTMLElement} root
 * @returns {ThemeSwitcherInstance}
 */
function createThemeSwitcher(root) {
    if (root.getAttribute(ATTRIBUTE) === 'button') return createToggle(root);

    const part = (/** @type {string} */ slot) => ownPart(root, ATTRIBUTE, slot);
    const drawer = root.getAttribute(ATTRIBUTE) === 'drawer';
    const name = root.dataset.name ?? '';
    const status = part('theme-switcher-status');
    const custom = part('theme-switcher-custom');
    const customInput = /** @type {HTMLInputElement | null} */ (part('theme-switcher-custom-input'));
    const customSwatch = part('theme-switcher-custom-swatch');
    const customValue = part('theme-switcher-custom-value');
    const defaultSwatch = part('theme-switcher-default-swatch');
    const controls = /** @type {HTMLElement[]} */ ([...root.querySelectorAll('[data-appearance]')]);
    // The page's own colour (the theme's --ac-tone-primary): the "Default" swatch and the custom input's start.
    const tone = getComputedStyle(document.documentElement).getPropertyValue('--ac-tone-primary').trim().toLowerCase();
    const themeHex = HEX.test(tone) ? tone : null;
    if (defaultSwatch && themeHex) defaultSwatch.style.background = themeHex;
    /** @type {ReturnType<typeof setTimeout> | undefined} */
    let colorTimer;
    /** @type {ReturnType<typeof setTimeout> | undefined} */
    let statusTimer;
    /** @type {(() => void) | null} */
    let stopInstant = null;

    const sync = () => {
        const state = appearance.get() ?? {};
        controls.forEach((control) => {
            const setting = SETTINGS[control.dataset.appearance ?? ''];
            if (!setting) return;
            const value = setting.read(state);
            inputsOf(control).forEach((input) => {
                input.checked = input.type === 'checkbox' ? value === true : input.value === String(value);
            });
        });

        // The colour of the user's own: the swatch, its #rrggbb and the colour input show it.
        const selected = String(SETTINGS.brandColor.read(state));
        const isCustom = HEX.test(selected);
        const preset = /** @type {HTMLInputElement[]} */ ([...root.querySelectorAll('[data-appearance="brandColor"] input[data-color]')]).find((input) => input.value === selected);
        const color = isCustom ? selected.toLowerCase() : selected === 'default' && themeHex ? themeHex : (preset?.dataset.color ?? themeHex ?? '#000000');
        custom?.toggleAttribute('data-custom', isCustom);
        if (customSwatch) customSwatch.style.background = color;
        if (customValue) {
            customValue.textContent = isCustom ? selected : '';
            customValue.toggleAttribute('hidden', !isCustom);
        }
        if (customInput && document.activeElement !== customInput) customInput.value = color;
    };

    const keepOpen = () => {
        if (!drawer) return;
        try {
            sessionStorage.setItem(openKey(), name);
        } catch {}
    };

    const reset = () => {
        appearance.reset();
        if (!status) return;
        // Emptied first, then said: the same sentence twice is read again.
        status.textContent = '';
        clearTimeout(statusTimer);
        statusTimer = setTimeout(() => (status.textContent = status.dataset.reset ?? ''), 100);
    };

    const onChange = (/** @type {Event} */ event) => {
        const input = /** @type {HTMLInputElement} */ (event.target);
        if (!(input instanceof HTMLInputElement) || input.type === 'color') return;
        const control = /** @type {HTMLElement | null} */ (input.closest('[data-appearance]'));
        const setting = control && root.contains(control) ? SETTINGS[control.dataset.appearance ?? ''] : undefined;
        if (!setting) return;
        setting.write(input.type === 'checkbox' ? input.checked : input.value);
    };
    const onColor = () => {
        clearTimeout(colorTimer);
        colorTimer = setTimeout(() => customInput && appearance.setBrandColor(customInput.value.toLowerCase()), 150);
    };
    const onClick = (/** @type {MouseEvent} */ event) => {
        const target = /** @type {Element} */ (event.target);
        if (target.closest('[data-slot="theme-switcher-reset"]')) reset();
        if (target.closest('a[data-switcher-keep-open], button[data-switcher-keep-open]')) keepOpen();
    };
    const onSubmit = (/** @type {Event} */ event) => {
        if (/** @type {Element} */ (event.target).closest('[data-switcher-keep-open]')) keepOpen();
    };

    root.addEventListener('change', onChange);
    root.addEventListener('click', onClick);
    root.addEventListener('submit', onSubmit);
    customInput?.addEventListener('input', onColor);
    const unsubscribe = appearance.subscribe(sync);
    sync();

    // The page reloaded after a choice the server keeps: the drawer opens again at once, without sliding in.
    // It slides in a couple of frames after it opens (later while the page is still loading), so sliding stays
    // off until its panel is in place.
    if (drawer) {
        let reopen = false;
        try {
            reopen = sessionStorage.getItem(openKey()) === name;
            if (reopen) sessionStorage.removeItem(openKey());
        } catch {}
        const panel = root.querySelector('[data-acun-drawer] [data-slot="drawer-panel"]');
        if (reopen && panel) {
            root.dataset.instant = '';
            const watch = new MutationObserver(() => panel.hasAttribute('data-open') && settle());
            const timer = setTimeout(() => settle(), 3000);
            const settle = () => {
                watch.disconnect();
                clearTimeout(timer);
                stopInstant = null;
                requestAnimationFrame(() => delete root.dataset.instant);
            };
            stopInstant = settle;
            watch.observe(panel, { attributes: true, attributeFilter: ['data-open'] });
            // After every plugin of the page has mounted (the drawer inside, too).
            queueMicrotask(() => dispatch(EVENTS.drawer.open, { name }));
        }
    }

    return {
        element: root,
        keepOpen,
        reset,
        destroy() {
            root.removeEventListener('change', onChange);
            root.removeEventListener('click', onClick);
            root.removeEventListener('submit', onSubmit);
            customInput?.removeEventListener('input', onColor);
            unsubscribe();
            clearTimeout(colorTimer);
            clearTimeout(statusTimer);
            stopInstant?.();
        },
    };
}

/**
 * The Alpine scope of the 2.x markup: keepOpen() and reset() in a section of your own.
 *
 * @param {HTMLElement} root
 */
export function themeSwitcherScope(root) {
    const instance = () => /** @type {ThemeSwitcherInstance | undefined} */ (/** @type {unknown} */ (plugins.get(root, 'theme-switcher')));

    return {
        scope: {
            keepOpen: () => instance()?.keepOpen(),
            reset: () => instance()?.reset(),
        },
        stop() {},
    };
}

/** @type {PluginDefinition} */
export const themeSwitcherPlugin = {
    attribute: ATTRIBUTE,
    mount: createThemeSwitcher,
};
