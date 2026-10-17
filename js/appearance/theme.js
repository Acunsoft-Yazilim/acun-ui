// The look of the page, on <html>: dark mode, primary colour, skin and the menu
// width. The boot script applies it before the first paint; the stores keep it.
//
// Every setting starts from: the value the page or the user's account gives
// (acun:layout.head props, in the config island), then the browser's saved choice
// (localStorage), then the application's defaults (config/acun-ui.php).

// --color-brand-600 is listed for pages that still carry it from an earlier version.
const CUSTOM_BRAND_PROPERTIES = ['--color-brand-600', '--brand-custom-600', '--brand-custom-600-dark', '--btn-primary-bg', '--btn-primary-border', '--btn-primary-hover', '--btn-primary-hover-border', '--btn-primary-shadow', '--btn-primary-color', '--btn-primary-hover-color'];

const WHITE = [255, 255, 255];
const BLACK = [0, 0, 0];
// The lightest of the dark theme's surfaces a focus frame sits on (zinc-800: fields, hovered rows).
// Standing out 3:1 on it, a frame stands out on the darker ones too (zinc-900 cards, the zinc-950 page).
const DARK_SURFACE = [31, 31, 31];
// A button's hover: 88% of its fill, as panel.css mixes the default's and the presets' (with black).
const HOVER_SHARE = 0.88;

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

/** The mix of rgb with toward (black or white) that changes it least, in 1% steps, and reaches minimum against background. */
function reach(rgb, toward, background, minimum, start = 100) {
    for (let share = start; share > 0; share--) {
        const candidate = mix(rgb, toward, share / 100);
        if (contrast(candidate, background) >= minimum) return candidate;
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

    const text = reach(rgb, BLACK, darker, 4.5, 24);
    if (contrast(text, darker) >= 4.5) return { text, hover: darker };

    return { text: reach(rgb, BLACK, rgb, 4.5, 24), hover: mix(rgb, WHITE, HOVER_SHARE) };
}

/**
 * The shades of a custom primary colour (#rrggbb), measured: the button is filled with the
 * colour as it is; its text is white or a dark shade, whichever reaches 4.5:1, and stays the
 * same on the hover (buttonShades). --color-brand-600 (links, the active menu item, focus
 * frames, small fills with white text) is the colour, darkened just enough for 4.5:1 on white;
 * in the dark theme a dark one is lightened just enough to stand out 3:1 on every dark surface.
 */
export function brandShades(color) {
    const rgb = [1, 3, 5].map((i) => Number.parseInt(color.slice(i, i + 2), 16));
    const { text, hover } = buttonShades(rgb);
    const brand600 = reach(rgb, BLACK, WHITE, 4.5);

    return {
        hover: toHex(hover),
        text: toHex(text),
        // The same as text; kept for --btn-primary-hover-color.
        hoverText: toHex(text),
        brand600: toHex(brand600),
        brand600Dark: toHex(reach(brand600, WHITE, DARK_SURFACE, 3)),
    };
}

export function readLocal(key) {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
}

export function writeLocal(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch {
        // Private mode or blocked storage: the choice lasts for this page only.
    }
}

export function removeLocal(key) {
    try {
        localStorage.removeItem(key);
    } catch {
        // See writeLocal.
    }
}

export function isDark(preference) {
    return preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

export function applyTheme(preference) {
    document.documentElement.classList.toggle('dark', isDark(preference));
}

/**
 * Primary colour: a preset name or #rrggbb. A preset's shades come from panel.css;
 * a name that is no longer a preset (e.g. one saved before the palette changed) falls
 * back to the default. A custom colour fills the primary button as it is; the rest is
 * measured (brandShades), and panel.css takes --color-brand-600 for the light or the
 * dark theme from it (all brand-* shades derive from --color-brand-600).
 */
export function applyBrand(color, presets = []) {
    const root = document.documentElement;
    CUSTOM_BRAND_PROPERTIES.forEach((name) => root.style.removeProperty(name));

    if (!/^#[0-9a-f]{6}$/i.test(color || '')) {
        root.dataset.brandColor = presets.includes(color) ? color : 'default';
        return;
    }

    const shades = brandShades(color);

    root.dataset.brandColor = 'custom';
    Object.entries({
        '--brand-custom-600': shades.brand600,
        '--brand-custom-600-dark': shades.brand600Dark,
        '--btn-primary-bg': color,
        '--btn-primary-border': color,
        '--btn-primary-hover': shades.hover,
        '--btn-primary-hover-border': shades.hover,
        '--btn-primary-shadow': `color-mix(in srgb, ${color} 90%, transparent)`,
        '--btn-primary-color': shades.text,
        '--btn-primary-hover-color': shades.hoverText,
    }).forEach(([name, value]) => root.style.setProperty(name, value));
}

/** Skin (bordered): <html data-skin>. */
export function applySkin(skin) {
    if (skin && skin !== 'default') document.documentElement.dataset.skin = skin;
    else delete document.documentElement.dataset.skin;
}

/** Content width (boxed: at most 1440px, centred; see panel.css .panel-container): <html data-content-width>. */
export function applyContentWidth(width) {
    if (width === 'boxed') document.documentElement.dataset.contentWidth = 'boxed';
    else delete document.documentElement.dataset.contentWidth;
}

/** The menu width on the first frame, before Alpine (see panel.css). */
export function applySidebar(collapsed) {
    document.documentElement.dataset.sidebarCollapsed = collapsed ? 'true' : 'false';
}

const savedFlag = (key) => (readLocal(key) !== null ? readLocal(key) === 'true' : null);

/** The starting value of every setting. */
export function initialPreferences(config) {
    const page = config ?? {};
    const defaults = page.defaults ?? {};

    return {
        theme: page.theme || readLocal('appearance') || defaults.theme || 'system',
        brandColor: page.brandColor || readLocal('brandColor') || defaults.brand_color || 'default',
        skin: page.skin || readLocal('skin') || defaults.skin || 'default',
        contentWidth: page.contentWidth || readLocal('contentWidth') || defaults.content_width || 'full',
        navbarMode: page.navbarMode || readLocal('navbarMode') || defaults.navbar_mode || 'fixed',
        sidebarCollapsed: page.sidebarMode ? page.sidebarMode === 'collapsed' : (savedFlag('sidebarCollapsed') ?? defaults.sidebar_mode === 'collapsed'),
        semiDark: typeof page.semiDark === 'boolean' ? page.semiDark : (savedFlag('semiDark') ?? defaults.semi_dark === true),
    };
}

/** Before the first paint (the boot script). */
export function applyInitialAppearance(config) {
    const preferences = initialPreferences(config);

    applyTheme(preferences.theme);
    applyBrand(preferences.brandColor, config?.brandPresets);
    applySkin(preferences.skin);
    applyContentWidth(preferences.contentWidth);
    applySidebar(preferences.sidebarCollapsed);
}
