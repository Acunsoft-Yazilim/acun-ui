import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Reads the palette from css/panel.css and checks the promise in its comment: white text on
// the solid fills (buttons, badges, toasts) and on their hover reaches 4.5:1 (WCAG AA for
// normal text: the components' text is 12-16px, and bold text only counts as large from
// 18.66px); text on white reaches 4.5:1. Values are #rrggbb or
// color-mix(in srgb, <value> N%, black|white), resolved the way the browser mixes them.
const cssDirectory = path.join(path.dirname(fileURLToPath(import.meta.url)), '../css');
const css = fs.readFileSync(path.join(cssDirectory, 'panel.css'), 'utf8');
const rootBlock = css.slice(css.indexOf('@theme'));
const declarations = new Map();

// panel.css first; then the tokens (--color-dark is the dark tone of tokens.css, --ac-dark, whose
// first value in themes.css is the light theme's).
const tokens = ['tokens.css', 'themes.css'].map((file) => fs.readFileSync(path.join(cssDirectory, file), 'utf8')).join('\n');
for (const [, name, value] of `${rootBlock}\n${tokens}`.matchAll(/(--[\w-]+):\s*([^;]+);/g)) {
    if (!declarations.has(name)) declarations.set(name, value.trim());
}

/** The declarations of the first rule whose selector is exactly selector and that sets name. */
function block(selector, name = '') {
    for (let start = css.indexOf(`\n${selector} {\n`); start >= 0; start = css.indexOf(`\n${selector} {\n`, start + 1)) {
        const body = css.slice(css.indexOf('{', start) + 1, css.indexOf('\n}', start));
        if (body.includes(`${name}:`)) return new Map([...body.matchAll(/(--[\w-]+):\s*([^;]+);/g)].map(([, key, value]) => [key, value.trim()]));
    }

    assert.fail(`panel.css has no ${selector} rule that sets ${name}`);
}

const hex = (value) => value.match(/^#([0-9a-f]{6})$/i) && [0, 2, 4].map((i) => Number.parseInt(value.slice(1 + i, 3 + i), 16));

function resolve(value, depth = 0, scope = declarations) {
    assert.ok(depth < 10, `Cannot resolve ${value}`);
    value = value.trim();
    const direct = hex(value);
    if (direct) return direct;
    if (value === 'white' || value === '#fff') return [255, 255, 255];
    if (value === 'black') return [0, 0, 0];
    const variable = value.match(/^var\((--[\w-]+)(?:,\s*([^)]+))?\)$/);
    if (variable) return resolve(scope.get(variable[1]) ?? declarations.get(variable[1]) ?? variable[2], depth + 1, scope);
    const mix = value.match(/^color-mix\(in srgb,\s*(.+?)\s+(\d+)%,\s*(black|white|#[0-9a-f]{6}|var\(--[\w-]+\))\)$/i);
    if (mix) {
        const [color, other, share] = [resolve(mix[1], depth + 1, scope), resolve(mix[3], depth + 1, scope), Number(mix[2]) / 100];
        return color.map((channel, i) => channel * share + other[i] * (1 - share));
    }
    throw new Error(`Unsupported colour value: ${value}`);
}

const luminance = (rgb) => rgb
    .map((channel) => Math.round(channel) / 255)
    .map((channel) => (channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4))
    .reduce((sum, channel, i) => sum + channel * [0.2126, 0.7152, 0.0722][i], 0);

const contrast = (a, b) => {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (light + 0.05) / (dark + 0.05);
};

const toHex = (rgb) => `#${rgb.map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('')}`;

// [what, fill, text, minimum] as the components pair them.
const pairs = [
    ['primary button and badge', 'var(--color-brand-600)', 'white', 4.5],
    ['primary button hover', 'color-mix(in srgb, var(--color-brand-600) 88%, black)', 'white', 4.5],
    ['secondary', 'var(--color-secondary-500)', 'white', 4.5],
    ['success fill', 'var(--color-emerald-solid)', 'white', 4.5],
    ['success hover', 'color-mix(in srgb, var(--color-emerald-solid) 88%, black)', 'white', 4.5],
    ['warning fill', 'var(--color-amber-solid)', 'white', 4.5],
    ['warning hover', 'color-mix(in srgb, var(--color-amber-solid) 88%, black)', 'white', 4.5],
    ['info fill', 'var(--color-sky-solid)', 'white', 4.5],
    ['info hover', 'color-mix(in srgb, var(--color-sky-solid) 88%, black)', 'white', 4.5],
    ['danger fill', 'var(--color-red-600)', 'white', 4.5],
    ['danger hover', 'color-mix(in srgb, var(--color-red-600) 88%, black)', 'white', 4.5],
    ['dark', 'var(--color-dark)', 'white', 4.5],
    ['success text on white', 'white', 'var(--color-emerald-600)', 4.5],
    ['warning text on white', 'white', 'var(--color-amber-600)', 4.5],
    ['info text on white', 'white', 'var(--color-sky-600)', 4.5],
    ['danger text on white', 'white', 'var(--color-red-600)', 4.5],
    ['muted text on white (hints, headings of menus)', 'white', 'var(--color-gray-500)', 4.5],
    ['muted text in the dark theme', 'var(--color-zinc-900)', 'var(--color-zinc-400)', 4.5],
    // The placeholder of the dark theme's fields (zinc-800) and of the command palette (zinc-900): YENİ-O2.
    ['placeholder of a dark theme field', 'var(--color-zinc-800)', 'var(--color-zinc-400)', 4.5],
    ['checkbox and radio frame', 'white', 'var(--ui-control-border)', 3],
];

for (const [what, fill, text, minimum] of pairs) {
    test(`${what} reaches ${minimum}:1`, () => {
        const ratio = contrast(resolve(fill), resolve(text));
        assert.ok(ratio >= minimum, `${what}: ${ratio.toFixed(2)}:1`);
    });
}

test('the solid buttons use the same readable pairs', () => {
    for (const [variant, fill] of [['success', '--color-emerald-solid'], ['warning', '--color-amber-solid'], ['info', '--color-sky-solid']]) {
        assert.match(css, new RegExp(`--btn-${variant}-bg: var\\(${fill}\\);`));
        assert.match(css, new RegExp(`--btn-${variant}-hover: color-mix\\(in srgb, var\\(${fill}\\) 88%, black\\);`));
        assert.match(css, new RegExp(`--btn-${variant}-color: #fff;`));
        assert.match(css, new RegExp(`\\.ui-btn-${variant} \\{[^}]*--btn-color: var\\(--btn-${variant}-color\\)`));
    }
    assert.match(css, /--btn-danger-bg: var\(--color-red-600\);/);
    assert.match(css, /--btn-danger-hover: color-mix\(in srgb, var\(--color-red-600\) 88%, black\);/);
});

test('the preset primary colours: white text on the button and on its hover, brand-600 text on white', () => {
    for (const preset of ['green', 'red', 'yellow', 'blue', 'gray', 'dark']) {
        const scope = block(`:root[data-brand-color='${preset}']`);

        for (const [what, fill] of [['button', '--btn-primary-bg'], ['hover', '--btn-primary-hover']]) {
            const ratio = contrast(resolve(`var(${fill})`, 0, scope), [255, 255, 255]);
            assert.ok(ratio >= 4.5, `${preset} ${what}: ${ratio.toFixed(2)}:1`);
        }

        const ratio = contrast(resolve('var(--color-brand-600)', 0, scope), [255, 255, 255]);
        assert.ok(ratio >= 4.5, `${preset} brand-600 on white: ${ratio.toFixed(2)}:1`);
    }
});

/**
 * The primary button derives its border, hover and shadow from --color-brand-600, so a theme's
 * own @theme value changes them all; no rule keeps the default purple for them.
 */
test('the default primary button derives every shade from --color-brand-600', () => {
    const button = block(':root', '--btn-primary-bg');
    for (const name of ['--btn-primary-bg', '--btn-primary-border', '--btn-primary-hover', '--btn-primary-hover-border', '--btn-primary-shadow']) {
        assert.doesNotMatch(button.get(name), /#[0-9a-f]{3,8}\b|rgb\(/i, `${name} derives from --color-brand-600: ${button.get(name)}`);
    }
    assert.doesNotMatch(css, /:root:not\(\[data-brand-color\]\)|:root\[data-brand-color='default'\]/, 'No rule pins the default purple on the button.');

    // Today's look for the default purple: border #794dff, hover #6a44e0, shadow rgb(121 77 255 / 90%).
    const purple = new Map([['--color-brand-600', '#794dff'], ...button]);
    assert.equal(toHex(resolve('var(--btn-primary-border)', 0, purple)), '#794dff');
    assert.equal(toHex(resolve('var(--btn-primary-hover)', 0, purple)), '#6a44e0');
    assert.equal(toHex(resolve('var(--btn-primary-hover-border)', 0, purple)), '#6a44e0');
    assert.equal(button.get('--btn-primary-shadow'), 'color-mix(in srgb, var(--color-brand-600) 90%, transparent)');

    // The theming guide's example colour: a teal border and hover, not purple ones.
    const teal = new Map([['--color-brand-600', '#0f766e'], ...button]);
    assert.equal(toHex(resolve('var(--btn-primary-border)', 0, teal)), '#0f766e');
    assert.equal(toHex(resolve('var(--btn-primary-hover)', 0, teal)), '#0d6861');
});

/**
 * js/appearance/theme.js writes the measured shades on <html> with data-brand-color="custom"; the
 * rules match only while they are there (T3-D10: their fallback was the default purple, so markup
 * naming the custom colour without them showed purple in a theme of another colour).
 */
test('a custom colour takes its measured shades for the light and the dark theme', () => {
    const custom = ":root[data-brand-color='custom'][style*='--brand-custom-600']";
    assert.equal(block(custom).get('--color-brand-600'), 'var(--brand-custom-600)');
    assert.equal(block(custom.replace(':root', ':root.dark')).get('--color-brand-600'), 'var(--brand-custom-600-dark, var(--brand-custom-600))');
    assert.doesNotMatch(css, /:root(?:\.dark)?\[data-brand-color='custom'\] \{/, 'No custom rule without the shades.');
    // The text on the primary button's hover is measured on its own.
    assert.match(css, /\.ui-btn-primary \{[^}]*--btn-hover-color: var\(--btn-primary-hover-color, var\(--btn-primary-color, #fff\)\);/);
    assert.match(css, /\.ui-btn:not\(:disabled, \[aria-disabled='true'\]\):is\(:hover, :active\) \{[^}]*color: var\(--btn-hover-color, var\(--btn-color, #fff\)\);/);
});

/**
 * Semi-dark (the dark menu in the light theme, .panel-sidebar.dark) takes the dark theme's
 * --color-brand-600 where it differs from the light one, and derives the shades the menu
 * reads from it the way @theme does (on :root they were derived from the light value).
 */
test('the semi-dark menu takes the dark theme primary colour', () => {
    assert.equal(block(":root[data-brand-color='dark'] .panel-sidebar.dark").get('--color-brand-600'), block(":root.dark[data-brand-color='dark']").get('--color-brand-600'));
    const custom = "[data-brand-color='custom'][style*='--brand-custom-600']";
    assert.equal(block(`:root${custom} .panel-sidebar.dark`).get('--color-brand-600'), block(`:root.dark${custom}`).get('--color-brand-600'));

    const start = css.indexOf(`\n:root[data-brand-color='dark'] .panel-sidebar.dark,\n:root${custom} .panel-sidebar.dark {\n`);
    assert.ok(start >= 0, 'one rule derives the shades for both');
    const shades = new Map([...css.slice(start, css.indexOf('\n}', start)).matchAll(/(--[\w-]+):\s*([^;]+);/g)].map(([, key, value]) => [key, value.trim()]));
    assert.deepEqual([...shades.keys()], ['--color-brand-300', '--color-brand-400', '--color-brand-500']);
    for (const [name, value] of shades) assert.equal(value, declarations.get(name), `${name} as in @theme`);
});

const DARK_SURFACES = ['zinc-950', 'zinc-900', 'zinc-800'];

/**
 * The default purple is written once, in @theme (T3-D10, T3-D14): the presets, the custom colour
 * and the semi-dark menu derive from the tokens, so a theme with another --color-brand-600 never
 * shows purple.
 */
test('no rule outside @theme names the default purple', () => {
    const theme = css.slice(css.indexOf('@theme {'), css.indexOf('\n}', css.indexOf('@theme {')));
    const rest = css.replace(theme, '').replace(/\/\*[\s\S]*?\*\//g, '');
    assert.doesNotMatch(rest, /#794dff|rgb\(121 77 255/i);
});

/**
 * The dark preset on the black dark theme (and in the semi-dark menu): #3f404d would disappear, the
 * default purple was there before (TEMA-6, T3-D10). A neutral grey: focus frames stand out 3:1 on
 * every dark surface, white text on it reads 4.5:1.
 */
test('the dark preset takes a neutral grey on the dark theme', () => {
    const scope = block(":root.dark[data-brand-color='dark']");
    const brand = resolve('var(--color-brand-600)', 0, scope);
    assert.equal(scope.get('--color-brand-600'), 'var(--color-gray-500)');
    assert.ok(contrast(brand, [255, 255, 255]) >= 4.5, `white on it: ${contrast(brand, [255, 255, 255]).toFixed(2)}:1`);
    for (const surface of DARK_SURFACES) {
        const ratio = contrast(brand, resolve(`var(--color-${surface})`));
        assert.ok(ratio >= 3, `on ${surface}: ${ratio.toFixed(2)}:1`);
    }
    for (const fill of ['--btn-primary-bg', '--btn-primary-hover']) {
        assert.ok(contrast(resolve(`var(${fill})`, 0, scope), [255, 255, 255]) >= 4.5, fill);
    }
});

/** T3-D11 for the presets: the focus frame (brand-600) stands out 3:1 on every dark surface. */
test('the preset primary colours stand out 3:1 on the dark surfaces', () => {
    const presets = [['default', declarations], ...['green', 'red', 'yellow', 'blue', 'gray'].map((preset) => [preset, block(`:root[data-brand-color='${preset}']`)])];
    for (const [preset, scope] of presets) {
        for (const surface of DARK_SURFACES) {
            const ratio = contrast(resolve('var(--color-brand-600)', 0, scope), resolve(`var(--color-${surface})`));
            assert.ok(ratio >= 3, `${preset} on ${surface}: ${ratio.toFixed(2)}:1`);
        }
    }
});

/** TEMA-5: a preset's brand-600 (tabs, checkbox, pagination…) is the same colour as its button. */
test('a preset fills its button and its brand-600 with one colour', () => {
    for (const preset of ['green', 'red', 'yellow', 'blue', 'gray', 'dark']) {
        const scope = block(`:root[data-brand-color='${preset}']`);
        assert.equal(toHex(resolve('var(--color-brand-600)', 0, scope)), toHex(resolve('var(--btn-primary-bg)', 0, scope)), preset);
    }
});

/**
 * TEMA-3: the solid buttons and the presets derive from the palette tokens instead of repeating
 * their hex values; the look stays the same.
 */
test('the solid buttons and the presets derive from the palette tokens', () => {
    // White text (#fff) and black shadows stay as they are.
    const literal = /#(?!fff\b)[0-9a-f]{3,8}\b|rgb\((?!0 0 0 \/)/i;
    const buttons = block(':root', '--btn-secondary-bg');
    for (const [name, value] of buttons) assert.doesNotMatch(value, literal, `${name}: ${value}`);
    for (const preset of ['green', 'red', 'yellow', 'blue', 'gray', 'dark']) {
        for (const [name, value] of block(`:root[data-brand-color='${preset}']`)) assert.doesNotMatch(value, literal, `${preset} ${name}: ${value}`);
    }
    for (const [name, value] of block(":root.dark[data-brand-color='dark']")) assert.doesNotMatch(value, literal, `dark preset, dark theme ${name}: ${value}`);

    const look = { '--btn-secondary-bg': '#6b7280', '--btn-secondary-hover': '#5e6471', '--btn-dark-bg': '#3f404d', '--btn-dark-hover': '#373844', '--btn-light-bg': '#f4f4f5', '--btn-light-border': '#e4e4e7', '--btn-light-hover': '#e4e4e7', '--btn-light-hover-border': '#d4d4d8', '--btn-light-color': '#52525c' };
    for (const [name, hex] of Object.entries(look)) assert.equal(toHex(resolve(`var(${name})`, 0, buttons)), hex, name);
    // The shadows: the colour at the same opacity.
    for (const [name, colour, share] of [['--btn-secondary-shadow', '#6b7280', 90], ['--btn-dark-shadow', '#3f404d', 75], ['--btn-danger-shadow', '#fb4141', 90]]) {
        const [, value, percent] = buttons.get(name).match(/^color-mix\(in srgb, (.+) (\d+)%, transparent\)$/);
        assert.deepEqual([toHex(resolve(value)), Number(percent)], [colour, share], name);
    }
    assert.equal(toHex(resolve('var(--color-brand-600)', 0, block(":root[data-brand-color='gray']"))), '#6b7280');
    assert.equal(toHex(resolve('var(--color-brand-600)', 0, block(":root[data-brand-color='dark']"))), '#3f404d');
});

/**
 * TEMA-15: the dark theme values of the checkbox frame and the dark button are set on .dark, so
 * the semi-dark menu (.panel-sidebar.dark) takes them too. The --ac-* tokens the PRO dialogs read
 * (themes.css) are checked in tokens.test.mjs, TEMA-7 (their primary fill carries its text) too.
 */
test('the dark theme tokens are set on every .dark element, the semi-dark menu too', () => {
    for (const name of ['--ui-control-border', '--btn-dark-bg', '--btn-dark-hover', '--btn-dark-shadow']) {
        assert.ok(block('.dark', name).has(name), name);
    }
    assert.doesNotMatch(css, /\n:root\.dark \{\n[^}]*--(?:ui-control-border|btn-dark-bg):/, 'Not on :root.dark alone.');
});
