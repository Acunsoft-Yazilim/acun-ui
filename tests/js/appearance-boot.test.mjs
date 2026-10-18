import test from 'node:test';
import assert from 'node:assert/strict';

// The boot script (js/boot.js) before the first paint: data-theme and the user's colour from the
// island, and its watcher, which gives the semi-dark menu and the navbar the browser's saved
// choice as the parser adds them, before DOMContentLoaded (no flash of the server's value).

const PAGE = {
    scope: 'panel', theme: { key: 'default', light: 'light', dark: 'dark' }, targets: { light: '#fafafa', dark: '#1f1f1f' },
    colors: { green: '#008933' }, mode: 'dark', brandColor: 'green', sidebarMode: null, navbarMode: null, skin: null, contentWidth: null,
    semiDark: null, preferenceUrl: null, defaults: {}, forced: false,
};

globalThis.acunTestPage = { head: `<script type="application/json" data-acun-ui-config>${JSON.stringify(PAGE)}</script>`, boot: true, runtime: false };

const { document, window } = await import('./browser.mjs');
const { applyInitialAppearance, watchSections } = await import('../../js/appearance/theme.js');
const { readConfig } = await import('../../js/core/config.js');

const root = document.documentElement;
const microtask = () => Promise.resolve();

test('the boot script writes data-theme, class="dark" and the user\'s colour from the island', () => {
    assert.equal(root.dataset.theme, 'dark');
    assert.ok(root.classList.contains('dark'));
    assert.equal(root.style.getPropertyValue('--ac-user-fill'), '#008933');
    assert.equal(root.dataset.brandColor, undefined);
});

test('the watcher sets the saved semi-dark menu and navbar before DOMContentLoaded', async () => {
    window.localStorage.setItem('acun-ui:panel', JSON.stringify({ semiDark: true, navbar: 'static' }));
    // The page is still being parsed.
    let state = 'loading';
    Object.defineProperty(document, 'readyState', { configurable: true, get: () => state });

    const config = readConfig();
    const observer = watchSections(applyInitialAppearance(config), config);
    assert.ok(observer, 'It watches while the page loads.');

    // The parser adds the menu as the server drew it (no semi-dark), then the content area.
    document.body.insertAdjacentHTML('beforeend', '<div id="frame"><aside data-acun-semi-dark class="panel-sidebar"><nav id="menu"></nav></aside><div data-acun-navbar data-navbar="fixed"></div></div>');
    const menu = document.querySelector('[data-acun-semi-dark]');
    const content = document.querySelector('[data-acun-navbar]');
    // A mutation observer runs in a microtask: before the browser paints.
    await microtask();
    assert.equal(menu.dataset.theme, 'dark');
    assert.ok(menu.classList.contains('dark'));
    assert.equal(content.dataset.navbar, 'static');

    // DOMContentLoaded: the watcher stops; from then on the stores keep the sections.
    state = 'interactive';
    document.dispatchEvent(new window.Event('DOMContentLoaded'));
    document.body.insertAdjacentHTML('beforeend', '<aside data-acun-semi-dark id="late"></aside>');
    await microtask();
    assert.equal(document.getElementById('late').dataset.theme, undefined);

    document.getElementById('frame').remove();
    document.getElementById('late').remove();
    delete document.readyState;
    window.localStorage.clear();
});

test('a menu the server drew semi-dark loses it when the browser saved the light menu', async () => {
    window.localStorage.setItem('acun-ui:panel', JSON.stringify({ semiDark: false }));
    let state = 'loading';
    Object.defineProperty(document, 'readyState', { configurable: true, get: () => state });

    const config = { ...readConfig(), semiDark: null };
    watchSections(applyInitialAppearance(config), config);
    document.body.insertAdjacentHTML('beforeend', '<aside data-acun-semi-dark data-theme="dark" class="panel-sidebar dark"></aside>');
    const menu = document.querySelector('[data-acun-semi-dark]');
    await microtask();
    assert.equal(menu.dataset.theme, undefined);
    assert.equal(menu.classList.contains('dark'), false);

    state = 'complete';
    document.dispatchEvent(new window.Event('DOMContentLoaded'));
    menu.remove();
    delete document.readyState;
    window.localStorage.clear();
});
