// @ts-nocheck: not type-checked yet; typed progressively (ARCHITECTURE.md, ADR-008). Its exports used
// by the public API carry JSDoc types for the declaration files.
//
// The look of the page, on <html>: the mode and theme (data-theme), the user's colour
// (--ac-user-*), the skin and the menu width. The boot script applies it before the first
// paint; the stores keep it.
//
// Every setting starts from: the value the page or the user's account gives (acun:layout.head
// props, in the config island), then the browser's saved choice (localStorage, one key per
// scope: 'acun-ui:panel', 'acun-ui:shop'…), then the application's defaults (config/acun-ui.php).
// The 1.x keys (appearance, brandColor…) are not read: saved choices start over once.

const WHITE = [255, 255, 255];
const BLACK = [0, 0, 0];
// A button's hover: 88% of its fill, as themes.css mixes the theme's own (with black).
const HOVER_SHARE = 0.88;
// The dark theme's text tone on a surface (themes.css --ac-primary-ink): 66% of the tone, the rest white.
const DARK_INK_SHARE = 0.66;

/** The built-in pair (Acun\Ui\Support\Appearance), for a page whose island names none. */
export const DEFAULT_THEME = { key: 'default', light: 'light', dark: 'dark' };
/**
 * The text backgrounds a colour is measured against when the island has none (Appearance::TARGETS):
 * the light theme's page (#fafafa, darker than its surface and fields) and the dark theme's fields
 * (#1f1f1f, lighter than its page and surface).
 */
export const DEFAULT_TARGETS = { light: '#fafafa', dark: '#1f1f1f' };
export const MODES = ['light', 'dark', 'system'];
export const STORAGE_PREFIX = 'acun-ui:';
/** The user's colour on <html>: --ac-user-fill, --ac-user-fill-hover … (css/themes.css reads them). */
export const USER_PROPERTIES = ['fill', 'fill-hover', 'on-fill', 'tone', 'tone-dark', 'fill-dark', 'fill-hover-dark', 'on-fill-dark'].map((name) => `--ac-user-${name}`);

const HEX = /^#[0-9a-f]{6}$/i;
const rgbOf = (hex) => [1, 3, 5].map((i) => Number.parseInt(hex.slice(i, i + 2), 16));

/** WCAG relative luminance of [r, g, b] (0-255). */
function luminance(rgb) {
    return rgb
        .map((channel) => channel / 255)
        .map((channel) => (channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4))
        .reduce((sum, channel, i) => sum + channel * [0.2126, 0.7152, 0.0722][i], 0);
}

/** WCAG contrast ratio of two [r, g, b] colours: 1 to 21. Text needs 4.5, a frame or an icon 3. */
export function contrast(a, b) {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

/** share of rgb, the rest of other, rounded as the browser paints color-mix(in srgb). */
const mix = (rgb, other, share) => rgb.map((channel, i) => Math.round(channel * share + other[i] * (1 - share)));
const toHex = (rgb) => `#${rgb.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;

/** The mix of rgb with toward (black or white) that changes it least, in 1% steps, for which reads(candidate) holds. */
function reach(rgb, toward, reads, start = 100) {
    for (let share = start; share > 0; share--) {
        const candidate = mix(rgb, toward, share / 100);
        if (reads(candidate)) return candidate;
    }

    return toward;
}

/**
 * The text and the hover of a button filled with the colour rgb. The text keeps its colour on
 * the hover. White while it reaches 4.5:1 on the fill; the hover is then the fill with 12% black,
 * where white reads better. Otherwise a dark shade of the colour (the least dark one that
 * reaches 4.5:1; black always does where white does not): if one reads on the darker hover too,
 * the hover darkens as for the other colours; if none does, the hover is the fill with 12% white
 * instead, where the dark shade that reads on the fill reads better still.
 */
function buttonShades(rgb) {
    const darker = mix(rgb, BLACK, HOVER_SHARE);
    if (contrast(WHITE, rgb) >= 4.5) return { text: WHITE, hover: darker };

    const text = reach(rgb, BLACK, (candidate) => contrast(candidate, darker) >= 4.5, 24);
    if (contrast(text, darker) >= 4.5) return { text, hover: darker };

    return { text: reach(rgb, BLACK, (candidate) => contrast(candidate, rgb) >= 4.5, 24), hover: mix(rgb, WHITE, HOVER_SHARE) };
}

/**
 * The measured shades of a primary colour (#rrggbb) on the given text backgrounds (targets: the
 * darkest light one and the lightest dark one of the enabled themes, Appearance::targets(); the
 * built-in light theme's is its page, so link text reads on the bare page as well as on a card):
 *   fill, hover, onFill   the button is filled with the colour as it is; its text is white or a
 *                         dark shade, whichever reaches 4.5:1, and stays the same on the hover
 *   tone                  text and links on light backgrounds: the colour, darkened just enough
 *                         for 4.5:1 on targets.light (so white text reads on it too)
 *   toneDark              the dark theme's tone: the colour's shade that carries white text (4.5:1;
 *                         application code's bg-brand-600 text-white), lightened just enough to
 *                         stand out 3:1 on targets.dark and for its text shade (66% of it, with
 *                         white) to read 4.5:1 there. It starts from white, not from the light
 *                         target, so a darker light page changes no colour of the dark theme.
 */
export function brandShades(color, targets) {
    const rgb = rgbOf(color);
    const { light, dark } = Object.fromEntries(Object.entries({ ...DEFAULT_TARGETS, ...targets }).map(([side, hex]) => [side, rgbOf(hex)]));
    const { text, hover } = buttonShades(rgb);
    const tone = reach(rgb, BLACK, (candidate) => contrast(candidate, light) >= 4.5);
    const onWhite = reach(rgb, BLACK, (candidate) => contrast(candidate, WHITE) >= 4.5);
    const toneDark = reach(onWhite, WHITE, (candidate) => contrast(candidate, dark) >= 3 && contrast(mix(candidate, WHITE, DARK_INK_SHARE), dark) >= 4.5);

    return { fill: toHex(rgb), hover: toHex(hover), onFill: toHex(text), tone: toHex(tone), toneDark: toHex(toneDark) };
}

/* ── Browser storage: one JSON object per scope ─────────────────────────────── */

export const storageKey = (scope) => STORAGE_PREFIX + (scope || 'app');

/** The scope's saved choices ({} when none, or when storage is blocked or holds something else). */
export function readStored(scope) {
    try {
        const value = JSON.parse(localStorage.getItem(storageKey(scope)));

        return value?.constructor === Object ? value : {};
    } catch {
        return {};
    }
}

/** Saves choices into the scope's object (null removes one). */
export function writeStored(scope, values) {
    const next = { ...readStored(scope), ...values };
    Object.keys(next).forEach((key) => next[key] === null && delete next[key]);

    try {
        if (Object.keys(next).length) localStorage.setItem(storageKey(scope), JSON.stringify(next));
        else localStorage.removeItem(storageKey(scope));
    } catch {
        // Private mode or blocked storage: the choice lasts for this page only.
    }
}

/** Forgets every choice of the scope. */
export function clearStored(scope) {
    try {
        localStorage.removeItem(storageKey(scope));
    } catch {
        // See writeStored.
    }
}

/* ── The page's settings ─────────────────────────────────────────────────────── */

/** The island's theme pair (the built-in one when it names none). */
export const themeOf = (config) => (config?.theme?.light ? config.theme : DEFAULT_THEME);

const modeOf = (value) => (MODES.includes(value) ? value : null);

/** The starting value of every setting. */
export function initialPreferences(config) {
    const page = config ?? {};
    const defaults = page.defaults ?? {};
    const saved = readStored(page.scope);
    const flag = (value) => (typeof value === 'boolean' ? value : null);

    return {
        mode: modeOf(page.mode) ?? modeOf(saved.mode) ?? modeOf(defaults.mode) ?? 'system',
        brandColor: page.brandColor || saved.brandColor || defaults.brand_color || 'default',
        skin: page.skin || saved.skin || defaults.skin || 'default',
        contentWidth: page.contentWidth || saved.contentWidth || defaults.content_width || 'full',
        navbarMode: page.navbarMode || saved.navbar || defaults.navbar_mode || 'fixed',
        sidebarCollapsed: page.sidebarMode ? page.sidebarMode === 'collapsed' : saved.sidebar ? saved.sidebar === 'collapsed' : defaults.sidebar_mode === 'collapsed',
        semiDark: flag(page.semiDark) ?? flag(saved.semiDark) ?? defaults.semi_dark === true,
    };
}

/* ── Mode and theme ──────────────────────────────────────────────────────────── */

export const prefersDark = () => window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false;

/** Whether the mode shows the dark member: dark, or system while the operating system is dark. */
export const isDarkMode = (mode) => mode === 'dark' || (mode === 'system' && prefersDark());

/**
 * Whether element is in a dark theme: the nearest data-theme ends in "dark" (AcunUI.isDark).
 *
 * @param {Element | null} [element]
 * @returns {boolean}
 */
export function isDark(element = document.documentElement) {
    return element?.closest?.('[data-theme]')?.dataset.theme.endsWith('dark') ?? false;
}

/**
 * <html data-theme>: the theme's dark member in the dark mode (a theme without one stays light),
 * else its light member. class="dark" goes with it for application CSS or JS that reads .dark,
 * until 3.0 (dark: classes follow data-theme).
 */
export function applyMode(mode, theme = DEFAULT_THEME) {
    const root = document.documentElement;
    const dark = Boolean(theme.dark) && isDarkMode(mode);

    root.dataset.theme = dark ? theme.dark : theme.light;
    root.classList.toggle('dark', dark);
}

/**
 * The colour of a token on element as rgb() (AcunUI.tokenColor(element, '--ac-line')): custom
 * properties hold unresolved text (color-mix(…), var(…)), so a hidden probe resolves it.
 *
 * @param {Element | null | undefined} element
 * @param {string} name  a custom property: '--ac-line'
 * @returns {string}
 */
export function tokenColor(element, name) {
    const host = element?.nodeType === 1 ? element : document.documentElement;
    const probe = document.createElement('span');
    probe.style.cssText = `display: none; color: var(${name})`;
    host.appendChild(probe);
    const value = getComputedStyle(probe).color;
    probe.remove();

    return value;
}

/* ── The user's colour ───────────────────────────────────────────────────────── */

/** The colour a choice names: #rrggbb, or a preset of the island (a fill, or { light, dark }); null for the theme's own. */
export function colorOf(choice, colors) {
    const preset = HEX.test(choice) ? choice : choice !== 'default' && Object.prototype.hasOwnProperty.call(colors ?? {}, choice) ? colors[choice] : null;
    const color = typeof preset === 'string' ? { light: preset } : preset;

    return HEX.test(color?.light) ? { light: color.light.toLowerCase(), dark: HEX.test(color.dark) ? color.dark.toLowerCase() : undefined } : null;
}

/**
 * The user's colour (a preset name or #rrggbb), measured on the island's targets and written once
 * on <html> as --ac-user-*: every theme block and every section reads them first. 'default', or a
 * name that is no longer a preset (saved before the palette changed), is the theme's own colour.
 */
export function applyColor(choice, config) {
    const root = document.documentElement;
    USER_PROPERTIES.forEach((name) => root.style.removeProperty(name));

    const color = colorOf(choice, config?.colors);
    if (!color) return;

    const shades = brandShades(color.light, config?.targets);
    const dark = color.dark ? brandShades(color.dark, config?.targets) : {};
    const values = { fill: shades.fill, 'fill-hover': shades.hover, 'on-fill': shades.onFill, tone: shades.tone, 'tone-dark': shades.toneDark, 'fill-dark': dark.fill, 'fill-hover-dark': dark.hover, 'on-fill-dark': dark.onFill };

    Object.entries(values).forEach(([name, value]) => value && root.style.setProperty(`--ac-user-${name}`, value));
}

/* ── Layout ──────────────────────────────────────────────────────────────────── */

/** <html data-{name}>: the value, or none. */
function setRoot(name, value) {
    if (value) document.documentElement.dataset[name] = value;
    else delete document.documentElement.dataset[name];
}

/** Skin (bordered): <html data-skin>. */
export const applySkin = (skin) => setRoot('skin', skin !== 'default' && skin);

/** Content width (boxed: at most 1440px, centred; see panel.css .panel-container): <html data-content-width>. */
export const applyContentWidth = (width) => setRoot('contentWidth', width === 'boxed' && width);

/** The menu width on the first frame, before Alpine (see panel.css). */
export const applySidebar = (collapsed) => setRoot('sidebarCollapsed', collapsed ? 'true' : 'false');

/**
 * Semi-dark: the theme's dark member on the menu (data-theme), or none: the menu's tokens and
 * application code's dark: classes follow it. The dark class goes with it for application CSS or
 * JS that reads .dark, until 3.0.
 */
export function applySemiDark(element, on, theme = DEFAULT_THEME) {
    const dark = on && theme.dark ? theme.dark : null;

    if (dark) element.dataset.theme = dark;
    else delete element.dataset.theme;
    element.classList.toggle('dark', Boolean(dark));
}

/** The menus that take the semi-dark choice (a 1.x copy of the sidebar layout has only its class). */
export const SEMI_DARK_SELECTOR = '[data-acun-semi-dark], .panel-sidebar';

/** The semi-dark menus and the navbar mode in root (an element or the document). */
function applySections(root, preferences, theme) {
    const within = (selector) => [...(root.matches?.(selector) ? [root] : []), ...(root.querySelectorAll?.(selector) ?? [])];

    within(SEMI_DARK_SELECTOR).forEach((element) => applySemiDark(element, preferences.semiDark, theme));
    within('[data-acun-navbar]').forEach((element) => (element.dataset.navbar = preferences.navbarMode));
}

/**
 * Until the page is parsed, every semi-dark menu and navbar the parser adds takes the saved choice
 * at once (a mutation observer runs before the browser paints), so neither flashes the server's
 * value first. After DOMContentLoaded the stores keep them.
 */
export function watchSections(preferences, config) {
    const theme = themeOf(config);
    applySections(document, preferences, theme);
    if (document.readyState !== 'loading' || typeof MutationObserver === 'undefined') return null;

    const observer = new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach((node) => node.nodeType === 1 && applySections(node, preferences, theme))));
    observer.observe(document.documentElement, { childList: true, subtree: true });
    document.addEventListener('DOMContentLoaded', () => {
        observer.disconnect();
        applySections(document, preferences, theme);
    }, { once: true });

    return observer;
}

/** Before the first paint (the boot script). */
export function applyInitialAppearance(config) {
    const preferences = initialPreferences(config);

    applyMode(preferences.mode, themeOf(config));
    applyColor(preferences.brandColor, config);
    applySkin(preferences.skin);
    applyContentWidth(preferences.contentWidth);
    applySidebar(preferences.sidebarCollapsed);

    return preferences;
}
