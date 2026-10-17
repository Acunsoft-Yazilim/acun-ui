import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { brandShades } from '../../js/appearance/theme.js';
import { chrome, inChrome, stylesheet } from './chrome.mjs';

// Contrast as Chrome paints it (see chrome.mjs): the placeholders of the fields and of the command
// palette, the counter on a button, the focus ring token and the primary colour presets in a theme
// of another colour. Text needs 4.5:1, a focus frame 3:1 (WCAG AA).

const skip = !chrome && 'Chrome is not installed (CHROME_PATH)';
const root = fileURLToPath(new URL('../../../../', import.meta.url));
const render = (templates) => JSON.parse(execFileSync('php', [fileURLToPath(new URL('./render.php', import.meta.url))], { cwd: root, input: JSON.stringify(templates) }));

const variants = ['primary', 'success', 'danger', 'warning', 'info', 'secondary', 'dark', 'light', 'outline', 'outline-success', 'outline-danger', 'outline-warning', 'outline-info', 'outline-secondary', 'outline-dark', 'soft', 'soft-success', 'soft-danger', 'soft-warning', 'soft-info', 'soft-secondary', 'soft-dark', 'white', 'ghost', 'link'];

const html = skip ? {} : render({
    fields: `
        <div data-case="input"><acun:input name="ara" placeholder="Sipariş no" /></div>
        <div data-case="textarea"><acun:textarea name="not" placeholder="Not" /></div>
        <div data-case="search"><acun:search name="q" placeholder="Ara" /></div>
        <div data-case="input-group"><acun:input-group name="tutar" placeholder="0,00"><x-slot:prefix>₺</x-slot:prefix></acun:input-group></div>
        <div data-case="floating input"><acun:input name="ad" label="Ad" variant="floating" /></div>
        <div data-case="floating textarea"><acun:textarea name="adres" label="Adres" variant="floating" /></div>`,
    palette: `<acun:command-palette :items="[['label' => 'Siparişler', 'href' => '/siparisler']]" />`,
    buttons: variants.map((variant) => `<p data-case="${variant}"><acun:button variant="${variant}" badge="12">Sepet</acun:button></p>`).join(''),
    links: `<p data-case="link primary"><acun:button href="/sepet" badge="3">Sepet</acun:button></p>`,
    focus: `<p data-case="focus"><acun:button>Kaydet</acun:button> <acun:button variant="outline">Vazgeç</acun:button> <acun:button variant="link">Ayrıntı</acun:button></p>`,
    menu: '<acun:button>Menü</acun:button>',
});
const { menu: menuButton = '', ...parts } = html;
const markup = Object.values(parts).join('\n');

// The command palette's dialog is teleported to <body> by Alpine; the page puts it there itself.
const page = `<div id="card" class="m-4 rounded-sm bg-white p-4 dark:bg-zinc-900">${markup}</div>`;
const teleport = `document.querySelectorAll('template').forEach((template) => document.body.append(template.content.cloneNode(true)));`;

const measureText = `
    ${teleport}
    const root = document.documentElement;
    const measure = () => {
        const result = {};
        for (const name of ['input', 'textarea', 'search', 'input-group']) result['placeholder: ' + name] = textContrast(document.querySelector('[data-case="' + name + '"] :is(input, textarea)'), '::placeholder');
        // A floating label stands where the placeholder would be while the field is empty.
        for (const name of ['floating input', 'floating textarea']) result['empty ' + name + ' label'] = textContrast(document.querySelector('[data-case="' + name + '"] label'));
        const dialog = document.querySelector('[role="dialog"]');
        result['command palette placeholder'] = textContrast(dialog.querySelector('input'), '::placeholder');
        result['command palette Esc'] = textContrast([...dialog.querySelectorAll('kbd')].find((kbd) => kbd.textContent.trim() === 'Esc'));
        document.querySelectorAll('[data-case] :is(button, a) > span.rounded-full').forEach((counter) => {
            const name = counter.closest('[data-case]').dataset.case;
            result['counter: ' + name] = textContrast(counter);
            // Without a fill of its own the counter keeps the button's text contrast in every state (hover too).
            result['counter fill alpha: ' + name] = rgba(getComputedStyle(counter).backgroundColor)[3];
        });
        return result;
    };
    const light = measure();
    root.classList.add('dark');
    const dark = measure();
    root.classList.remove('dark');
    // A custom primary colour with dark text (js/appearance/theme.js measures its shades).
    root.dataset.brandColor = 'custom';
    Object.entries(window.customYellow).forEach(([name, value]) => root.style.setProperty(name, value));
    const yellow = textContrast(document.querySelector('[data-case="primary"] button > span.rounded-full'));
    return { light, dark, yellow };
`;

const yellow = brandShades('#ffd400');
const customYellow = {
    '--brand-custom-600': yellow.brand600, '--brand-custom-600-dark': yellow.brand600Dark, '--btn-primary-bg': '#ffd400', '--btn-primary-border': '#ffd400',
    '--btn-primary-hover': yellow.hover, '--btn-primary-hover-border': yellow.hover, '--btn-primary-color': yellow.text, '--btn-primary-hover-color': yellow.hoverText,
};

test('placeholders, the command palette and button counters read 4.5:1 in both themes', { skip }, async () => {
    const css = await stylesheet(markup);
    const { light, dark, yellow: onYellow } = inChrome({ css, body: `${page}<script>window.customYellow = ${JSON.stringify(customYellow)};</script>`, measure: measureText });

    const failing = Object.entries({ ...Object.fromEntries(Object.entries(light).map(([k, v]) => [`light ${k}`, v])), ...Object.fromEntries(Object.entries(dark).map(([k, v]) => [`dark ${k}`, v])) })
        .filter(([what, value]) => (what.includes('fill alpha') ? value !== 0 : value < 4.5));
    // Before: dark placeholders 3.41, the command palette's 3.82, counters 3.51-3.79 (YENİ-O2, YENİ-O12).
    assert.deepEqual(Object.fromEntries(failing), {});
    assert.equal(Object.keys(dark).filter((key) => key.startsWith('counter: ')).length, variants.length + 1, 'Every variant was measured.');
    assert.ok(onYellow >= 4.5, `The counter on a custom yellow (dark text): ${onYellow}:1`);
});

/**
 * TEMA-9: the focus ring of the kit is one token, --ui-focus-ring; without it the ring is the
 * primary colour as before (the dark theme's in the semi-dark menu).
 */
test('the focus ring is --ui-focus-ring, the primary colour unless a theme sets it', { skip }, async () => {
    // The semi-dark menu: .panel-sidebar.dark in the light theme.
    const body = `${page}<aside class="panel-sidebar dark bg-zinc-900"><span id="semi-dark">${menuButton}</span></aside>`;
    const css = await stylesheet(body);
    const result = inChrome({ css, body, htmlAttributes: 'data-brand-color="dark"', measure: `
        const ring = (element) => { element.focus({ focusVisible: true }); return hex(getComputedStyle(element).outlineColor); };
        const buttons = [...document.querySelectorAll('[data-case="focus"] :is(button, a)')];
        const brand = () => hex(getComputedStyle(document.documentElement).getPropertyValue('--color-brand-600'));
        const preset = { buttons: buttons.map(ring), brand: brand(), semiDark: ring(document.querySelector('#semi-dark button')) };
        document.documentElement.style.setProperty('--ui-focus-ring', 'rgb(0, 128, 0)');
        const themed = { buttons: buttons.map(ring), semiDark: ring(document.querySelector('#semi-dark button')) };
        return { preset, themed };
    ` });

    // The dark preset: #3f404d on the page; the semi-dark menu takes the dark theme's grey (3:1 on zinc-900).
    assert.deepEqual(result.preset.buttons, [result.preset.brand, result.preset.brand, result.preset.brand]);
    assert.equal(result.preset.brand, '#3f404d');
    assert.equal(result.preset.semiDark, '#71717b');
    assert.deepEqual(result.themed, { buttons: ['#008000', '#008000', '#008000'], semiDark: '#008000' });
});

/**
 * T3-D10: in a theme with its own primary colour (teal, the theming guide's example) no preset
 * shows the kit's default purple: the dark preset is a neutral grey on the dark theme and in the
 * semi-dark menu; markup that names the custom colour before theme.js wrote its shades keeps teal.
 */
test('the presets and the custom colour never show the default purple in a teal theme', { skip }, async () => {
    const body = `<span id="page" class="text-brand-600">Sayfa</span><aside class="panel-sidebar dark bg-zinc-900"><span id="menu" class="text-brand-600">Menü</span></aside>`;
    const css = await stylesheet(body, '@theme { --color-brand-600: #0f766e; }');
    const result = inChrome({ css, body, measure: `
        const root = document.documentElement;
        const colours = () => [hex(getComputedStyle(document.getElementById('page')).color), hex(getComputedStyle(document.getElementById('menu')).color)];
        const result = { theme: colours() };
        root.dataset.brandColor = 'dark';
        result.darkPreset = colours();
        root.classList.add('dark');
        result.darkPresetDarkTheme = colours();
        root.classList.remove('dark');
        root.dataset.brandColor = 'custom';
        result.customWithoutShades = colours();
        root.style.setProperty('--brand-custom-600', '#1e3a8a');
        root.style.setProperty('--brand-custom-600-dark', '#5c6fa8');
        result.custom = colours();
        root.classList.add('dark');
        result.customDarkTheme = colours();
        return result;
    ` });

    assert.deepEqual(result, {
        theme: ['#0f766e', '#0f766e'],
        darkPreset: ['#3f404d', '#71717b'],
        darkPresetDarkTheme: ['#71717b', '#71717b'],
        customWithoutShades: ['#0f766e', '#0f766e'],
        custom: ['#1e3a8a', '#5c6fa8'],
        customDarkTheme: ['#5c6fa8', '#5c6fa8'],
    });
});
