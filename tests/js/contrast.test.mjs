import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { brandShades } from '../../js/appearance/theme.js';
import { chrome, componentSources, inChrome, stylesheet } from './chrome.mjs';

// Contrast as Chrome paints it (see chrome.mjs): the placeholders of the fields and of the command
// palette, the counter on a button, the focus ring token, every component's focus ring and the
// user's colour in a theme of another colour. The mode is data-theme on <html>; the semi-dark menu is data-theme="dark"; the
// user's colour is the --ac-user-* variables js/appearance/theme.js writes on <html>.
// Text needs 4.5:1, a focus frame 3:1 (WCAG AA).

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
const page = `<div id="card" class="m-4 rounded-sm bg-surface p-4">${markup}</div>`;
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
    root.dataset.theme = 'dark';
    root.classList.add('dark');
    const dark = measure();
    root.dataset.theme = 'light';
    root.classList.remove('dark');
    // A custom primary colour with dark text (js/appearance/theme.js measures its shades).
    Object.entries(window.customYellow).forEach(([name, value]) => root.style.setProperty(name, value));
    const yellow = textContrast(document.querySelector('[data-case="primary"] button > span.rounded-full'));
    return { light, dark, yellow };
`;

/** The variables applyColor writes on <html> for a colour (and its own dark fill). */
const userVariables = (color, dark = null) => {
    const shades = brandShades(color);
    const own = dark ? brandShades(dark) : null;
    return {
        '--ac-user-fill': shades.fill, '--ac-user-fill-hover': shades.hover, '--ac-user-on-fill': shades.onFill, '--ac-user-tone': shades.tone, '--ac-user-tone-dark': shades.toneDark,
        ...(own ? { '--ac-user-fill-dark': own.fill, '--ac-user-fill-hover-dark': own.hover, '--ac-user-on-fill-dark': own.onFill } : {}),
    };
};
const customYellow = userVariables('#ffd400');

test('placeholders, the command palette and button counters read 4.5:1 in both themes', { skip }, async () => {
    const css = await stylesheet(markup);
    const { light, dark, yellow: onYellow } = inChrome({ css, body: `${page}<script>window.customYellow = ${JSON.stringify(customYellow)};</script>`, measure: measureText, htmlAttributes: 'data-theme="light"' });

    const failing = Object.entries({ ...Object.fromEntries(Object.entries(light).map(([k, v]) => [`light ${k}`, v])), ...Object.fromEntries(Object.entries(dark).map(([k, v]) => [`dark ${k}`, v])) })
        .filter(([what, value]) => (what.includes('fill alpha') ? value !== 0 : value < 4.5));
    // Before: dark placeholders 3.41, the command palette's 3.82, counters 3.51-3.79 (YENİ-O2, YENİ-O12).
    assert.deepEqual(Object.fromEntries(failing), {});
    assert.equal(Object.keys(dark).filter((key) => key.startsWith('counter: ')).length, variants.length + 1, 'Every variant was measured.');
    assert.ok(onYellow >= 4.5, `The counter on a custom yellow (dark text): ${onYellow}:1`);
});

/**
 * TEMA-9: the focus ring of the kit is one token, --ac-focus: the primary text tone of the element's
 * theme (the dark theme's in the semi-dark menu) unless a theme sets it in its block.
 */
test('the focus ring is --ac-focus, the primary colour unless a theme sets it', { skip }, async () => {
    // The semi-dark menu: data-theme="dark" in the light mode; the dark preset (its own dark fill).
    const body = `${page}<aside data-theme="dark" class="panel-sidebar bg-surface"><span id="semi-dark">${menuButton}</span></aside>`;
    const css = await stylesheet(body);
    const style = Object.entries(userVariables('#3f404d', '#474747')).map(([name, value]) => `${name}: ${value}`).join('; ');
    const result = inChrome({ css, body, htmlAttributes: `data-theme="light" style="${style}"`, measure: `
        const ring = (element) => { element.focus({ focusVisible: true }); return hex(getComputedStyle(element).outlineColor); };
        const buttons = [...document.querySelectorAll('[data-case="focus"] :is(button, a)')];
        const preset = { buttons: buttons.map(ring), semiDark: ring(document.querySelector('#semi-dark button')) };
        // A theme's own focus colour, in its block (every themed element).
        const theme = document.createElement('style');
        theme.textContent = ':root, [data-theme] { --ac-focus: rgb(0, 128, 0); }';
        document.head.append(theme);
        const themed = { buttons: buttons.map(ring), semiDark: ring(document.querySelector('#semi-dark button')) };
        return { preset, themed };
    ` });

    // The dark preset: #3f404d on the page; the semi-dark menu takes its dark tone (3:1 on the dark fields).
    assert.deepEqual(result.preset.buttons, ['#3f404d', '#3f404d', '#3f404d']);
    assert.equal(result.preset.semiDark, brandShades('#3f404d').toneDark);
    assert.deepEqual(result.themed, { buttons: ['#008000', '#008000', '#008000'], semiDark: '#008000' });
});

/**
 * T3-D10: in a theme with its own primary colour (teal, the theming guide's example) no preset
 * shows the kit's default purple: the dark preset is a neutral grey on the dark theme and in the
 * semi-dark menu; without the user's variables the page keeps teal.
 */
test('the presets and the custom colour never show the default purple in a teal theme', { skip }, async () => {
    const body = `<span id="page" class="text-primary-ink">Sayfa</span><aside data-theme="dark" class="panel-sidebar bg-surface"><span id="menu" class="text-focus">Menü</span></aside>`;
    const css = await stylesheet(body, ':root, [data-theme] { --ac-tone-primary: #0f766e; }');
    const presets = { dark: userVariables('#3f404d', '#474747'), navy: userVariables('#1e3a8a') };
    const result = inChrome({ css, body, htmlAttributes: 'data-theme="light"', measure: `
        const presets = ${JSON.stringify(presets)};
        const root = document.documentElement;
        const colours = () => [hex(getComputedStyle(document.getElementById('page')).color), hex(getComputedStyle(document.getElementById('menu')).color)];
        const use = (variables) => { root.removeAttribute('style'); Object.entries(variables ?? {}).forEach(([name, value]) => root.style.setProperty(name, value)); };
        const result = { theme: colours() };
        use(presets.dark);
        result.darkPreset = colours();
        root.dataset.theme = 'dark';
        result.darkPresetDarkTheme = colours();
        root.dataset.theme = 'light';
        use(null);
        result.noColour = colours();
        use(presets.navy);
        result.custom = colours();
        root.dataset.theme = 'dark';
        result.customDarkTheme = colours();
        return result;
    ` });

    const darkTone = brandShades('#3f404d').toneDark;
    const navyDark = brandShades('#1e3a8a').toneDark;
    // The dark theme's text on a surface is 66% of the tone with white (--ac-primary-ink).
    const ink = (tone) => `#${[1, 3, 5].map((i) => Math.round(Number.parseInt(tone.slice(i, i + 2), 16) * 0.66 + 255 * 0.34).toString(16).padStart(2, '0')).join('')}`;
    assert.deepEqual(result, {
        theme: ['#0f766e', '#0f766e'],
        darkPreset: ['#3f404d', darkTone],
        darkPresetDarkTheme: [ink(darkTone), darkTone],
        noColour: ['#0f766e', '#0f766e'],
        custom: ['#1e3a8a', navyDark],
        customDarkTheme: [ink(navyDark), navyDark],
    });
});

/**
 * WCAG 2.2 (non-text contrast, focus appearance): the keyboard focus ring of every button variant
 * and of the kit's other controls stands out 3:1 on what it sits on (the page, a surface, a field,
 * a sunken area: the ring is drawn around the control), in the light and the dark theme, in a dark
 * section of a light page and a light island of a dark page, with the theme's colour and two custom
 * ones (a yellow that needs dark text, a navy that needs a lighter tone in the dark theme). Before:
 * the success, warning and info buttons' rings (their -line shade) gave 1.75-2.21:1, the white,
 * ghost and light buttons' (line-hover) 2.39-2.62:1, and the checkbox drew none (its outline-none
 * cancelled the ring).
 */
test('every focus ring stands out 3:1 on the page, a surface, a field and a sunken area, in every theme and section', { skip }, async () => {
    // [case, the element that takes the focus, the element that draws the ring (a peer's or the parent's) or none].
    const controls = {
        ...Object.fromEntries(variants.map((variant) => [`button ${variant}`, [`<acun:button variant="${variant}">Sepet</acun:button>`, 'button']])),
        'link button': ['<acun:button href="/sepet" variant="outline-success">Sepet</acun:button>', 'a'],
        'tab (underline)': [`<acun:tabs :tabs="['ozet' => 'Özet', 'kargo' => 'Kargo']"><acun:tab-panel value="ozet">-</acun:tab-panel><acun:tab-panel value="kargo">-</acun:tab-panel></acun:tabs>`, '[role="tab"]'],
        'tab (pills)': [`<acun:tabs variant="pills" :tabs="['ozet' => 'Özet', 'kargo' => 'Kargo']"><acun:tab-panel value="ozet">-</acun:tab-panel><acun:tab-panel value="kargo">-</acun:tab-panel></acun:tabs>`, '[role="tab"]'],
        'tab (cards)': [`<acun:tabs variant="cards" :tabs="['ozet' => 'Özet', 'kargo' => 'Kargo']"><acun:tab-panel value="ozet">-</acun:tab-panel><acun:tab-panel value="kargo">-</acun:tab-panel></acun:tabs>`, '[role="tab"]'],
        chip: [`<acun:chip-group name="durum" :options="['hepsi' => 'Tümü', 'bekliyor' => 'Bekleyen']" value="hepsi" />`, 'input', 'next'],
        segmented: [`<acun:segmented name="donem" :options="['gun' => 'Gün', 'hafta' => 'Hafta']" value="gun" />`, 'input', 'next'],
        switch: ['<acun:switch name="bildirim" label="Bildirim" />', 'input', 'next'],
        checkbox: ['<acun:checkbox name="kabul" label="Kabul" />', 'input'],
        radio: ['<acun:radio name="odeme" value="kart" label="Kart" />', 'input'],
        input: ['<acun:input name="ad" />', 'input'],
        'invalid input': ['<acun:input name="eposta" error="Geçersiz" />', 'input'],
        select: [`<acun:select name="sehir" :options="['ist' => 'İstanbul']" />`, 'select'],
        textarea: ['<acun:textarea name="not" />', 'textarea'],
        'input group': ['<acun:input-group name="tutar"><x-slot:prefix>₺</x-slot:prefix></acun:input-group>', 'input', 'parent'],
        'search button': ['<acun:search name="q" button="Ara" />', 'button[type="submit"]'],
        accordion: ['<acun:accordion><acun:accordion.item name="kargo" title="Kargo">-</acun:accordion.item></acun:accordion>', 'button'],
        collapse: ['<acun:collapse label="Ayrıntı">-</acun:collapse>', 'button'],
        'sortable column': ['<table><thead><tr><acun:sortable-th field="ad" sort-field="ad" sort-direction="asc">Ad</acun:sortable-th></tr></thead></table>', 'button'],
        'theme toggle': ['<acun:theme-toggle />', 'button'],
        'colour swatch': ['<acun:brand-color-picker />', 'input[type="radio"]', 'next'],
        'custom colour': ['<acun:brand-color-picker />', 'input[type="color"]', 'parent'],
        'mode choice': ['<acun:theme-switcher as="inline" sections="mode" :reset="false" />', 'input[type="radio"]', 'next'],
    };
    const html = render(Object.fromEntries(Object.entries(controls).map(([name, [template]]) => [name, template])));
    const block = Object.entries(controls).map(([name, [, target, drawer = 'self']]) => `<div data-case="${name}" data-target='${target}' data-drawer="${drawer}">${html[name]}</div>`).join('\n');
    // One copy on the page, one in a section; the script paints their wrapper with each background in turn.
    const body = `<i hidden class="bg-page bg-surface bg-field bg-sunken"></i><main data-ground class="p-4">${block}</main><div id="section"><div data-ground class="p-4">${block}</div></div>`;
    const css = await stylesheet(body, componentSources('acun-ui'));
    const colours = { theme: {}, yellow: userVariables('#ffd400'), navy: userVariables('#1e3a8a') };
    const result = inChrome({ css, body, htmlAttributes: 'data-theme="light"', measure: `try {
        const colours = ${JSON.stringify(colours)};
        const html = document.documentElement;
        const section = document.getElementById('section');
        const rows = [];
        for (const [colour, variables] of Object.entries(colours)) {
            html.removeAttribute('style');
            Object.entries(variables).forEach(([name, value]) => html.style.setProperty(name, value));
            for (const mode of ['light', 'dark']) {
                html.dataset.theme = mode;
                section.dataset.theme = mode === 'light' ? 'dark' : 'light';
                for (const [where, ground] of [[mode + ' page', document.querySelector('main')], [mode === 'light' ? 'dark section' : 'light island', section.firstElementChild]]) {
                    for (const background of ['page', 'surface', 'field', 'sunken']) {
                        ground.className = 'p-4 bg-' + background;
                        ground.querySelectorAll('[data-case]').forEach((wrapper) => {
                            const target = wrapper.querySelector(wrapper.dataset.target);
                            const drawer = { self: target, next: target.nextElementSibling, parent: target.parentElement }[wrapper.dataset.drawer];
                            rows.push({ at: colour + ' / ' + where + ' / ' + background, control: wrapper.dataset.case, rings: focusRings(target, drawer) });
                        });
                    }
                }
            }
        }
        return { rows };
    } catch (error) { return { error: error.stack }; }` });
    assert.equal(result.error, undefined);

    // 3 colours × 2 modes × (page + section) × 4 backgrounds, every control.
    assert.equal(result.rows.length, 3 * 2 * 2 * 4 * Object.keys(controls).length);
    const missing = result.rows.filter(({ rings }) => !rings.length).map(({ at, control }) => `${at}: ${control} draws no focus ring`);
    const failing = result.rows.flatMap(({ at, control, rings }) => rings.filter(({ ratio }) => !(ratio >= 3)).map(({ kind, colour, ratio }) => `${at}: ${control} ${kind} ${colour} ${ratio}:1`));
    assert.deepEqual([...missing, ...failing].slice(0, 20), [], `${missing.length} without a ring, ${failing.length} below 3:1`);
});
