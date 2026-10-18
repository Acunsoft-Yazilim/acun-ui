import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { brandShades } from '../js/appearance/theme.js';
import { chrome, inChrome, stylesheet } from './js/chrome.mjs';

// The design tokens: css/themes.css (the --ac-* roles of the built-in light and dark themes) and
// css/tokens.css (their Tailwind utilities). Checked here:
// - every token is set in the light theme and resolves in the dark theme, and every colour role
//   is a utility;
// - the contrast every role promises, for light, dark and the bordered skin of both;
// - the tokens carry the 1.x look: the values equal the palette the components used, and the
//   primary roles read the user's colour first (--ac-user-*, js/appearance/theme.js), in every
//   block and every nested section: the presets, a custom colour, the semi-dark menu and a
//   light island (in Chrome, where one is installed);
// - the corner and font utilities read their tokens, and brand-* (application code) follows them;
// - the PRO stylesheet reads the tokens, without fallbacks of its own.
// Colours are resolved as the browser mixes them: #rrggbb, rgb(), var() with fallbacks, and
// color-mix(in srgb | in oklab, <colour> N%, <colour>) where an oklab mix is with transparent.

const read = (file) => fs.readFileSync(new URL(file, import.meta.url), 'utf8');
const themesCss = read('../css/themes.css');
const tokensCss = read('../css/tokens.css');
const panelCss = read('../css/panel.css');
const proCss = read('../../acun-pro/resources/css/panel.css');

const TONES = ['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'dark', 'accent'];
const roles = (tone) => [tone, `${tone}-hover`, `on-${tone}`, `${tone}-ink`, `${tone}-line`, `${tone}-tint`, `${tone}-soft`, `${tone}-tint-ink`];
const NEUTRALS = ['page', 'surface', 'field', 'field-hover', 'field-disabled', 'sunken', 'hover', 'track', 'inverse', 'on-inverse', 'line', 'line-soft', 'line-strong', 'line-hover', 'control', 'ink', 'body', 'dim', 'muted', 'faint'];
const COLOUR_ROLES = [...NEUTRALS, ...TONES.flatMap(roles), 'focus'];
/** The layers each shadow composes (a 1px edge, then soft near and far layers), in the utility's order. */
const SHADOW_LAYERS = { xs: ['edge', 'near'], sm: ['edge', 'near', 'far'], md: ['edge', 'far'], lg: ['edge', 'far', 'near'], xl: ['edge', 'far', 'near'] };
const SHADOW_TOKENS = Object.entries(SHADOW_LAYERS).flatMap(([size, layers]) => [`shadow-${size}`, ...layers.map((layer) => `shadow-${size}-${layer}`)]);
const OTHER_TOKENS = [
    ...TONES.map((tone) => `tone-${tone}`), 'p', 'page-pattern', 'page-pattern-size', 'focus-shadow', 'focus-shadow-danger', 'backdrop', 'backdrop-blur',
    'radius', 'radius-check', 'font-sans', 'font-mono', 'font-display', 'font-brand', ...SHADOW_TOKENS,
];

/** Every innermost {…} block of a stylesheet: its selector (or at-rule) and its declarations in order. */
function rules(css) {
    const source = css.replace(/\/\*[\s\S]*?\*\//g, '');
    const found = [];
    const stack = [];
    let start = 0;
    for (let i = 0; i < source.length; i++) {
        if (source[i] === '{') {
            stack.push({ prelude: source.slice(start, i).trim(), open: i, nested: false });
            if (stack.length > 1) stack[stack.length - 2].nested = true;
            start = i + 1;
        } else if (source[i] === '}') {
            const block = stack.pop();
            if (block && !block.nested) {
                const body = source.slice(block.open + 1, i);
                found.push({
                    selectors: block.prelude.split(',').map((selector) => selector.trim()),
                    declarations: new Map([...body.matchAll(/(--[\w-]+):\s*([^;]+);/g)].map(([, name, value]) => [name, value.trim()])),
                });
            }
            start = i + 1;
        } else if (source[i] === ';' && !stack.at(-1)?.nested) {
            start = i + 1;
        }
    }

    return found;
}

const themeRules = rules(themesCss);
const ruleWith = (selector, name) => themeRules.filter((rule) => rule.selectors.includes(selector) && rule.declarations.has(name));
const toneBlocks = ruleWith(':root', '--ac-tone-primary').map((rule) => rule.declarations);
const lightBlock = toneBlocks[0];
const darkBlock = ruleWith("[data-theme$='dark']", '--ac-page')[0].declarations;
const borderedBlock = ruleWith(":root[data-skin='bordered']", '--ac-page')[0].declarations;

const overlay = (...maps) => new Map(maps.flatMap((map) => [...map]));
const scopes = {
    light: lightBlock,
    dark: overlay(lightBlock, darkBlock),
    'bordered light': overlay(lightBlock, borderedBlock),
    'bordered dark': overlay(lightBlock, darkBlock, borderedBlock),
};

// The 1.x kit's variables, as each theme and skin cascaded them on <html>: what is left of them in
// panel.css (the palette scales), and, frozen here, the ones the token pipeline replaced (the brand
// palette mixed from --color-brand-600, the primary fill, the backdrop, the fields' focus shadow,
// the checkbox frame, the shadows with their dark and bordered layer colours, the corner and the
// fonts; --color-dark is the dark tone of tokens.css now).
const panelRules = rules(panelCss);
const brand = (share, toward) => `color-mix(in srgb, var(--color-brand-600) ${share}%, ${toward})`;
const LEGACY = new Map([
    ['--color-dark', '#3f404d'],
    ['--color-brand-600', '#794dff'],
    ...[[50, 6], [100, 12], [200, 24], [300, 42], [400, 66], [500, 84]].map(([step, share]) => [`--color-brand-${step}`, brand(share, 'white')]),
    ...[[700, 86], [800, 70], [900, 55], [950, 38]].map(([step, share]) => [`--color-brand-${step}`, brand(share, 'black')]),
    ['--btn-primary-bg', 'var(--color-brand-600)'],
    ['--btn-primary-hover', brand(88, 'black')],
    ['--ui-backdrop', 'rgb(10 10 10 / 0.45)'],
    ['--ui-backdrop-blur', '2px'],
    ['--ui-focus-shadow', '0 0 #0000'],
    ['--ui-focus-shadow-error', '0 0 #0000'],
    ['--radius-sm', '0.25rem'],
    ['--font-sans', "var(--font-public-sans, 'Public Sans'), ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'"],
    ['--font-mono', "var(--font-dm-mono, 'DM Mono'), ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"],
    ['--font-brand', "var(--font-public-sans, 'Public Sans'), ui-sans-serif, system-ui, sans-serif"],
    ['--ui-control-border', 'color-mix(in srgb, var(--color-gray-500) 80%, white)'],
    // 1.x panel.css @theme: the shadow utilities, their layer colours in variables with the light values as fallbacks.
    ['--shadow-xs', '0 0 0 1px var(--shadow-edge-xs, transparent), 0 1px 2px 0 var(--shadow-near-xs, rgb(24 24 27 / 0.06))'],
    ['--shadow-sm', '0 0 0 1px var(--shadow-edge, rgb(24 24 27 / 0.05)), 0 1px 2px 0 var(--shadow-near, rgb(24 24 27 / 0.05)), 0 4px 14px 0 var(--shadow-far, rgb(24 24 27 / 0.04))'],
    ['--shadow-md', '0 0 0 1px var(--shadow-edge, rgb(24 24 27 / 0.05)), 0 6px 20px 0 var(--shadow-far-md, rgb(24 24 27 / 0.08))'],
    ['--shadow-lg', '0 0 0 1px var(--shadow-edge-lg, rgb(24 24 27 / 0.06)), 0 12px 32px 0 var(--shadow-far-lg, rgb(24 24 27 / 0.12)), 0 4px 10px 0 var(--shadow-near-lg, rgb(24 24 27 / 0.05))'],
    ['--shadow-xl', '0 0 0 1px var(--shadow-edge-xl, transparent), 0 24px 70px 0 var(--shadow-far-xl, rgb(24 24 27 / 0.16)), 0 4px 18px 0 var(--shadow-near-xl, rgb(24 24 27 / 0.08))'],
]);
// 1.x .dark: a light-toned hairline and deep black layers; the dark checkbox frame and backdrop.
const LEGACY_DARK = new Map([
    ['--ui-backdrop', 'rgb(0 0 0 / 0.72)'], ['--ui-backdrop-blur', '4px'], ['--ui-control-border', 'var(--color-zinc-500)'],
    ['--shadow-edge', 'var(--color-zinc-800)'], ['--shadow-edge-lg', 'var(--color-zinc-700)'], ['--shadow-edge-xl', 'var(--color-zinc-700)'],
    ['--shadow-near', 'transparent'], ['--shadow-far', 'rgb(0 0 0 / 0.22)'], ['--shadow-far-md', 'rgb(0 0 0 / 0.22)'], ['--shadow-far-lg', 'rgb(0 0 0 / 0.45)'],
    ['--shadow-near-lg', 'transparent'], ['--shadow-far-xl', 'rgb(0 0 0 / 0.45)'], ['--shadow-near-xl', 'transparent'],
]);
// 1.x :root[data-skin='bordered'] (and :root.dark[data-skin='bordered']): every shadow the 1px line.
const bordered = (edge) => new Map([
    ...['--shadow-edge', '--shadow-edge-xs', '--shadow-edge-lg', '--shadow-edge-xl'].map((name) => [name, edge]),
    ...['--shadow-near', '--shadow-near-xs', '--shadow-far', '--shadow-far-md', '--shadow-far-lg', '--shadow-near-lg', '--shadow-far-xl', '--shadow-near-xl'].map((name) => [name, 'transparent']),
]);
const palette = overlay(...['@theme', ':root'].flatMap((selector) => panelRules.filter((rule) => rule.selectors.length === 1 && rule.selectors[0] === selector).map((rule) => rule.declarations)));
// The palette scales of panel.css, then the frozen 1.x values over them (panel.css's own shadows read the tokens now).
const legacy = (...maps) => overlay(palette, LEGACY, ...maps);
const legacyScopes = {
    light: legacy(),
    dark: legacy(LEGACY_DARK),
    'bordered light': legacy(bordered('var(--color-gray-200)')),
    'bordered dark': legacy(LEGACY_DARK, bordered('var(--color-zinc-700)')),
};

/** The text between the parentheses that open at index open, and the index after them. */
function group(text, open) {
    let depth = 0;
    for (let i = open; i < text.length; i++) {
        if (text[i] === '(') depth++;
        else if (text[i] === ')' && --depth === 0) return [text.slice(open + 1, i), i + 1];
    }
    throw new Error(`Unbalanced: ${text}`);
}

/** Splits on the commas outside parentheses. */
function topLevel(text, separator = ',') {
    const parts = [];
    let depth = 0;
    let start = 0;
    for (let i = 0; i < text.length; i++) {
        if (text[i] === '(') depth++;
        else if (text[i] === ')') depth--;
        else if (text[i] === separator && depth === 0) {
            parts.push(text.slice(start, i).trim());
            start = i + 1;
        }
    }

    return [...parts, text.slice(start).trim()];
}

/** Replaces every var() in value with its value in scope (or its fallback), recursively. */
function substitute(value, scope, depth = 0) {
    assert.ok(depth < 20, `Cannot resolve ${value}`);
    const at = value.indexOf('var(');
    if (at < 0) return value.trim();
    const [inside, end] = group(value, at + 3);
    const [name, ...fallback] = topLevel(inside);
    const found = scope.get(name) ?? (fallback.length ? fallback.join(', ') : undefined);
    assert.ok(found !== undefined, `${name} has no value (in ${value})`);

    return substitute(value.slice(0, at) + substitute(found, scope, depth + 1) + value.slice(end), scope, depth + 1);
}

/** [r, g, b, alpha] (0-255, 0-1) of a colour value. */
function colour(value, scope, depth = 0) {
    assert.ok(depth < 20, `Cannot resolve ${value}`);
    value = value.trim();
    const named = { white: [255, 255, 255, 1], black: [0, 0, 0, 1], transparent: [0, 0, 0, 0] }[value];
    if (named) return named;
    const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (hex) {
        const digits = hex[1].length === 3 ? [...hex[1]].map((digit) => digit + digit).join('') : hex[1];
        return [0, 2, 4].map((i) => Number.parseInt(digits.slice(i, i + 2), 16)).concat(1);
    }
    const rgb = value.match(/^rgb\((\d+) (\d+) (\d+)(?: \/ ([\d.]+))?\)$/);
    if (rgb) return [Number(rgb[1]), Number(rgb[2]), Number(rgb[3]), rgb[4] === undefined ? 1 : Number(rgb[4])];
    if (value.startsWith('var(')) return colour(substitute(value, scope), scope, depth + 1);
    if (value.startsWith('color-mix(')) {
        const [space, first, second] = topLevel(group(value, value.indexOf('('))[0]);
        const [, a, share] = first.match(/^(.+?)\s+(\d+(?:\.\d+)?)%$/);
        const [top, bottom, p] = [colour(a, scope, depth + 1), colour(second, scope, depth + 1), Number(share) / 100];
        if (bottom[3] === 0) return [...top.slice(0, 3), top[3] * p];
        assert.equal(space, 'in srgb', `Only srgb mixes of two colours: ${value}`);
        assert.ok(top[3] === 1 && bottom[3] === 1, `Opaque srgb mixes only: ${value}`);
        return [0, 1, 2].map((i) => top[i] * p + bottom[i] * (1 - p)).concat(1);
    }
    throw new Error(`Unsupported colour value: ${value}`);
}

const over = (top, bottom) => [0, 1, 2].map((i) => top[i] * top[3] + bottom[i] * (1 - top[3])).concat(1);
const luminance = (rgb) => rgb.slice(0, 3)
    .map((channel) => Math.round(channel) / 255)
    .map((channel) => (channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4))
    .reduce((sum, channel, i) => sum + channel * [0.2126, 0.7152, 0.0722][i], 0);
const contrast = (a, b) => {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (light + 0.05) / (dark + 0.05);
};
const toHex = (rgba) => `#${rgba.slice(0, 3).map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('')}${rgba[3] === 1 ? '' : `/${Math.round(rgba[3] * 100)}`}`;
const token = (name, scope) => colour(`var(--ac-${name})`, scope);

test('every token is set in the light theme and resolves in the dark theme', () => {
    for (const name of [...COLOUR_ROLES, ...OTHER_TOKENS]) {
        assert.ok(lightBlock.has(`--ac-${name}`), `--ac-${name} in the light block`);
    }
    for (const [mode, scope] of Object.entries(scopes)) {
        for (const name of COLOUR_ROLES) {
            assert.doesNotThrow(() => token(name, scope), `--ac-${name} (${mode})`);
        }
    }
    // What changes in the dark theme is declared there; the rest is the same colour in both.
    for (const name of ['page', 'surface', 'field', 'line', 'ink', 'body', 'muted', 'p', 'primary', 'primary-ink', 'backdrop', 'shadow-sm-edge', 'shadow-sm-far', 'shadow-xl-edge', 'shadow-xl-far', ...TONES.filter((tone) => tone !== 'primary').flatMap((tone) => [`${tone}-ink`, `${tone}-tint`])]) {
        assert.ok(darkBlock.has(`--ac-${name}`), `--ac-${name} in the dark block`);
    }
});

test('every colour role is a Tailwind colour that reads its token on the element', () => {
    const theme = tokensCss.slice(tokensCss.indexOf('@theme inline {'));
    for (const name of COLOUR_ROLES) {
        assert.match(theme, new RegExp(`\\n\\s*--color-${name}: var\\(--ac-${name}\\);`), `--color-${name}`);
    }
    for (const [utility, name] of [['font-sans', 'font-sans'], ['font-mono', 'font-mono'], ['font-display', 'font-display'], ['font-brand', 'font-brand'], ...['sm', 'md', 'lg', 'xl', '2xl', '3xl'].map((step) => [`radius-${step}`, 'radius'])]) {
        assert.match(theme, new RegExp(`\\n\\s*--${utility}: var\\(--ac-${name}\\);`), `--${utility}`);
    }
    // panel.css no longer gives them values of its own.
    assert.doesNotMatch(panelCss.replace(/\/\*[\s\S]*?\*\//g, ''), /--(?:font-(?:sans|mono|brand)|radius-(?:sm|md|lg|xl|2xl|3xl)):/);
    // tokens.css owns the names: panel.css no longer declares the focus colour or the dark colour.
    assert.doesNotMatch(panelCss, /--color-(?:focus|dark):/);
    assert.match(panelCss, /@import '\.\/tokens\.css';\n@import '\.\/themes\.css';/, 'The kit entry imports the tokens.');
});

/** Plan 6.1, item 1: the contrast table, for the built-in light/dark pair and the bordered skin. */
test('the token contrast table holds in light, dark and the bordered skin', () => {
    const failures = [];
    for (const [mode, scope] of Object.entries(scopes)) {
        const get = (name) => token(name, scope);
        const surface = get('surface');
        const check = (what, text, background, minimum) => {
            const ground = over(background, surface);
            const ratio = contrast(over(text, ground), ground);
            if (ratio < minimum) failures.push(`${mode}: ${what} ${ratio.toFixed(2)}:1 < ${minimum}`);
        };

        for (const text of ['ink', 'body', 'dim', 'muted']) {
            for (const background of ['page', 'surface', 'field', 'sunken', 'hover']) check(`${text} on ${background}`, get(text), get(background), 4.5);
        }
        for (const tone of TONES) {
            check(`on-${tone} on ${tone}`, get(`on-${tone}`), get(tone), 4.5);
            check(`on-${tone} on ${tone}-hover`, get(`on-${tone}`), get(`${tone}-hover`), 4.5);
            for (const background of ['page', 'surface', 'field']) check(`${tone}-ink on ${background}`, get(`${tone}-ink`), get(background), 4.5);
            // The ink is the focus ring of the tone's buttons, which may sit on a sunken toolbar.
            check(`${tone}-ink on sunken`, get(`${tone}-ink`), get('sunken'), 3);
            for (const background of ['tint', 'soft']) check(`${tone}-tint-ink on ${tone}-${background}`, get(`${tone}-tint-ink`), get(`${tone}-${background}`), 4.5);
        }
        check('on-inverse on inverse', get('on-inverse'), get('inverse'), 4.5);
        for (const background of ['surface', 'field']) check(`control on ${background}`, get('control'), get(background), 3);
        for (const background of ['page', 'surface', 'field', 'sunken']) check(`focus on ${background}`, get('focus'), get(background), 3);
        check('danger-line on field', get('danger-line'), get('field'), 3);
        check('primary-line on surface', get('primary-line'), get('surface'), 3);
    }

    assert.deepEqual(failures, []);
});

/**
 * The focus ring of every acun:button variant (2px, 2px off its edge, so on whatever the button
 * sits on) is a token the table above promises 3:1 on the page, a surface, a field and a sunken
 * area: the focus token, or the ink of the variant's colour. The -line shades are decoration, not
 * a frame: the success, warning and info buttons' rings gave 1.75-2.21:1 and the white, ghost and
 * light buttons' line-hover 2.39-2.62:1 in the light theme.
 */
test('every button variant\'s focus ring is a token that stands out 3:1', () => {
    const view = read('../resources/views/components/ui/button.blade.php');
    const variants = Object.fromEntries([...view.slice(view.indexOf('$variants = ['), view.indexOf('$shadows = [')).matchAll(/^\s*'([\w-]+)' => '([^']*)',$/gm)].map(([, name, classes]) => [name, classes]));
    assert.equal(Object.keys(variants).length, 25);
    const failures = [];
    for (const [variant, classes] of Object.entries(variants)) {
        const rings = [...classes.matchAll(/(?:^| )focus-visible:outline-([a-z-]+)(?= |$)/g)].map(([, name]) => name);
        const tone = variant.replace(/^(?:outline|soft)-/, '');
        const expected = TONES.includes(tone) && tone !== 'primary' ? `${tone}-ink` : 'focus';
        if (rings.join() !== expected) failures.push(`${variant}: outline-${rings.join(', outline-')}, expected outline-${expected}`);
        for (const [mode, scope] of Object.entries(scopes)) {
            for (const background of ['page', 'surface', 'field', 'sunken']) {
                const ratio = contrast(token(expected, scope), token(background, scope));
                if (ratio < 3) failures.push(`${mode} ${variant}: ${expected} on ${background} ${ratio.toFixed(2)}:1`);
            }
        }
    }
    assert.deepEqual(failures, []);
});

/**
 * The built-in values are today's look: each role equals the palette shade the components use
 * now for it (light, then dark). Four are a step darker, so their text reaches 4.5:1 on every
 * background the contrast table names (today's 600 shades on the #fafafa page, gray-500 on gray-100);
 * none differs by more than 3 per channel.
 */
test('the built-in values are the colours of today\'s components', () => {
    // A solid button's hover: its fill with 12% black (the --btn-* variables of panel.css until step 5).
    const hover = (shade) => `color-mix(in srgb, var(--color-${shade}) 88%, black)`;
    const shades = {
        page: ['gray-50', 'zinc-950'], surface: ['#fff', 'zinc-900'], field: ['#fff', 'zinc-800'], 'field-hover': ['gray-50', 'zinc-700'],
        'field-disabled': ['gray-50', 'zinc-900'], sunken: ['gray-100', 'zinc-800'], hover: ['gray-100', 'zinc-800'], track: ['gray-200', 'zinc-700'],
        inverse: ['gray-900', 'zinc-700'], 'on-inverse': ['#fff', '#fff'], line: ['gray-200', 'zinc-700'], 'line-soft': ['gray-100', 'zinc-800'],
        'line-strong': ['gray-300', 'zinc-600'], 'line-hover': ['gray-400', 'zinc-500'], control: ['var(--ui-control-border)', 'var(--ui-control-border)'],
        ink: ['gray-900', 'zinc-100'], body: ['gray-700', 'zinc-300'], dim: ['gray-600', 'zinc-400'], muted: ['gray-500', 'zinc-400'], faint: ['gray-400', 'zinc-500'],

        primary: ['var(--btn-primary-bg)', 'var(--btn-primary-bg)'], 'primary-hover': ['var(--btn-primary-hover)', 'var(--btn-primary-hover)'],
        'on-primary': ['#fff', '#fff'], 'primary-ink': ['brand-600', 'brand-400'], 'primary-line': ['brand-500', 'brand-400'],
        'primary-tint': ['brand-50', 'brand-500/10'], 'primary-soft': ['brand-100', 'brand-500/15'], 'primary-tint-ink': ['brand-700', 'brand-300'],
        focus: ['brand-600', 'brand-600'],

        // Today's solid button fills: the palette's solid shade, hovered with 12% black (the dark button is a grey in the dark theme).
        secondary: ['secondary-500', 'secondary-500'], 'secondary-hover': [hover('secondary-500'), hover('secondary-500')],
        'secondary-ink': ['secondary-600', 'secondary-300'], 'secondary-line': ['secondary-500', 'secondary-400'], 'secondary-tint': ['secondary-100', 'secondary-500/15'],
        'secondary-soft': ['secondary-100', 'secondary-500/15'], 'secondary-tint-ink': ['secondary-700', 'secondary-300'],

        dark: ['#3f404d', 'zinc-700'], 'dark-hover': [hover('dark'), 'zinc-600'], 'on-dark': ['#fff', '#fff'],
        'dark-ink': ['#3f404d', 'zinc-300'], 'dark-line': ['#3f404d', 'zinc-300'], 'dark-tint': ['#3f404d/10', 'zinc-800'], 'dark-soft': ['#3f404d/10', 'zinc-700'],
        'dark-tint-ink': ['#3f404d', 'zinc-200'],

        accent: ['accent-600', 'accent-600'], 'accent-ink': ['accent-600', 'accent-500'], 'accent-line': ['accent-500', 'accent-500'],
        'accent-tint': ['accent-50', 'accent-500/10'], 'accent-soft': ['accent-100', 'accent-500/15'],
    };
    for (const [tone, palette, fill] of [['success', 'emerald', 'emerald-solid'], ['info', 'sky', 'sky-solid'], ['warning', 'amber', 'amber-solid'], ['danger', 'red', 'red-600']]) {
        Object.assign(shades, {
            [tone]: [fill, fill], [`${tone}-hover`]: [hover(fill), hover(fill)], [`on-${tone}`]: ['#fff', '#fff'],
            [`${tone}-ink`]: [`${palette}-600`, `${palette}-400`], [`${tone}-line`]: [`${palette}-500`, `${palette}-400`],
            [`${tone}-tint`]: [`${palette}-50`, `${palette}-500/10`], [`${tone}-soft`]: [`${palette}-100`, `${palette}-500/15`],
            [`${tone}-tint-ink`]: [`${palette}-700`, `${palette}-300`],
        });
    }
    // A step darker than today, for 4.5:1 (light theme): role → [today's shade, the token's colour].
    const darker = { muted: ['gray-500', '#6f6f79'], 'success-ink': ['emerald-600', '#008532'], 'danger-ink': ['red-600', '#d53737'], 'accent-ink': ['accent-600', '#3370d4'] };
    const lighter = { 'accent-ink': ['accent-500', '#4387f6'], 'accent-line': ['accent-500', '#4387f6'] };

    const today = (shade, scope) => {
        if (shade.startsWith('color-mix(')) return colour(shade, scope);
        const [, name, alpha] = shade.match(/^(.+?)(?:\/(\d+))?$/);
        const value = name.startsWith('#') || name.startsWith('var(') ? name : `var(--color-${name})`;
        const rgba = colour(value, scope);
        return alpha ? [...rgba.slice(0, 3), Number(alpha) / 100] : rgba;
    };
    const mismatches = [];
    for (const [i, mode] of ['light', 'dark'].entries()) {
        // The default theme, without a colour of the user's.
        const scope = overlay(legacyScopes[mode], scopes[mode]);
        for (const [role, pair] of Object.entries(shades)) {
            const exception = (i === 0 ? darker : lighter)[role];
            const expected = toHex(exception ? colour(exception[1], scope) : today(pair[i], legacyScopes[mode]));
            const actual = toHex(token(role, scope));
            if (actual !== expected) mismatches.push(`${mode} ${role}: ${actual}, today ${expected}`);
            if (exception) {
                const was = today(exception[0], legacyScopes[mode]);
                const step = Math.max(...[0, 1, 2].map((channel) => Math.abs(Math.round(was[channel]) - Math.round(token(role, scope)[channel]))));
                // The accent was no tone before (no component paints accent-600 text); the others are a small step.
                if (!role.startsWith('accent')) assert.ok(step <= 3, `${mode} ${role}: ${step} per channel from ${exception[0]}`);
            }
        }
    }
    assert.deepEqual(mismatches, []);
});

test('the shadows, backdrop, focus shadow, corner and fonts are the 1.x values', () => {
    // A shadow's layers with their colours resolved; fully transparent layers paint nothing.
    const layers = (value, scope) => topLevel(substitute(value, scope))
        .filter((layer) => !/(?:^|\s)transparent$/.test(layer))
        .map((layer) => layer.replace(/#[0-9a-f]{6}|rgb\([^)]*\)|white|black/gi, (match) => toHex(colour(match, scope))));
    for (const mode of Object.keys(scopes)) {
        const scope = overlay(legacyScopes[mode], scopes[mode]);
        for (const size of ['xs', 'sm', 'md', 'lg', 'xl']) {
            assert.deepEqual(layers(`var(--ac-shadow-${size})`, scope), layers(`var(--shadow-${size})`, legacyScopes[mode]), `${mode} shadow-${size}`);
        }
        assert.equal(substitute('var(--ac-backdrop) var(--ac-backdrop-blur)', scopes[mode]), substitute('var(--ui-backdrop) var(--ui-backdrop-blur)', legacyScopes[mode]), `${mode} backdrop`);
        // The page: the layouts' bg-gray-50 / dark:bg-zinc-950; the bordered skin paints it white / zinc-900.
        const page = { light: 'var(--color-gray-50)', dark: 'var(--color-zinc-950)', 'bordered light': '#fff', 'bordered dark': 'var(--color-zinc-900)' }[mode];
        assert.equal(toHex(token('page', scopes[mode])), toHex(colour(page, legacyScopes[mode])), `${mode} page`);
    }
    const light = scopes.light;
    const kit = legacyScopes.light;
    assert.equal(substitute('var(--ac-focus-shadow)', light), substitute('var(--ui-focus-shadow)', kit));
    assert.equal(substitute('var(--ac-focus-shadow-danger)', light), substitute('var(--ui-focus-shadow-error)', kit));
    for (const [name, today] of [['radius', 'radius-sm'], ['radius-check', 'radius-sm'], ['font-sans', 'font-sans'], ['font-mono', 'font-mono'], ['font-display', 'font-sans'], ['font-brand', 'font-brand']]) {
        assert.equal(substitute(`var(--ac-${name})`, light), substitute(`var(--${today})`, kit), `--ac-${name}`);
    }
});

/**
 * One source for the shadows: the shadow-* utilities (panel.css @theme) and --ac-shadow-* compose the
 * same layer tokens, so a theme, a dark section and the bordered skin change both by setting the
 * layers' colours. The utilities stay composed in place (not var(--ac-shadow-sm)), so Tailwind can
 * colour each layer for a colour modifier (shadow-black/40).
 */
test('the shadow utilities compose the theme\'s shadow layers, as --ac-shadow-* does', () => {
    const theme = panelRules.find((rule) => rule.selectors[0] === '@theme').declarations;
    for (const [mode, scope] of Object.entries(scopes)) {
        for (const size of Object.keys(SHADOW_LAYERS)) {
            assert.equal(substitute(theme.get(`--shadow-${size}`), scope), substitute(`var(--ac-shadow-${size})`, scope), `${mode} shadow-${size}`);
        }
    }
    assert.equal(theme.get('--shadow'), theme.get('--shadow-sm'));
    for (const [size, layers] of Object.entries(SHADOW_LAYERS)) {
        assert.deepEqual([...theme.get(`--shadow-${size}`).matchAll(/var\((--[\w-]+)\)/g)].map(([, name]) => name), layers.map((layer) => `--ac-shadow-${size}-${layer}`), `shadow-${size}`);
    }
    // The dark theme and the bordered skin set layers, not whole shadows.
    for (const block of [darkBlock, borderedBlock]) {
        assert.ok(![...block.keys()].some((name) => /^--ac-shadow-(?:xs|sm|md|lg|xl)$/.test(name)));
    }
});

/** The user's colour as js/appearance/theme.js writes it on <html> (applyColor). */
const userVariables = (color, dark = null) => {
    const shades = brandShades(color);
    const own = dark ? brandShades(dark) : null;
    return new Map([
        ['--ac-user-fill', shades.fill], ['--ac-user-fill-hover', shades.hover], ['--ac-user-on-fill', shades.onFill],
        ['--ac-user-tone', shades.tone], ['--ac-user-tone-dark', shades.toneDark],
        ...(own ? [['--ac-user-fill-dark', own.fill], ['--ac-user-fill-hover-dark', own.hover], ['--ac-user-on-fill-dark', own.onFill]] : []),
    ]);
};

/** The primary roles the blocks derive from a fill, its hover and text, and a tone (light) or dark tone (dark). */
const primaryRoles = (mode, { fill, hover, onFill, tone }) => (mode === 'light' ? {
    p: tone, primary: fill, 'primary-hover': hover, 'on-primary': onFill, focus: tone, 'primary-ink': tone,
    'primary-line': `color-mix(in srgb, ${tone} 84%, white)`, 'primary-tint': `color-mix(in srgb, ${tone} 6%, white)`,
    'primary-soft': `color-mix(in srgb, ${tone} 12%, white)`, 'primary-tint-ink': `color-mix(in srgb, ${tone} 86%, black)`,
} : {
    p: tone, primary: fill, 'primary-hover': hover, 'on-primary': onFill, focus: tone, 'primary-ink': `color-mix(in srgb, ${tone} 66%, white)`,
    'primary-line': `color-mix(in srgb, ${tone} 66%, white)`, 'primary-tint': `color-mix(in oklab, color-mix(in srgb, ${tone} 84%, white) 10%, transparent)`,
    'primary-soft': `color-mix(in oklab, color-mix(in srgb, ${tone} 84%, white) 15%, transparent)`, 'primary-tint-ink': `color-mix(in srgb, ${tone} 42%, white)`,
});

/** What each primary role should be for a colour of the user's (null: the theme's own, #794dff), light or dark. */
function expectedPrimary(mode, color = null, dark = null) {
    if (!color) {
        const own = { fill: '#794dff', hover: 'color-mix(in srgb, #794dff 88%, black)', onFill: '#fff', tone: '#794dff' };
        return primaryRoles(mode, own);
    }
    const shades = brandShades(color);
    const own = dark ? brandShades(dark) : shades;
    return primaryRoles(mode, mode === 'light' ? shades : { fill: own.fill, hover: own.hover, onFill: own.onFill, tone: shades.toneDark });
}

/**
 * The user's colour keeps the table's primary promises in every block: the presets and custom
 * colours (a yellow, an orange, a navy) through js/appearance/theme.js brandShades. Its text tone
 * reads 4.5:1 on the bare light page (#fafafa) too: measured against the surface alone, the green,
 * red, yellow and cyan presets' gave 4.35-4.43:1 there and #ffd400's 4.32:1.
 */
test('the user\'s colour keeps the primary roles\' contrast in light, dark and the bordered skin', () => {
    const failures = [];
    for (const [color, dark] of [['#008933'], ['#d83838'], ['#9f6b13'], ['#038197'], ['#6b7280'], ['#3f404d', '#474747'], ['#ffd400'], ['#f9c74f'], ['#ff6a00'], ['#1e3a8a']]) {
        for (const [mode, base] of Object.entries(scopes)) {
            const scope = overlay(base, userVariables(color, dark));
            const get = (name) => token(name, scope);
            const check = (what, text, background, minimum) => {
                const ground = over(background, get('surface'));
                const ratio = contrast(over(text, ground), ground);
                if (ratio < minimum) failures.push(`${color} ${mode}: ${what} ${ratio.toFixed(2)}:1 < ${minimum}`);
            };
            check('on-primary on primary', get('on-primary'), get('primary'), 4.5);
            check('on-primary on primary-hover', get('on-primary'), get('primary-hover'), 4.5);
            for (const background of ['page', 'surface', 'field']) check(`primary-ink on ${background}`, get('primary-ink'), get(background), 4.5);
            for (const background of ['tint', 'soft']) check(`primary-tint-ink on primary-${background}`, get('primary-tint-ink'), get(`primary-${background}`), 4.5);
            for (const background of ['page', 'surface', 'field', 'sunken']) check(`focus on ${background}`, get('focus'), get(background), 3);
        }
    }
    assert.deepEqual(failures, []);
});

/**
 * No bridge: the light and dark blocks are selected with data-theme alone, and the primary roles read
 * the user's variables first, then the theme's own colour. For the default theme that is #794dff,
 * the button's #6a44e0 hover and white text, as in 1.x.
 */
test('the primary roles read the user\'s colour first, then the theme\'s own', () => {
    assert.equal(toneBlocks.length, 1, 'One block sets the tone inputs.');
    assert.deepEqual(ruleWith('.dark', '--ac-page'), [], 'The .dark class selects no block.');
    const rulesOnly = themesCss.replace(/\/\*[\s\S]*?\*\//g, '');
    assert.doesNotMatch(rulesOnly, /--btn-|--ui-|--color-brand-|\.dark\b/, 'Nothing of the 1.x runtime.');

    for (const mode of ['light', 'dark']) {
        const values = Object.fromEntries(['tone-primary', 'p', 'primary', 'primary-hover', 'on-primary', 'focus'].map((name) => [name, toHex(token(name, scopes[mode]))]));
        assert.deepEqual(values, { 'tone-primary': '#794dff', p: '#794dff', primary: '#794dff', 'primary-hover': '#6a44e0', 'on-primary': '#ffffff', focus: '#794dff' }, mode);
        assert.equal(substitute('var(--ac-radius)', scopes[mode]), '0.25rem');
    }

    const mismatches = [];
    for (const [color, dark] of [[null], ['#008933'], ['#3f404d', '#474747'], ['#ffd400'], ['#1e3a8a']]) {
        const user = color ? userVariables(color, dark) : new Map();
        for (const mode of ['light', 'dark']) {
            const scope = overlay(scopes[mode], user);
            for (const [role, expected] of Object.entries(expectedPrimary(mode, color, dark))) {
                const [actual, wanted] = [toHex(token(role, scope)), toHex(colour(expected, scope))];
                if (actual !== wanted) mismatches.push(`${color ?? 'default'} ${mode} ${role}: ${actual}, expected ${wanted}`);
            }
        }
    }
    assert.deepEqual(mismatches, []);

    // A preset gives 1.x's values: green's fill, hover and text are the palette's solid green, and so
    // is its brand-600 in the dark theme. In the light theme its text tone is a step darker (#008632),
    // as the built-in success ink is: the fill reads 4.35:1 on the #fafafa page.
    const green = overlay(scopes.light, userVariables('#008933'));
    assert.deepEqual(['primary', 'primary-hover', 'on-primary', 'p'].map((role) => toHex(token(role, green))), ['#008933', '#00792d', '#ffffff', '#008632']);
    assert.equal(toHex(token('p', overlay(scopes.dark, userVariables('#008933')))), '#008933');
});

test('the PRO stylesheet reads the tokens, without fallbacks of its own', () => {
    const declared = new Set([...lightBlock.keys(), ...darkBlock.keys()]);
    const read = [...proCss.matchAll(/var\((--ac-[\w-]+)(,[^)]*)?\)/g)];
    assert.ok(read.length > 20);
    for (const [, name, fallback] of read) {
        if (name.startsWith('--ac-modal-')) continue;
        assert.ok(declared.has(name), `${name} is a token`);
        assert.equal(fallback, undefined, `${name} has no fallback`);
    }
    assert.doesNotMatch(proCss, /--ui-backdrop|--ac-(?:surface-subtle|border|text|radius-md|radius-lg|space-\d)\b/);
});

/**
 * Tailwind writes a shadow utility whose theme value is a variable as --tw-shadow: var(…); a
 * shadow colour (shadow-black/40) then has nothing to colour. That is why shadow-* composes its
 * layers in panel.css (each layer's colour a token) while the colour modifiers are in use.
 */
test('a shadow utility can read a token, without its colour modifier', async () => {
    const { compile } = await import('@tailwindcss/node');
    const compiler = await compile('@import "tailwindcss/utilities";\n@theme inline { --shadow-token: var(--ac-shadow-sm); --color-ink: var(--ac-ink); }', { base: new URL('.', import.meta.url).pathname, onDependency() {} });
    const css = compiler.build(['shadow-token', 'shadow-ink']);
    assert.match(css, /\.shadow-token \{\s*--tw-shadow: var\(--ac-shadow-sm\);/);
    assert.match(css, /\.shadow-ink \{[^}]*--tw-shadow-color: var\(--ac-ink\)/);
});

/**
 * In Chrome: the user's colour reaches every block and every section. The variables applyColor
 * writes on <html> (the presets, the dark preset with its own dark fill, a custom yellow and navy),
 * data-theme light or dark on <html>, the semi-dark menu (data-theme="dark") and a light island in
 * it (data-theme="light"): each primary role is what the pipeline promises, and application code's
 * brand-* utilities follow the element's theme too.
 */
test('the user\'s colour reaches every block and section: presets, custom colours, the semi-dark menu, a light island', { skip: !chrome && 'no Chrome' }, async () => {
    const colours = [[null], ['#008933'], ['#d83838'], ['#9f6b13'], ['#038197'], ['#6b7280'], ['#3f404d', '#474747'], ['#ffd400'], ['#1e3a8a']];
    const cases = colours.map(([color, dark]) => ({
        name: color ?? 'default',
        user: Object.fromEntries(color ? userVariables(color, dark) : []),
        light: expectedPrimary('light', color, dark),
        dark: expectedPrimary('dark', color, dark),
    }));
    // brand-* (application code): the element's tone, in the 1.x shades of it.
    const body = `<i id="page" class="text-brand-600 bg-brand-50"></i><aside data-theme="dark"><i id="menu" class="text-brand-600 bg-brand-50"></i><div data-theme="light"><i id="island" class="text-brand-600 bg-brand-50"></i></div></aside>`;
    const result = inChrome({ css: await stylesheet(body), body, measure: `
        const cases = ${JSON.stringify(cases)};
        const root = document.documentElement;
        const probe = (element, value) => { element.style.color = 'red'; element.style.color = value; const colour = rgba(getComputedStyle(element).color).join(); element.style.color = ''; return colour; };
        const mismatches = [];
        let compared = 0;
        for (const { name, user, light, dark } of cases) {
            root.removeAttribute('style');
            Object.entries(user).forEach(([property, value]) => root.style.setProperty(property, value));
            for (const mode of ['light', 'dark']) {
                root.dataset.theme = mode;
                // The menu is dark in both modes, the island in it light; the page follows the mode.
                for (const [where, expected] of [['page', mode === 'dark' ? dark : light], ['menu', dark], ['island', light]]) {
                    const element = document.getElementById(where);
                    for (const [role, value] of Object.entries(expected)) {
                        compared++;
                        const [token, wanted] = [probe(element, 'var(--ac-' + role + ')'), probe(element, value)];
                        if (token !== wanted) mismatches.push(name + ' ' + mode + ' ' + where + ' ' + role + ': ' + token + ' vs ' + wanted);
                    }
                    const tone = expected.p;
                    const brand = [rgba(getComputedStyle(element).color).join(), rgba(getComputedStyle(element).backgroundColor).join()];
                    const wanted = [probe(element, tone), probe(element, 'color-mix(in srgb, ' + tone + ' 6%, white)')];
                    if (brand.join('|') !== wanted.join('|')) mismatches.push(name + ' ' + mode + ' ' + where + ' brand-600/brand-50: ' + brand.join('|') + ' vs ' + wanted.join('|'));
                }
            }
        }
        return { compared, mismatches };
    ` });

    assert.deepEqual(result.mismatches, []);
    assert.equal(result.compared, cases.length * 2 * 3 * Object.keys(cases[0].light).length);
});
