/** The built-in pair (Acun\Ui\Support\Appearance), for a page whose island names none. */
export declare const DEFAULT_THEME: {
    key: string;
    light: string;
    dark: string;
};
/**
 * The text backgrounds a colour is measured against when the island has none (Appearance::TARGETS):
 * the light theme's page (#fafafa, darker than its surface and fields) and the dark theme's fields
 * (#1f1f1f, lighter than its page and surface).
 */
export declare const DEFAULT_TARGETS: {
    light: string;
    dark: string;
};
export declare const MODES: string[];
export declare const STORAGE_PREFIX = "acun-ui:";
/** The user's colour on <html>: --ac-user-fill, --ac-user-fill-hover … (css/themes.css reads them). */
export declare const USER_PROPERTIES: string[];
/** WCAG contrast ratio of two [r, g, b] colours: 1 to 21. Text needs 4.5, a frame or an icon 3. */
export declare function contrast(a: any, b: any): number;
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
export declare function brandShades(color: any, targets: any): {
    fill: string;
    hover: string;
    onFill: string;
    tone: string;
    toneDark: string;
};
export declare const storageKey: (scope: any) => string;
/** The scope's saved choices ({} when none, or when storage is blocked or holds something else). */
export declare function readStored(scope: any): any;
/** Saves choices into the scope's object (null removes one). */
export declare function writeStored(scope: any, values: any): void;
/** Forgets every choice of the scope. */
export declare function clearStored(scope: any): void;
/** The island's theme pair (the built-in one when it names none). */
export declare const themeOf: (config: any) => any;
/** The starting value of every setting. */
export declare function initialPreferences(config: any): {
    mode: any;
    brandColor: any;
    skin: any;
    contentWidth: any;
    navbarMode: any;
    sidebarCollapsed: boolean;
    semiDark: boolean;
};
export declare const prefersDark: () => boolean;
/** Whether the mode shows the dark member: dark, or system while the operating system is dark. */
export declare const isDarkMode: (mode: any) => boolean;
/**
 * Whether element is in a dark theme: the nearest data-theme ends in "dark" (AcunUI.isDark).
 *
 * @param {Element | null} [element]
 * @returns {boolean}
 */
export declare function isDark(element?: Element | null): boolean;
/**
 * <html data-theme>: the theme's dark member in the dark mode (a theme without one stays light),
 * else its light member. class="dark" goes with it for application CSS or JS that reads .dark,
 * until 3.0 (dark: classes follow data-theme).
 */
export declare function applyMode(mode: any, theme?: {
    key: string;
    light: string;
    dark: string;
}): void;
/**
 * The colour of a token on element as rgb() (AcunUI.tokenColor(element, '--ac-line')): custom
 * properties hold unresolved text (color-mix(…), var(…)), so a hidden probe resolves it.
 *
 * @param {Element | null | undefined} element
 * @param {string} name  a custom property: '--ac-line'
 * @returns {string}
 */
export declare function tokenColor(element: Element | null | undefined, name: string): string;
/** The colour a choice names: #rrggbb, or a preset of the island (a fill, or { light, dark }); null for the theme's own. */
export declare function colorOf(choice: any, colors: any): {
    light: any;
    dark: any;
} | null;
/**
 * The user's colour (a preset name or #rrggbb), measured on the island's targets and written once
 * on <html> as --ac-user-*: every theme block and every section reads them first. 'default', or a
 * name that is no longer a preset (saved before the palette changed), is the theme's own colour.
 */
export declare function applyColor(choice: any, config: any): void;
/** Skin (bordered): <html data-skin>. */
export declare const applySkin: (skin: any) => void;
/** Content width (boxed: at most 1440px, centred; see panel.css .panel-container): <html data-content-width>. */
export declare const applyContentWidth: (width: any) => void;
/** The menu width on the first frame, before Alpine (see panel.css). */
export declare const applySidebar: (collapsed: any) => void;
/**
 * Semi-dark: the theme's dark member on the menu (data-theme), or none: the menu's tokens and
 * application code's dark: classes follow it. The dark class goes with it for application CSS or
 * JS that reads .dark, until 3.0.
 */
export declare function applySemiDark(element: any, on: any, theme?: {
    key: string;
    light: string;
    dark: string;
}): void;
/** The menus that take the semi-dark choice (a 1.x copy of the sidebar layout has only its class). */
export declare const SEMI_DARK_SELECTOR = "[data-acun-semi-dark], .panel-sidebar";
/**
 * Until the page is parsed, every semi-dark menu and navbar the parser adds takes the saved choice
 * at once (a mutation observer runs before the browser paints), so neither flashes the server's
 * value first. After DOMContentLoaded the stores keep them.
 */
export declare function watchSections(preferences: any, config: any): MutationObserver | null;
/** Before the first paint (the boot script). */
export declare function applyInitialAppearance(config: any): {
    mode: any;
    brandColor: any;
    skin: any;
    contentWidth: any;
    navbarMode: any;
    sidebarCollapsed: boolean;
    semiDark: boolean;
};
