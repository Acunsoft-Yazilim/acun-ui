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
// - the tokens carry today's look: the values equal the palette the components use now, and the
//   bridge (the last block of themes.css) gives the primary colour of today's runtime: the
//   presets, a custom colour and the semi-dark menu (in Chrome, where one is installed);
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
const OTHER_TOKENS = [
    ...TONES.map((tone) => `tone-${tone}`), 'p', 'page-pattern', 'page-pattern-size', 'focus-shadow', 'focus-shadow-danger', 'backdrop', 'backdrop-blur',
    'radius', 'radius-check', 'font-sans', 'font-mono', 'font-display', 'font-brand', 'shadow-xs', 'shadow-sm', 'shadow-md', 'shadow-lg', 'shadow-xl',
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
const [lightBlock, bridge] = ruleWith(':root', '--ac-tone-primary').map((rule) => rule.declarations);
const darkBlock = ruleWith("[data-theme$='dark']", '--ac-page')[0].declarations;
const borderedBlock = ruleWith(":root[data-skin='bordered']", '--ac-page')[0].declarations;

const overlay = (...maps) => new Map(maps.flatMap((map) => [...map]));
const scopes = {
    light: lightBlock,
    dark: overlay(lightBlock, darkBlock),
    'bordered light': overlay(lightBlock, borderedBlock),
    'bordered dark': overlay(lightBlock, darkBlock, borderedBlock),
};

// Today's kit variables (panel.css), as each theme and skin cascades them on <html>.
const panelRules = rules(panelCss);
// --color-dark was #3f404d in panel.css; tokens.css now owns the name (the dark tone, --ac-dark).
const legacy = (...selectors) => overlay(new Map([['--color-dark', '#3f404d']]), ...selectors.flatMap((selector) => panelRules.filter((rule) => rule.selectors.length === 1 && rule.selectors[0] === selector).map((rule) => rule.declarations)));
const legacyScopes = {
    light: legacy('@theme', ':root'),
    dark: legacy('@theme', ':root', '.dark', ':root.dark'),
    'bordered light': legacy('@theme', ':root', ":root[data-skin='bordered']"),
    'bordered dark': legacy('@theme', ':root', '.dark', ':root.dark', ":root[data-skin='bordered']", ":root.dark[data-skin='bordered']"),
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
    for (const name of ['page', 'surface', 'field', 'line', 'ink', 'body', 'muted', 'p', 'primary', 'primary-ink', 'backdrop', 'shadow-sm', 'shadow-xl', ...TONES.filter((tone) => tone !== 'primary').flatMap((tone) => [`${tone}-ink`, `${tone}-tint`])]) {
        assert.ok(darkBlock.has(`--ac-${name}`), `--ac-${name} in the dark block`);
    }
});

test('every colour role is a Tailwind colour that reads its token on the element', () => {
    const theme = tokensCss.slice(tokensCss.indexOf('@theme inline {'));
    for (const name of COLOUR_ROLES) {
        assert.match(theme, new RegExp(`\\n\\s*--color-${name}: var\\(--ac-${name}\\);`), `--color-${name}`);
    }
    assert.match(theme, /--font-display: var\(--ac-font-display\);/);
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
 * The built-in values are today's look: each role equals the palette shade the components use
 * now for it (light, then dark). Four are a step darker, so their text reaches 4.5:1 on every
 * background the contrast table names (today's 600 shades on the #fafafa page, gray-500 on gray-100);
 * none differs by more than 3 per channel.
 */
test('the built-in values are the colours of today\'s components', () => {
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

        secondary: ['var(--btn-secondary-bg)', 'var(--btn-secondary-bg)'], 'secondary-hover': ['var(--btn-secondary-hover)', 'var(--btn-secondary-hover)'],
        'secondary-ink': ['secondary-600', 'secondary-300'], 'secondary-line': ['secondary-500', 'secondary-400'], 'secondary-tint': ['secondary-100', 'secondary-500/15'],
        'secondary-soft': ['secondary-100', 'secondary-500/15'], 'secondary-tint-ink': ['secondary-700', 'secondary-300'],

        dark: ['var(--btn-dark-bg)', 'var(--btn-dark-bg)'], 'dark-hover': ['var(--btn-dark-hover)', 'var(--btn-dark-hover)'], 'on-dark': ['#fff', '#fff'],
        'dark-ink': ['#3f404d', 'zinc-300'], 'dark-line': ['#3f404d', 'zinc-300'], 'dark-tint': ['#3f404d/10', 'zinc-800'], 'dark-soft': ['#3f404d/10', 'zinc-700'],
        'dark-tint-ink': ['#3f404d', 'zinc-200'],

        accent: ['accent-600', 'accent-600'], 'accent-ink': ['accent-600', 'accent-500'], 'accent-line': ['accent-500', 'accent-500'],
        'accent-tint': ['accent-50', 'accent-500/10'], 'accent-soft': ['accent-100', 'accent-500/15'],
    };
    for (const [tone, palette, fill] of [['success', 'emerald', 'emerald-solid'], ['info', 'sky', 'sky-solid'], ['warning', 'amber', 'amber-solid'], ['danger', 'red', 'red-600']]) {
        Object.assign(shades, {
            [tone]: [fill, fill], [`${tone}-hover`]: [`var(--btn-${tone}-hover)`, `var(--btn-${tone}-hover)`], [`on-${tone}`]: ['#fff', '#fff'],
            [`${tone}-ink`]: [`${palette}-600`, `${palette}-400`], [`${tone}-line`]: [`${palette}-500`, `${palette}-400`],
            [`${tone}-tint`]: [`${palette}-50`, `${palette}-500/10`], [`${tone}-soft`]: [`${palette}-100`, `${palette}-500/15`],
            [`${tone}-tint-ink`]: [`${palette}-700`, `${palette}-300`],
        });
    }
    // A step darker than today, for 4.5:1 (light theme): role → [today's shade, the token's colour].
    const darker = { muted: ['gray-500', '#6f6f79'], 'success-ink': ['emerald-600', '#008532'], 'danger-ink': ['red-600', '#d53737'], 'accent-ink': ['accent-600', '#3370d4'] };
    const lighter = { 'accent-ink': ['accent-500', '#4387f6'], 'accent-line': ['accent-500', '#4387f6'] };

    const today = (shade, scope) => {
        const [, name, alpha] = shade.match(/^(.+?)(?:\/(\d+))?$/);
        const value = name.startsWith('#') || name.startsWith('var(') ? name : `var(--color-${name})`;
        const rgba = colour(value, scope);
        return alpha ? [...rgba.slice(0, 3), Number(alpha) / 100] : rgba;
    };
    const mismatches = [];
    for (const [i, mode] of ['light', 'dark'].entries()) {
        // The bridge on: the primary colour comes from today's variables (the default theme).
        const scope = overlay(legacyScopes[mode], scopes[mode], bridge);
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
        // Built in, without the bridge: the same colours for the default theme.
        for (const role of ['primary', 'primary-hover', 'on-primary', 'primary-ink', 'primary-tint', 'focus']) {
            assert.equal(toHex(token(role, scopes[mode])), toHex(token(role, scope)), `${mode} ${role} without the bridge`);
        }
    }
    assert.deepEqual(mismatches, []);
});

test('the shadows, backdrop, focus shadow, corner and fonts are today\'s values', () => {
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
 * The bridge: until the runtime writes data-theme, the .dark class marks the dark theme (on
 * <html> and on the semi-dark menu), and the primary colour and the other runtime-driven values
 * come from today's variables. For the default theme it gives #794dff, the button's #6a44e0
 * hover and white text, whatever the built-in blocks say.
 */
test('the bridge gives today\'s primary colour for the default theme', () => {
    for (const block of [lightBlock, darkBlock]) assert.ok(ruleWith('.dark', '--ac-page').some((rule) => rule.declarations === block), 'The light and the dark block match .dark.');
    assert.equal(bridge.get('--ac-tone-primary'), 'var(--color-brand-600)');
    assert.equal(bridge.get('--ac-primary'), 'var(--btn-primary-bg)');

    for (const mode of ['light', 'dark']) {
        const scope = overlay(legacyScopes[mode], scopes[mode], bridge);
        const values = Object.fromEntries(['tone-primary', 'p', 'primary', 'primary-hover', 'on-primary', 'focus'].map((name) => [name, toHex(token(name, scope))]));
        assert.deepEqual(values, { 'tone-primary': '#794dff', p: '#794dff', primary: '#794dff', 'primary-hover': '#6a44e0', 'on-primary': '#ffffff', focus: '#794dff' }, mode);
        assert.equal(substitute('var(--ac-radius)', scope), '0.25rem');
    }
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
 * shadow colour (shadow-black/40) then has nothing to colour. That is why shadow-* keeps its
 * composed panel.css values while the colour modifiers are in use.
 */
test('a shadow utility can read a token, without its colour modifier', async () => {
    const { compile } = await import('@tailwindcss/node');
    const compiler = await compile('@import "tailwindcss/utilities";\n@theme inline { --shadow-token: var(--ac-shadow-sm); --color-ink: var(--ac-ink); }', { base: new URL('.', import.meta.url).pathname, onDependency() {} });
    const css = compiler.build(['shadow-token', 'shadow-ink']);
    assert.match(css, /\.shadow-token \{\s*--tw-shadow: var\(--ac-shadow-sm\);/);
    assert.match(css, /\.shadow-ink \{[^}]*--tw-shadow-color: var\(--ac-ink\)/);
});

/**
 * In Chrome, with today's runtime: the presets, a custom colour (the shades theme.js writes) and
 * the semi-dark menu. On the page and in the menu (.panel-sidebar.dark in the light theme), each
 * primary role equals the variable the components read today, in the light and the dark theme.
 */
test('the bridge follows today\'s runtime: presets, a custom colour and the semi-dark menu', { skip: !chrome && 'no Chrome' }, async () => {
    const custom = (color) => {
        const shades = brandShades(color);
        return {
            '--brand-custom-600': shades.brand600, '--brand-custom-600-dark': shades.brand600Dark, '--btn-primary-bg': color, '--btn-primary-border': color,
            '--btn-primary-hover': shades.hover, '--btn-primary-hover-border': shades.hover, '--btn-primary-shadow': `color-mix(in srgb, ${color} 90%, transparent)`,
            '--btn-primary-color': shades.text, '--btn-primary-hover-color': shades.hoverText,
        };
    };
    const colours = [['default', {}], ...['green', 'red', 'yellow', 'blue', 'gray', 'dark'].map((name) => [name, {}]), ['custom', custom('#ffd400')], ['custom', custom('#1e3a8a')]];
    const light = { 'p': 'var(--color-brand-600)', primary: 'var(--btn-primary-bg)', 'primary-hover': 'var(--btn-primary-hover)', 'on-primary': 'var(--btn-primary-color, #fff)', focus: 'var(--ui-focus-ring, var(--color-brand-600))', 'primary-ink': 'var(--color-brand-600)', 'primary-line': 'var(--color-brand-500)', 'primary-tint': 'var(--color-brand-50)', 'primary-soft': 'var(--color-brand-100)', 'primary-tint-ink': 'var(--color-brand-700)', surface: '#fff', line: 'var(--color-gray-200)', ink: 'var(--color-gray-900)' };
    const dark = { ...light, 'primary-ink': 'var(--color-brand-400)', 'primary-line': 'var(--color-brand-400)', 'primary-tint': 'color-mix(in oklab, var(--color-brand-500) 10%, transparent)', 'primary-soft': 'color-mix(in oklab, var(--color-brand-500) 15%, transparent)', 'primary-tint-ink': 'var(--color-brand-300)', surface: 'var(--color-zinc-900)', line: 'var(--color-zinc-700)', ink: 'var(--color-zinc-100)' };
    // The palette variables the probes read are written by their utilities.
    const body = `<i id="page"></i><aside class="panel-sidebar dark"><i id="menu"></i></aside><div class="hidden bg-brand-50 bg-brand-100 bg-brand-300 bg-brand-400 bg-brand-500 bg-brand-600 bg-brand-700 bg-gray-200 bg-gray-900 bg-zinc-100 bg-zinc-700 bg-zinc-900"></div>`;
    const result = inChrome({ css: await stylesheet(body), body, measure: `
        const colours = ${JSON.stringify(colours)}, light = ${JSON.stringify(light)}, dark = ${JSON.stringify(dark)};
        const root = document.documentElement;
        const probe = (element, value) => { element.style.color = 'red'; element.style.color = value; return rgba(getComputedStyle(element).color).join(); };
        const mismatches = [];
        let compared = 0;
        for (const [name, properties] of colours) {
            root.removeAttribute('style');
            root.dataset.brandColor = name;
            Object.entries(properties).forEach(([property, value]) => root.style.setProperty(property, value));
            for (const mode of ['light', 'dark']) {
                root.classList.toggle('dark', mode === 'dark');
                // The menu is dark in both themes; the page follows the theme.
                for (const [where, expected] of [['page', mode === 'dark' ? dark : light], ['menu', dark]]) {
                    const element = document.getElementById(where);
                    for (const [role, today] of Object.entries(expected)) {
                        compared++;
                        const [token, now] = [probe(element, 'var(--ac-' + role + ')'), probe(element, today)];
                        if (token !== now) mismatches.push(name + (properties['--btn-primary-bg'] ?? '') + ' ' + mode + ' ' + where + ' ' + role + ': ' + token + ' vs ' + now);
                    }
                }
            }
        }
        return { compared, mismatches };
    ` });

    assert.deepEqual(result.mismatches, []);
    assert.equal(result.compared, colours.length * 2 * 2 * Object.keys(light).length);
});
