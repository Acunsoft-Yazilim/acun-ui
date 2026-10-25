import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { brandShades, contrast } from '../js/appearance/theme.js';

// The user's colour (js/appearance/theme.js brandShades): a preset or any #rrggbb is measured
// against the hardest text backgrounds of the enabled themes (Appearance::targets()) and written
// once as --ac-user-*; every shade is checked here on every text background of every shipped theme.
// Text needs 4.5:1, a focus frame or a border 3:1 (WCAG AA).

const read = (file) => fs.readFileSync(new URL(file, import.meta.url), 'utf8');
const rgb = (hex) => [1, 3, 5].map((i) => Number.parseInt(hex.slice(i, i + 2), 16));
const mix = (color, other, share) => color.map((channel, i) => Math.round(channel * share + other[i] * (1 - share)));
const toHex = (color) => `#${color.map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('')}`;
const WHITE = [255, 255, 255];
const BLACK = [0, 0, 0];

// The shipped themes' text backgrounds, from css/themes.css: the built-in light and dark blocks
// (the bordered skin paints the page with the surface, which is one of them already). Link text
// sits on the bare page as well as on cards and fields, so the page is one in both themes.
const themesCss = read('../css/themes.css').replace(/\/\*[\s\S]*?\*\//g, '');
const block = (selector) => {
    const start = themesCss.indexOf(`${selector} {`);
    assert.ok(start >= 0, `themes.css has ${selector}`);
    return themesCss.slice(start, themesCss.indexOf('}', start));
};
const neutral = (css, name) => css.match(new RegExp(`--ac-${name}: (#[0-9a-f]{6});`))[1];
const lightBlock = block(':root,\n    [data-theme]');
const darkBlock = block("[data-theme$='dark']");
const THEMES = {
    // Light text backgrounds: the page (#fafafa, the darkest: the target), the cards and the fields (white).
    light: { light: ['page', 'surface', 'field'].map((name) => neutral(lightBlock, name)), dark: [] },
    // Dark text backgrounds: the page, the cards and the fields (the lightest).
    dark: { light: [], dark: ['page', 'surface', 'field'].map((name) => neutral(darkBlock, name)) },
};
const lightBackgrounds = Object.values(THEMES).flatMap((theme) => theme.light);
const darkBackgrounds = Object.values(THEMES).flatMap((theme) => theme.dark);

// Acun\Ui\Support\Appearance: the default targets and the preset colours.
const appearancePhp = read('../src/Support/Appearance.php');
const TARGETS = Object.fromEntries([...appearancePhp.match(/const TARGETS = \[([^\]]*)\]/)[1].matchAll(/'(\w+)' => '(#[0-9a-f]{6})'/g)].map(([, side, hex]) => [side, hex]));
const COLORS = Object.fromEntries([...appearancePhp.match(/const COLORS = \[([\s\S]*?)\n {4}\];/)[1].matchAll(/'(\w+)' => (?:'(#[0-9a-f]{6})'|\['light' => '(#[0-9a-f]{6})', 'dark' => '(#[0-9a-f]{6})'\])/g)]
    .map(([, key, hex, light, dark]) => [key, hex ?? { light, dark }]));

/** Every pair below its minimum for color on targets, as "colour what: ratio". */
function check(color, targets = TARGETS) {
    const shades = brandShades(color, targets);
    const [fill, hover, onFill, tone, toneDark] = [shades.fill, shades.hover, shades.onFill, shades.tone, shades.toneDark].map(rgb);
    const darkInk = mix(toneDark, WHITE, 0.66);
    const ratios = [
        ['button text', contrast(onFill, fill), 4.5],
        ['hover text', contrast(onFill, hover), 4.5],
        ...lightBackgrounds.map((ground) => [`tone on ${ground}`, contrast(tone, rgb(ground)), 4.5]),
        ...darkBackgrounds.map((ground) => [`dark tone on ${ground}`, contrast(toneDark, rgb(ground)), 3]),
        // The dark theme's primary text (themes.css --ac-primary-ink: 66% of the tone, with white).
        ...['surface', 'field'].map((name) => neutral(darkBlock, name)).map((ground) => [`dark ink on ${ground}`, contrast(darkInk, rgb(ground)), 4.5]),
        // White text on the dark tone: application code's bg-brand-600 text-white in the dark theme.
        ['white on the dark tone', contrast(WHITE, toneDark), 4.5],
    ];

    return ratios.filter(([, ratio, minimum]) => ratio < minimum).map(([what, ratio]) => `${color} ${what}: ${ratio.toFixed(2)}:1`);
}

// The colours that gave unreadable pairs before (acun-ui-eksikler Y11, acun-ui-kontrol T3-D11 and
// T3-D12) and the presets.
const examples = ['#7a7a7a', '#ffd400', '#ff6a00', '#1e3a8a', '#2563eb', '#508858', '#808080', '#f9c74f', '#0070f8', '#794dff', '#00ca4b', '#fb4141', '#fcaa1e', '#04b6d4', '#6b7280', '#3f404d', '#000000', '#ffffff'];

// 32 steps per channel: 32,768 colours.
const steps = Array.from({ length: 32 }, (_, i) => Math.round((i * 255) / 31).toString(16).padStart(2, '0'));
const grid = steps.flatMap((r) => steps.flatMap((g) => steps.map((b) => `#${r}${g}${b}`)));

test('the targets are the shipped themes\' hardest text backgrounds', () => {
    // Appearance::TARGETS agrees with themes.css: the light theme's page, the dark theme's field.
    assert.deepEqual(TARGETS, { light: neutral(lightBlock, 'page'), dark: neutral(darkBlock, 'field') });
    const luminance = (hex) => contrast(rgb(hex), BLACK);
    assert.equal(TARGETS.light, [...lightBackgrounds].sort((a, b) => luminance(a) - luminance(b))[0], 'The darkest light text background.');
    assert.equal(TARGETS.dark, [...darkBackgrounds].sort((a, b) => luminance(b) - luminance(a))[0], 'The lightest dark text background.');
    // The runtime's default targets are the same.
    assert.match(read('../js/appearance/theme.js'), new RegExp(`DEFAULT_TARGETS = \\{ light: '${TARGETS.light}', dark: '${TARGETS.dark}' \\}`));
});

test('the colours that failed before give readable pairs', () => {
    assert.deepEqual(examples.flatMap((color) => check(color)), []);
});

/**
 * Link and outline text sits on the bare page too, not only on cards and fields. Measured against
 * the surface (#fff) alone, a borderline colour's text read 4.32:1 on the page (#fafafa: #ffd400),
 * the green, red, yellow and cyan presets' 4.35-4.43:1. The light target is the page now.
 */
test('the text tone reads 4.5:1 on the light page, not only on the cards', () => {
    const page = rgb(neutral(lightBlock, 'page'));
    const presets = Object.values(COLORS).map((color) => color.light ?? color);
    for (const color of ['#ffd400', '#ff6a00', '#f9c74f', ...presets]) {
        assert.ok(contrast(rgb(brandShades(color).tone), page) >= 4.5, `${color}: ${contrast(rgb(brandShades(color).tone), page).toFixed(2)}:1 on the page`);
    }
    // Measured on the surface alone it did not.
    assert.equal(contrast(rgb(brandShades('#ffd400', { light: '#ffffff' }).tone), page).toFixed(2), '4.32');
});

test('every colour of a 32-step grid gives readable pairs on every shipped theme surface', () => {
    const failures = grid.flatMap((color) => check(color));

    assert.deepEqual(failures.slice(0, 10), [], `${failures.length} failing pairs`);
});

test('darker targets (a theme with a cream page) keep every pair readable there', () => {
    const cream = { light: '#f7f3ea', dark: '#2e2a23' };
    const failures = grid.filter((_, i) => i % 7 === 0).flatMap((color) => {
        const { tone, toneDark } = brandShades(color, cream);
        return [
            contrast(rgb(tone), rgb(cream.light)) < 4.5 ? `${color} tone on ${cream.light}` : null,
            contrast(rgb(toneDark), rgb(cream.dark)) < 3 ? `${color} dark tone on ${cream.dark}` : null,
            contrast(mix(rgb(toneDark), WHITE, 0.66), rgb(cream.dark)) < 4.5 ? `${color} dark ink on ${cream.dark}` : null,
        ].filter(Boolean);
    });

    assert.deepEqual(failures.slice(0, 10), [], `${failures.length} failing pairs`);
});

test('white text while it reads, otherwise a dark shade of the colour', () => {
    // Dark colours keep white text.
    assert.equal(brandShades('#1e3a8a').onFill, '#ffffff');
    assert.equal(brandShades('#794dff').onFill, '#ffffff');
    // #508858 gave white text at 3.98:1, #808080 a dark shade at 4.17:1: both get a dark shade that reads.
    for (const color of ['#508858', '#808080', '#7a7a7a']) {
        assert.notEqual(brandShades(color).onFill, '#ffffff', color);
        assert.ok(contrast(rgb(color), rgb(brandShades(color).onFill)) >= 4.5, color);
    }
});

/**
 * The text keeps its colour on hover (T3-D12: #0070f8 had dark text that turned white on the
 * hover). The hover mixes 12% black into the fill, as themes.css does for the theme's own; when no
 * dark text reads on that darker hover, the hover is the fill with 12% white instead, where the
 * same dark text reads better.
 */
test('the hover is 12% darker, or 12% lighter when the dark text would not read', () => {
    for (const color of [...examples, ...grid]) {
        const { hover, onFill } = brandShades(color);
        const darker = toHex(mix(rgb(color), BLACK, 0.88));
        const lighter = toHex(mix(rgb(color), WHITE, 0.88));
        assert.ok(hover === darker || (onFill !== '#ffffff' && hover === lighter), `${color}: hover ${hover}`);
        if (onFill === '#ffffff') assert.equal(hover, darker, `${color}: white text, the hover darkens`);
    }

    const blue = brandShades('#0070f8');
    assert.notEqual(blue.onFill, '#ffffff', 'White reads 4.49:1 on #0070f8.');
    assert.ok(contrast(rgb(blue.hover), rgb(blue.onFill)) >= 4.5);
    // #ff6a00 (orange): its dark text reads on its 12% darker hover too.
    assert.equal(brandShades('#ff6a00').hover, toHex(mix(rgb('#ff6a00'), BLACK, 0.88)));
});

test('the tone is the colour itself when it reads on the light target, the dark theme lightens only dark colours', () => {
    assert.equal(brandShades('#794dff').tone, '#794dff');
    assert.equal(brandShades('#2563eb').tone, '#2563eb');
    // #ffd400 gave 3.89:1 with the fixed 60% black.
    assert.notEqual(brandShades('#ffd400').tone, '#ffd400');
    // Light or medium colours keep their white-text shade in the dark theme (the light page's target
    // does not darken it); #1e3a8a (1.78:1 there) gets a lighter one.
    assert.equal(brandShades('#ff6a00').toneDark, brandShades('#ff6a00', { light: '#ffffff' }).tone);
    assert.equal(brandShades('#ff6a00', { light: '#f7f3ea' }).toneDark, brandShades('#ff6a00').toneDark);
    assert.equal(brandShades('#1e3a8a').tone, '#1e3a8a');
    assert.notEqual(brandShades('#1e3a8a').toneDark, '#1e3a8a');
    // T3-D11: the dark tone was measured on zinc-900 only; on the fields (#1f1f1f) #1e3a8a's gave 2.69:1.
    assert.ok(contrast(rgb(brandShades('#1e3a8a').toneDark), rgb(TARGETS.dark)) >= 3);
});

/**
 * The presets are the palette's solid fills, read from panel.css (and the dark tone from
 * themes.css), so they cannot drift; through the pipeline they give today's tuned button, hover,
 * text and brand-600 values. Two exceptions: the dark preset's tone in a dark theme was a hand-picked
 * gray-500 (#71717b); measured, it is the least lightened shade of #3f404d that stands out 3:1. And
 * the light theme's text tone of the four fills that read below 4.5:1 on the page (#fafafa) is a
 * step darker, as the built-in success and danger inks are (at most 3 per channel); their buttons,
 * hovers and dark-theme tones keep the fill.
 */
test('the presets through the pipeline give today\'s tuned values', () => {
    const panelCss = read('../css/core.css');
    const palette = (name) => {
        const value = (panelCss.match(new RegExp(`--color-${name}: ([^;]+);`)) ?? themesCss.match(new RegExp(`--ac-${name}: ([^;]+);`)))[1].trim();
        const shade = value.match(/^color-mix\(in srgb, var\(--color-([\w-]+)\) (\d+)%, (black|white)\)$/);
        return shade ? toHex(mix(rgb(palette(shade[1])), shade[3] === 'black' ? BLACK : WHITE, Number(shade[2]) / 100)) : value;
    };
    const fills = { green: palette('emerald-solid'), red: palette('red-600'), yellow: palette('amber-solid'), blue: palette('sky-solid'), gray: palette('secondary-500'), dark: { light: palette('tone-dark'), dark: palette('zinc-600') } };

    assert.deepEqual(Object.keys(COLORS), ['default', 'green', 'red', 'yellow', 'blue', 'gray', 'dark']);
    assert.equal(COLORS.default, neutral(lightBlock, 'tone-primary'), 'The swatch of the theme\'s own colour is the built-in purple.');
    assert.deepEqual(Object.fromEntries(Object.entries(COLORS).filter(([key]) => key !== 'default')), fills);

    // On the page: green 4.35, red 4.43, yellow 4.39, cyan 4.38:1 → a step darker.
    const darker = { green: '#008632', red: '#d63737', yellow: '#9c6913', blue: '#037e94' };
    for (const [key, color] of Object.entries(fills)) {
        const fill = color.light ?? color;
        const shades = brandShades(fill);
        // Today: the button filled with the palette fill, hovered with 12% black, white text; brand-600 the fill.
        assert.deepEqual({ fill: shades.fill, hover: shades.hover, onFill: shades.onFill, tone: shades.tone }, { fill, hover: toHex(mix(rgb(fill), BLACK, 0.88)), onFill: '#ffffff', tone: darker[key] ?? fill }, key);
        if (darker[key]) {
            const page = rgb(neutral(lightBlock, 'page'));
            assert.ok(contrast(rgb(fill), page) < 4.5 && contrast(rgb(shades.tone), page) >= 4.5, `${key}: the fill fails on the page, the tone reads`);
            assert.ok(Math.max(...[0, 1, 2].map((i) => Math.abs(rgb(fill)[i] - rgb(shades.tone)[i]))) <= 3, `${key}: a small step`);
        }
        if (key === 'dark') {
            assert.equal(shades.toneDark, '#696a74', 'Measured (today\'s hand-picked #71717b).');
            // Its own dark fill: zinc-600 with white text, hovered with 12% black, as today.
            const dark = brandShades(color.dark);
            assert.deepEqual([dark.fill, dark.hover, dark.onFill], ['#474747', toHex(mix(rgb('#474747'), BLACK, 0.88)), '#ffffff']);
            assert.ok(contrast(WHITE, rgb(dark.hover)) >= 4.5);
        } else {
            assert.equal(shades.toneDark, fill, `${key}: today's dark-theme brand-600 is the fill too`);
        }
        assert.deepEqual(check(fill), [], key);
    }
});
