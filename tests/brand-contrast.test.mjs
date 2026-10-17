import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { brandShades, contrast } from '../js/appearance/theme.js';

// A custom primary colour (js/appearance/theme.js brandShades): every shade is measured, so any
// #rrggbb gives readable pairs. Text needs 4.5:1, a focus frame or a border 3:1 (WCAG AA).

const rgb = (hex) => [1, 3, 5].map((i) => Number.parseInt(hex.slice(i, i + 2), 16));
const mix = (color, other, share) => color.map((channel, i) => Math.round(channel * share + other[i] * (1 - share)));
const toHex = (color) => `#${color.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
const WHITE = [255, 255, 255];
const BLACK = [0, 0, 0];

// The dark theme's surfaces a focus frame sits on, from panel.css: the page (zinc-950), cards and
// menus (zinc-900), fields and hovered rows (zinc-800, the lightest).
const css = fs.readFileSync(new URL('../css/panel.css', import.meta.url), 'utf8');
const DARK_SURFACES = Object.fromEntries(['950', '900', '800'].map((step) => [`zinc-${step}`, rgb(css.match(new RegExp(`--color-zinc-${step}: (#[0-9a-f]{6});`))[1])]));

function check(color) {
    const shades = brandShades(color);
    const ratios = {
        'button text': contrast(rgb(color), rgb(shades.text)),
        'hover text': contrast(rgb(shades.hover), rgb(shades.hoverText)),
        'brand-600 text on white': contrast(rgb(shades.brand600), WHITE),
        'white text on the dark theme brand-600': contrast(rgb(shades.brand600Dark), WHITE),
        ...Object.fromEntries(Object.entries(DARK_SURFACES).map(([name, surface]) => [`dark theme brand-600 on ${name}`, contrast(rgb(shades.brand600Dark), surface)])),
    };

    return Object.entries(ratios).filter(([what, ratio]) => ratio < (what.startsWith('dark theme brand-600 on') ? 3 : 4.5)).map(([what, ratio]) => `${color} ${what}: ${ratio.toFixed(2)}:1`);
}

// The colours that gave unreadable pairs before (acun-ui-eksikler Y11, acun-ui-kontrol T3-D11 and
// T3-D12) and the presets.
const examples = ['#7a7a7a', '#ffd400', '#ff6a00', '#1e3a8a', '#2563eb', '#508858', '#808080', '#f9c74f', '#0070f8', '#794dff', '#00ca4b', '#fb4141', '#fcaa1e', '#04b6d4', '#6b7280', '#3f404d', '#000000', '#ffffff'];

// 32 steps per channel: 32,768 colours.
const steps = Array.from({ length: 32 }, (_, i) => Math.round((i * 255) / 31).toString(16).padStart(2, '0'));
const grid = steps.flatMap((r) => steps.flatMap((g) => steps.map((b) => `#${r}${g}${b}`)));

test('the colours that failed before give readable pairs', () => {
    assert.deepEqual(examples.flatMap(check), []);
});

test('every colour of a 32-step grid gives readable pairs', () => {
    const failures = grid.flatMap(check);

    assert.deepEqual(failures.slice(0, 10), [], `${failures.length} failing pairs`);
});

test('white text while it reads, otherwise a dark shade of the colour', () => {
    // Dark colours keep white text.
    assert.equal(brandShades('#1e3a8a').text, '#ffffff');
    assert.equal(brandShades('#794dff').text, '#ffffff');
    // #508858 gave white text at 3.98:1, #808080 a dark shade at 4.17:1: both get a dark shade that reads.
    for (const color of ['#508858', '#808080', '#7a7a7a']) {
        assert.notEqual(brandShades(color).text, '#ffffff', color);
        assert.ok(contrast(rgb(color), rgb(brandShades(color).text)) >= 4.5, color);
    }
});

/**
 * The text keeps its colour on hover (T3-D12: #0070f8 had dark text that turned white on the
 * hover). The hover mixes 12% black into the fill, as panel.css does for the default and the
 * presets; when no dark text reads on that darker hover, the hover is the fill with 12% white
 * instead, where the same dark text reads better.
 */
test('the hover keeps the button text and is 12% darker, or 12% lighter when the dark text would not read', () => {
    const changed = grid.filter((color) => brandShades(color).hoverText !== brandShades(color).text);
    assert.deepEqual(changed.slice(0, 5), [], `${changed.length} colours change the text colour on hover`);

    for (const color of [...examples, ...grid]) {
        const { hover, text } = brandShades(color);
        const darker = toHex(mix(rgb(color), BLACK, 0.88));
        const lighter = toHex(mix(rgb(color), WHITE, 0.88));
        assert.ok(hover === darker || (text !== '#ffffff' && hover === lighter), `${color}: hover ${hover}`);
        if (text === '#ffffff') assert.equal(hover, darker, `${color}: white text, the hover darkens`);
    }

    const blue = brandShades('#0070f8');
    assert.notEqual(blue.text, '#ffffff', 'White reads 4.49:1 on #0070f8.');
    assert.equal(blue.hoverText, blue.text);
    assert.ok(contrast(rgb(blue.hover), rgb(blue.text)) >= 4.5);
    // #ff6a00 (orange): its dark text reads on its 12% darker hover too.
    assert.equal(brandShades('#ff6a00').hover, toHex(mix(rgb('#ff6a00'), BLACK, 0.88)));
});

test('brand-600 is the colour itself when it reads on white, the dark theme lightens only dark colours', () => {
    assert.equal(brandShades('#794dff').brand600, '#794dff');
    assert.equal(brandShades('#2563eb').brand600, '#2563eb');
    // #ffd400 gave 3.89:1 with the fixed 60% black.
    assert.notEqual(brandShades('#ffd400').brand600, '#ffd400');
    // Light or medium colours keep the same tone in the dark theme; #1e3a8a (1.78:1 there) gets a lighter one.
    assert.equal(brandShades('#ff6a00').brand600Dark, brandShades('#ff6a00').brand600);
    assert.equal(brandShades('#1e3a8a').brand600, '#1e3a8a');
    assert.notEqual(brandShades('#1e3a8a').brand600Dark, '#1e3a8a');
});

/** T3-D11: the dark tone was measured on zinc-900 only; on zinc-800 (fields) #1e3a8a's gave 2.69:1. */
test('the dark theme tone stands out 3:1 on the lightest dark surface, zinc-800', () => {
    const navy = brandShades('#1e3a8a').brand600Dark;
    assert.ok(contrast(rgb(navy), DARK_SURFACES['zinc-800']) >= 3, `${navy} on zinc-800: ${contrast(rgb(navy), DARK_SURFACES['zinc-800']).toFixed(2)}:1`);
});
