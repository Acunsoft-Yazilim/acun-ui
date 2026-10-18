import test from 'node:test';
import assert from 'node:assert/strict';

// A page with acun:layout.head whose application JS (the runtime) did not load: the
// boot script still applies the theme, prints one error that says what to do, and
// registers stand-ins so that no component expression throws.

const consoleErrors = [];
const alpineWarnings = [];
const uncaught = [];
const error = console.error;
const warn = console.warn;
console.error = (...args) => consoleErrors.push(args.join(' '));
console.warn = (...args) => {
    if (String(args[0]).includes('Alpine')) alpineWarnings.push(String(args[0]));
    else warn(...args);
};
process.on('uncaughtException', (exception) => uncaught.push(exception.message));

globalThis.acunTestPage = {
    head: '<script type="application/json" data-acun-ui-config>{"scope":"app","theme":{"key":"default","light":"light","dark":"dark"},"targets":{"light":"#fafafa","dark":"#1f1f1f"},"colors":{"green":"#008933"},"mode":"dark","brandColor":"green","sidebarMode":"collapsed","navbarMode":null,"skin":"bordered","semiDark":null,"preferenceUrl":null,"defaults":{},"forced":false}</script>',
    boot: true,
    runtime: false,
};

const { Alpine, document, mount, render, settle, window } = await import('./browser.mjs');
const { fallbackComponents, fallbackStores } = await import('../../js/core/fallback.js');
const { drawer } = await import('../../js/components/drawer.js');
const { modal } = await import('../../js/components/modal.js');
const { registerStores } = await import('../../js/appearance/stores.js');

test.after(() => {
    console.error = error;
    console.warn = warn;
});

const html = render({
    page: `
        <div x-data><span id="menu" x-text="$store.sidebar.collapsed ? 'dar' : 'geniş'"></span><button id="tema" x-on:click="$store.appearance.toggle()">Tema</button></div>
        <acun:drawer.trigger name="d"><acun:button id="ac">Aç</acun:button></acun:drawer.trigger>
        <acun:drawer name="d" title="Panel"><input></acun:drawer>
        <acun:modal name="m" title="Modal"><input></acun:modal>
        <acun:command-palette :items="[['label' => 'Siparişler', 'href' => '#siparisler']]" />`,
});

test('the boot script applies the theme before the first paint without the runtime', () => {
    const root = document.documentElement;

    assert.equal(window.AcunUI, undefined);
    assert.equal(root.dataset.theme, 'dark');
    assert.ok(root.classList.contains('dark'));
    assert.equal(root.style.getPropertyValue('--ac-user-fill'), '#008933');
    assert.equal(root.dataset.skin, 'bordered');
    assert.equal(root.dataset.sidebarCollapsed, 'true');
});

test('one error names the problem; drawers, modals, the command palette and the stores stay quiet', async () => {
    await mount(html.page);

    document.getElementById('ac').click();
    document.getElementById('tema').click();
    document.querySelector('[aria-haspopup="dialog"]:not(#ac)').click();
    window.dispatchEvent(new window.CustomEvent('open-modal', { detail: { name: 'm' } }));
    await settle();
    await new Promise((resolve) => setTimeout(resolve, 50));

    assert.deepEqual(uncaught, []);
    assert.deepEqual(alpineWarnings, []);
    assert.equal(consoleErrors.filter((message) => message.includes('[Acun UI] JavaScript is not loaded')).length, 1);
    assert.equal(document.getElementById('menu').textContent, 'dar', 'The stand-in store shows the saved setting.');
    assert.equal(Alpine.$data(document.querySelector('[data-acun-drawer="d"]')).opened, false);
    assert.equal(document.querySelector('[data-acun-drawer="d"]').style.display, 'none', 'The drawer stays closed.');
});

test('the stand-ins have the names of the real components and stores', () => {
    const lifecycle = new Set(['init', 'destroy']);
    const names = (object) => Object.keys(object).filter((name) => !lifecycle.has(name)).sort();

    assert.deepEqual(names(fallbackComponents.acunDrawer()), names(drawer({ name: 'x' })));
    assert.deepEqual(names(fallbackComponents.acunModal()), names(modal({ name: 'x' })));

    const stores = {};
    registerStores({ store: (name, value) => { stores[name] = value; } }, { defaults: {} });
    const stand = fallbackStores({ defaults: {} });

    assert.deepEqual(Object.keys(stand).sort(), Object.keys(stores).sort());
    for (const store of Object.keys(stores)) {
        assert.deepEqual(names(stand[store]), names(stores[store]), store);
    }
});
