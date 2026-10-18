import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

// acun:theme-switcher in the browser (jsdom, the real markup and runtime): a choice goes to the
// stores and is kept under the page's scope only (the storefront never changes the panel's colour or
// mode, nor the other way round), Reset forgets the scope's key alone, the radios follow the stores,
// the navbar button says the new mode, a choice that reloads the page opens the drawer again at once,
// the drawer keeps the focus and closes on Escape, and the PHP contrast helper agrees with the runtime.

const island = (values) => `<script type="application/json" data-acun-ui-config>${JSON.stringify(values)}</script>`;
const COLORS = { green: '#008933', red: '#d83838', dark: { light: '#3f404d', dark: '#474747' } };
const THEME = { key: 'default', light: 'light', dark: 'dark' };
const PAGE = { scope: 'shop', theme: THEME, targets: { light: '#fafafa', dark: '#1f1f1f' }, colors: COLORS, mode: null, brandColor: null, sidebarMode: null, navbarMode: null, skin: null, contentWidth: null, semiDark: null, preferenceUrl: null, defaults: {}, forced: false };

globalThis.acunTestPage = { head: island(PAGE) };

const { Alpine, document, mount, press, render, settle, sleep, window } = await import('./browser.mjs');
const { contrast } = await import('../../js/appearance/theme.js');

const root = document.documentElement;
const saved = (scope) => JSON.parse(window.localStorage.getItem(`acun-ui:${scope}`) ?? 'null');
const radio = (section, value) => document.querySelector(`[data-switcher-section="${section}"] input[type="radio"][value="${value}"]`);
const choose = async (section, value) => {
    radio(section, value).click();
    await settle();
};

/** Puts a newer island on the page (as wire:navigate does) and lets the stores read it. */
async function navigateTo(values) {
    document.querySelectorAll('script[data-acun-ui-config]').forEach((script, index) => index > 0 && script.remove());
    const script = document.createElement('script');
    script.type = 'application/json';
    script.dataset.acunUiConfig = '';
    script.textContent = JSON.stringify({ ...PAGE, ...values });
    document.head.append(script);
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));
    await settle();
}

const html = render({
    inline: '<acun:theme-switcher as="inline" />',
    drawer: `<acun:theme-switcher as="drawer" :themes="[['key' => 'nova', 'name' => 'Nova', 'url' => '#nova', 'active' => true], ['key' => 'vivid', 'name' => 'Vivid', 'url' => '#vivid', 'look' => 'modern']]" />`,
    button: '<acun:theme-switcher />',
});

test('a choice in the storefront stays in the storefront; the panel keeps its own', async () => {
    window.localStorage.clear();
    window.localStorage.setItem('acun-ui:panel', JSON.stringify({ mode: 'light', brandColor: 'green' }));
    await mount(html.inline);

    await choose('mode', 'dark');
    assert.equal(root.dataset.theme, 'dark');
    assert.deepEqual(saved('shop'), { mode: 'dark' });
    await choose('color', 'red');
    assert.equal(root.style.getPropertyValue('--ac-user-fill'), '#d83838');
    assert.deepEqual(saved('shop'), { mode: 'dark', brandColor: 'red' });
    assert.deepEqual(saved('panel'), { mode: 'light', brandColor: 'green' }, 'The panel\'s choices are untouched.');

    // A panel page (wire:navigate): its own mode and colour, and the switcher shows them.
    await navigateTo({ scope: 'panel' });
    assert.equal(root.dataset.theme, 'light');
    assert.equal(root.style.getPropertyValue('--ac-user-fill'), '#008933');
    assert.equal(radio('mode', 'light').checked, true);
    assert.equal(radio('color', 'green').checked, true);
    assert.equal(radio('color', 'red').checked, false);

    await choose('mode', 'system');
    assert.deepEqual(saved('panel'), { mode: 'system', brandColor: 'green' });
    assert.deepEqual(saved('shop'), { mode: 'dark', brandColor: 'red' }, 'The storefront\'s choices are untouched.');

    // Back in the storefront: its choices again.
    await navigateTo({ scope: 'shop' });
    assert.equal(root.dataset.theme, 'dark');
    assert.equal(radio('mode', 'dark').checked, true);
    assert.equal(radio('color', 'red').checked, true);
    window.localStorage.clear();
});

test('Reset forgets the scope\'s saved choices and nothing else', async () => {
    Alpine.store('appearance').set('light');
    Alpine.store('brand').set('default');
    window.localStorage.clear();
    window.localStorage.setItem('acun-ui:panel', JSON.stringify({ mode: 'dark', skin: 'bordered' }));
    await mount(html.inline);
    await choose('mode', 'dark');
    await choose('color', 'green');
    assert.deepEqual(saved('shop'), { mode: 'dark', brandColor: 'green' });

    [...document.querySelectorAll('button')].find((button) => button.textContent.includes('Varsayılana döndür')).click();
    await settle();
    assert.equal(window.localStorage.getItem('acun-ui:shop'), null, 'The storefront\'s key is gone.');
    assert.deepEqual(saved('panel'), { mode: 'dark', skin: 'bordered' }, 'The panel keeps its key.');
    assert.equal(Alpine.store('appearance').value, 'system');
    assert.equal(root.style.getPropertyValue('--ac-user-fill'), '', 'The theme\'s own colour again.');
    assert.equal(radio('mode', 'system').checked, true);
    assert.equal(radio('color', 'default').checked, true);

    await sleep(150);
    assert.equal(document.querySelector('[data-theme-switcher] [role="status"]').textContent, 'Görünüm varsayılana döndü.', 'A screen reader hears what happened.');
    window.localStorage.clear();
});

test('every choice is a native radio in a group its heading names', async () => {
    await mount(html.inline);

    for (const group of document.querySelectorAll('[data-theme-switcher] [role="radiogroup"]')) {
        const heading = document.getElementById(group.getAttribute('aria-labelledby'));
        assert.ok(heading?.textContent.trim(), 'The group has a name.');
        const radios = group.querySelectorAll('input[type="radio"]');
        assert.ok(radios.length > 1);
        assert.equal(new Set([...radios].map((input) => input.name)).size, 1, 'One name: Tab enters once, the arrow keys move.');
    }

    // A store change elsewhere (the navbar button) moves the checked radio.
    Alpine.store('appearance').set('dark');
    await settle();
    assert.equal(radio('mode', 'dark').checked, true);
    assert.equal(radio('mode', 'light').checked, false);
    Alpine.store('appearance').set('light');
    window.localStorage.clear();
});

test('the navbar button switches the mode and says the new one', async () => {
    window.localStorage.clear();
    Alpine.store('appearance').set('light');
    await mount(html.button);
    const button = document.querySelector('[data-test="theme-toggle"]');

    button.click();
    await settle();
    assert.equal(root.dataset.theme, 'dark');
    assert.equal(button.nextElementSibling.getAttribute('role'), 'status');
    assert.equal(button.nextElementSibling.textContent, 'Koyu tema seçildi.');
    assert.deepEqual(saved('shop'), { mode: 'dark' });

    button.click();
    await settle();
    assert.equal(button.nextElementSibling.textContent, 'Açık tema seçildi.');
    window.localStorage.clear();
});

test('a choice that reloads the page opens the drawer again at once', async () => {
    window.sessionStorage.clear();
    await mount(html.drawer);
    const current = document.querySelector('[data-switcher-section="theme"] a[aria-current="true"]');
    assert.equal(current.getAttribute('href'), '#nova');
    assert.equal(document.querySelector('[data-switcher-section="theme"] [data-theme="vivid"]').className.includes('rounded-[12px]'), true, 'A miniature in the theme\'s own tokens and look.');

    document.querySelector('[data-switcher-section="theme"] a[href="#vivid"]').click();
    assert.equal(window.sessionStorage.getItem('acun-ui:shop:open'), 'theme-switcher', 'Kept under the scope: the panel\'s drawer stays closed.');

    // The next page: the drawer is open without the user asking, and the note is gone.
    await mount(html.drawer);
    await sleep(60);
    assert.equal(Alpine.$data(document.querySelector('[data-acun-drawer="theme-switcher"]')).opened, true);
    assert.equal(window.sessionStorage.getItem('acun-ui:shop:open'), null);
    window.sessionStorage.clear();
});

test('the drawer: the gear opens it, Tab stays in it, Escape closes it and the focus goes back', async () => {
    await mount(html.drawer);
    const gear = document.querySelector('button[aria-haspopup="dialog"]');
    assert.equal(gear.getAttribute('aria-controls'), 'theme-switcher');
    assert.equal(document.getElementById('theme-switcher').getAttribute('role'), 'dialog');
    assert.equal(document.getElementById('theme-switcher').getAttribute('aria-modal'), 'true');

    gear.focus();
    gear.click();
    await settle();
    const dialog = document.getElementById('theme-switcher');
    assert.ok(dialog.contains(document.activeElement), 'The focus moves into the drawer.');

    // Tab from the last control wraps to the first.
    const focusable = window.AcunUI.focus.focusable(dialog.querySelector('[x-ref=panel]'));
    focusable.at(-1).focus();
    press('Tab');
    assert.ok(dialog.contains(document.activeElement), 'Tab stays inside.');

    press('Escape');
    await sleep(250);
    assert.equal(Alpine.$data(dialog).opened, false);
    assert.equal(document.activeElement, gear, 'The focus goes back to the gear.');
});

test('Acun\\Ui\\Support\\Contrast measures as the runtime does', () => {
    const root = fileURLToPath(new URL('../../../../', import.meta.url));
    const colours = [];
    for (let r = 0; r < 256; r += 51) for (let g = 0; g < 256; g += 51) for (let b = 0; b < 256; b += 51) colours.push([r, g, b]);
    const hex = (rgb) => `#${rgb.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
    const pairs = colours.flatMap((colour) => [[hex(colour), '#ffffff'], [hex(colour), '#1f1f1f'], [hex(colour), '#fafafa']]);
    const php = JSON.parse(execFileSync('php', ['-r', 'require "vendor/autoload.php"; echo json_encode(array_map(fn ($pair) => [Acun\\Ui\\Support\\Contrast::ratio(...$pair), Acun\\Ui\\Support\\Contrast::whiteReads($pair[0])], json_decode(stream_get_contents(STDIN), true)));'], { cwd: root, input: JSON.stringify(pairs) }));
    const rgb = (value) => [1, 3, 5].map((i) => Number.parseInt(value.slice(i, i + 2), 16));

    pairs.forEach(([a, b], i) => {
        assert.ok(Math.abs(php[i][0] - contrast(rgb(a), rgb(b))) < 1e-9, `${a} on ${b}`);
        assert.equal(php[i][1], contrast([255, 255, 255], rgb(a)) >= 3, `white on ${a}`);
    });
});
