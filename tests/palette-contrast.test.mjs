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
// acun:button paints with the tokens (bg-success text-on-success…); its variant map is checked against them.
const buttonView = fs.readFileSync(path.join(cssDirectory, '../resources/views/components/ui/button.blade.php'), 'utf8');
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
    const variable = value.match(/^var\((--[\w-]+)(?:,\s*(.+))?\)$/);
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

test('the solid buttons fill with the tokens\' readable pairs', () => {
    // themes.css: each solid fill is the palette's solid shade, hovered with 12% black, with white text.
    for (const [tone, fill] of [['success', 'emerald-solid'], ['warning', 'amber-solid'], ['info', 'sky-solid'], ['danger', 'red-600'], ['secondary', 'secondary-500']]) {
        assert.equal(toHex(resolve(`var(--ac-${tone})`)), toHex(resolve(`var(--color-${fill})`)), tone);
        assert.equal(toHex(resolve(`var(--ac-${tone}-hover)`)), toHex(resolve(`color-mix(in srgb, var(--color-${fill}) 88%, black)`)), `${tone} hover`);
        assert.equal(declarations.get(`--ac-on-${tone}`), '#fff', `on-${tone}`);
    }
    // acun:button: every solid variant is its tone's fill, hover and text colour (the border is the fill).
    for (const tone of ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark']) {
        assert.match(buttonView, new RegExp(`'${tone}' => 'border-${tone} bg-${tone} text-on-${tone} hover:not-disabled:border-${tone}-hover hover:not-disabled:bg-${tone}-hover `), tone);
    }
    // The .ui-btn classes and the --btn-* variables are gone: the primary fill is a token too.
    const rules = css.replace(/\/\*[\s\S]*?\*\//g, '');
    assert.doesNotMatch(rules, /\.ui-btn|--btn-/);
});

/**
 * The primary fill derives its hover from the theme's tone (themes.css), so a theme's own
 * --ac-tone-primary changes them all; the user's colour (--ac-user-*, js/appearance/theme.js,
 * tests/brand-contrast.test.mjs) comes before it. The border is the fill (border-primary) and the
 * shadow prop the fill at 90% (shadow-primary/90).
 */
test('the default primary button derives every shade from the theme\'s tone', () => {
    assert.equal(declarations.get('--ac-primary'), 'var(--ac-user-fill, var(--ac-p))');
    assert.equal(declarations.get('--ac-primary-hover'), 'var(--ac-user-fill-hover, color-mix(in srgb, var(--ac-primary) 88%, black))');
    assert.equal(declarations.get('--ac-on-primary'), 'var(--ac-user-on-fill, var(--ac-tone-on-primary, #fff))');
    assert.equal(declarations.get('--ac-p'), 'var(--ac-user-tone, var(--ac-tone-primary))');

    // The 1.x look for the default purple: fill and border #794dff, hover #6a44e0.
    const theme = (tone) => new Map([['--ac-tone-primary', tone]]);
    const own = (scope) => [resolve('var(--ac-p)', 0, scope), resolve('color-mix(in srgb, var(--ac-p) 88%, black)', 0, scope)].map(toHex);
    assert.deepEqual(own(theme('#794dff')), ['#794dff', '#6a44e0']);
    assert.match(buttonView, /'primary' => 'border-primary bg-primary /);
    assert.match(buttonView, /'primary' => 'shadow-primary\/90'/);
    // The theming guide's example colour: a teal fill and hover, not purple ones.
    assert.deepEqual(own(theme('#0f766e')), ['#0f766e', '#0d6861']);

    // The measured text on a user's fill is the token's on-primary, and the primary button keeps it on the hover.
    const primary = buttonView.match(/'primary' => '([^']*)'/)[1];
    assert.match(primary, /(?:^| )text-on-primary(?: |$)/);
    assert.doesNotMatch(primary, /hover:[\w:-]*text-/);
});

const DARK_SURFACES = ['zinc-950', 'zinc-900', 'zinc-800'];

/**
 * The default purple is written once, as the built-in theme's tone input (themes.css; T3-D10,
 * T3-D14): the presets, the custom colour and the semi-dark menu derive from the tokens, so a theme
 * with another --ac-tone-primary never shows purple. Acun\Ui\Support\Appearance names it for the
 * swatch of the theme's own colour.
 */
test('no rule names the default purple except the built-in tone', () => {
    const rest = `${css}\n${tokens}`.replace(/\/\*[\s\S]*?\*\//g, '').replace('--ac-tone-primary: #794dff;', '');
    assert.doesNotMatch(rest, /#794dff|rgb\(121 77 255/i);
});

/** The dark tone and the dark tone's fill on the dark surfaces (the dark preset's dark fill is the grey too). */
test('the dark tone is a neutral grey in the dark theme, with white text', () => {
    const darkBlock = tokens.slice(tokens.indexOf("[data-theme$='dark'] {"));
    const scope = new Map([...declarations, ...[...darkBlock.slice(0, darkBlock.indexOf('}')).matchAll(/(--[\w-]+):\s*([^;]+);/g)].map(([, key, value]) => [key, value.trim()])]);
    assert.equal(resolve('var(--ac-dark)', 0, scope).map(Math.round).join(), [46, 46, 46].join());
    for (const fill of ['var(--ac-dark)', 'var(--ac-dark-hover)', 'var(--color-zinc-600)']) {
        assert.ok(contrast(resolve(fill, 0, scope), [255, 255, 255]) >= 4.5, fill);
    }
});

/**
 * TEMA-3: the solid buttons and the presets derive from the palette tokens instead of repeating
 * their hex values; the look stays the same.
 */
test('the solid buttons and the presets derive from the palette tokens', () => {
    // White text (#fff) and black shadows stay as they are.
    const literal = /#(?!fff\b)[0-9a-f]{3,8}\b|rgb\((?!0 0 0 \/)/i;
    // The presets are colours of Acun\Ui\Support\Appearance, checked against the palette in tests/brand-contrast.test.mjs.
    assert.doesNotMatch(css, /data-brand-color/);

    // Today's look of the secondary, dark and light buttons, now from the tokens (light theme).
    const look = { '--ac-secondary': '#6b7280', '--ac-secondary-hover': '#5e6471', '--ac-dark': '#3f404d', '--ac-dark-hover': '#373844', '--ac-sunken': '#f4f4f5', '--ac-line': '#e4e4e7', '--ac-track': '#e4e4e7', '--ac-line-strong': '#d4d4d8', '--ac-dim': '#52525c' };
    for (const [name, hex] of Object.entries(look)) assert.equal(toHex(resolve(`var(${name})`)), hex, name);
    assert.match(buttonView, /'light' => 'border-line bg-sunken text-dim hover:not-disabled:border-line-strong hover:not-disabled:bg-track /);
    // The shadow prop: the fill at the same opacity as before.
    for (const [tone, share] of [['secondary', 90], ['dark', 75], ['danger', 90]]) assert.match(buttonView, new RegExp(`'${tone}' => 'shadow-${tone}/${share}'`), tone);
});

/**
 * TEMA-15: the dark theme's values are set on every element whose data-theme ends in "dark", so the
 * semi-dark menu (data-theme="dark") takes them too: the dark tone's grey (--ac-dark, --ac-dark-hover)
 * in the dark block of themes.css. The 1.x checkbox frame variable (--ui-control-border, panel.css)
 * still follows the .dark class the runtime writes next to data-theme.
 */
test('the dark theme tokens are set on every dark data-theme, the semi-dark menu too', () => {
    assert.ok(block('.dark', '--ui-control-border').has('--ui-control-border'));
    const dark = tokens.slice(tokens.indexOf("[data-theme$='dark'] {"));
    assert.match(dark, /^\[data-theme\$='dark'\] \{[^}]*--ac-dark: #2e2e2e;[^}]*--ac-dark-hover: #474747;/);
    assert.doesNotMatch(css, /\n:root\.dark \{\n[^}]*--ui-control-border:/, 'Not on :root.dark alone.');
});
