import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, document, mount, settle, window } from './browser.mjs';
import { chrome, inChrome } from './chrome.mjs';
import { component, morphComponent, renderLivewire } from './livewire.mjs';
import { kitCss } from './tailwind.mjs';

// acun:drawer with its parts in a nested Livewire component, in real Livewire output
// (tests/Fixtures/livewire): the page (siparis-sayfasi) draws the drawer around the nested
// order details (siparis-detayi), which start with drawer.header. Livewire draws the details
// whole only on the first render; the page's own updates leave an empty stub there, and the
// details' own updates draw their parts without the drawer around them (no name on the
// server). The same for the modal: modal-parts.test.mjs.

const { page } = renderLivewire({ page: 'siparis-sayfasi' });
const drawer = () => document.querySelector('[data-acun-drawer="siparis-detay"]');

test('the header of a nested component names the drawer, also after the page\'s and its own updates', async () => {
    await mount(page.first);

    // The first render: the server finds the header in the nested component.
    assert.equal(drawer().getAttribute('aria-labelledby'), 'siparis-detay-title');
    assert.equal(drawer().getAttribute('aria-describedby'), 'siparis-detay-description');
    assert.equal(drawer().hasAttribute('aria-label'), false);

    for (const round of [1, 2]) {
        // The page's update: a stub where the details are, so the server prints the fallback name.
        assert.match(page.updates['siparis-sayfasi'], /aria-label="Menü"/);
        morphComponent(component('siparis-sayfasi'), page.updates['siparis-sayfasi']);
        await settle();

        assert.ok(document.getElementById('siparis-detay-title'), `round ${round}: the details are still there`);
        assert.equal(drawer().getAttribute('aria-labelledby'), 'siparis-detay-title', `round ${round}: the page's update keeps the header's name`);
        assert.equal(drawer().getAttribute('aria-describedby'), 'siparis-detay-description', `round ${round}`);
        assert.equal(drawer().hasAttribute('aria-label'), false, `round ${round}`);

        // The details' own update: the header has no name on the server, the browser keeps the ids.
        morphComponent(component('siparis-detayi'), page.updates['siparis-detayi']);
        await settle();

        assert.equal(document.getElementById('siparis-detay-title')?.textContent.trim(), 'Sipariş #4821', `round ${round}: the title keeps its id`);
        assert.ok(document.getElementById('siparis-detay-description'), `round ${round}: the description keeps its id`);
        assert.equal(drawer().getAttribute('aria-labelledby'), 'siparis-detay-title', `round ${round}`);
    }
});

test('the parts of a nested component take the panel in place of the padded body, on every update', async () => {
    await mount(page.first);
    const body = () => component('siparis-detayi').parentElement;

    // As in the modal: the padded body stays and gives way to the parts in the browser (CSS :has).
    assert.equal(body().parentElement, drawer().querySelector('[data-slot="drawer-panel"]'));
    const classes = body().getAttribute('class');
    assert.match(classes, /\[&:has\(>\[wire\\:id\]>:first-child:is\(\[data-slot=drawer-header\],\[data-slot=drawer-body\],\[data-slot=drawer-footer\]\)\)\]:contents/);

    morphComponent(component('siparis-sayfasi'), page.updates['siparis-sayfasi']);
    await settle();

    assert.equal(body().parentElement, drawer().querySelector('[data-slot="drawer-panel"]'), 'the nested component stays in the same place');
    assert.equal(body().getAttribute('class'), classes);
});

// The layout as Chrome paints it (jsdom cannot evaluate :has); skipped without Chrome.
test('Chrome lays out a nested component\'s parts as the drawer\'s rows, also after the page\'s update', { skip: !chrome && 'Chrome is not installed (CHROME_PATH)' }, async () => {
    await mount(page.first);
    morphComponent(component('siparis-sayfasi'), page.updates['siparis-sayfasi']);
    await settle();
    const updated = document.querySelector('main').innerHTML;

    const layout = inChrome({
        // The kit's classes as the application's Tailwind builds them (its @source of the views).
        css: await kitCss(['drawer.blade.php', 'drawer/header.blade.php', 'drawer/body.blade.php', 'drawer/footer.blade.php'].map((file) => `packages/acun-ui/resources/views/components/ui/${file}`), '@import "tailwindcss";'),
        body: [page.first, updated].map((html) => `<section>${html}</section>`).join(''),
        // Shown as when open (the server renders the dialog closed: hidden).
        measure: `
            document.querySelectorAll('[hidden]').forEach((element) => element.removeAttribute('hidden'));
            document.querySelectorAll('[data-acun-drawer], [data-slot="drawer-backdrop"], [data-slot="drawer-panel"]').forEach((element) => element.setAttribute('data-open', ''));
            return [...document.querySelectorAll('section')].map((section) => {
                const panel = section.querySelector('[data-slot="drawer-panel"]');
                const root = [...panel.querySelectorAll('*')].find((element) => element.hasAttribute('wire:id'));
                const left = (element) => Math.round(element.getBoundingClientRect().left - panel.getBoundingClientRect().left);
                return {
                    body: getComputedStyle(root.parentElement).display,
                    root: getComputedStyle(root).display,
                    header: left(section.querySelector('[data-slot="drawer-header"]')),
                    footer: left(section.querySelector('[data-slot="drawer-footer"]')),
                };
            });
        `,
    });

    // The parts are the panel's rows: no padded body around them (they used to sit 20px in).
    assert.deepEqual(layout[0], { body: 'contents', root: 'contents', header: 0, footer: 0 });
    assert.deepEqual(layout[1], layout[0], 'the same after the page\'s update');
});

test('the parts still close the drawer after the nested component\'s own update', async () => {
    await mount(page.first);
    morphComponent(component('siparis-detayi'), page.updates['siparis-detayi']);
    await settle();

    const instance = AcunUI.plugins.get(drawer(), 'drawer');
    instance.open();
    await settle();
    assert.equal(instance.isOpen, true);

    document.querySelector('[data-slot="drawer-close"] button').click();
    await settle();
    assert.equal(instance.isOpen, false, 'drawer.close (data-acun-close) closes the drawer the click is in.');

    instance.open();
    await settle();
    document.querySelector('[data-slot="drawer-header"] button[aria-label]').click();
    await settle();
    assert.equal(instance.isOpen, false, 'The header X closes it too.');
});

test('a header that ends up outside a drawer says so instead of throwing', async () => {
    // The nested component's own update, put on a page without the drawer around it.
    await mount(`<div x-data>${page.updates['siparis-detayi']}</div>`);
    const errors = [];
    const uncaught = [];
    const consoleError = console.error;
    const consoleWarn = console.warn;
    const onError = (event) => { uncaught.push(event.error ?? event.message); event.preventDefault(); };
    console.error = (...args) => errors.push(args.join(' '));
    console.warn = (...args) => errors.push(args.join(' '));
    window.addEventListener('error', onError);

    try {
        assert.equal(document.querySelector('[data-slot="drawer-header"] h3').hasAttribute('id'), false, 'no drawer, no id');

        document.querySelector('[data-slot="drawer-header"] button[aria-label]').click();
        document.querySelector('[data-slot="drawer-close"] button').click();
        await settle();
    } finally {
        console.error = consoleError;
        console.warn = consoleWarn;
        window.removeEventListener('error', onError);
    }

    assert.deepEqual(uncaught, [], 'no ReferenceError');
    assert.equal(errors.length, 2);
    errors.forEach((message) => assert.match(message, /^\[Acun UI\] data-acun-close is not inside a component it can close\./));
});

test('a page update while the drawer is open keeps it open', async () => {
    await mount(page.first);
    const instance = AcunUI.plugins.get(drawer(), 'drawer');

    instance.open();
    await settle();
    morphComponent(component('siparis-sayfasi'), page.updates['siparis-sayfasi']);
    await settle();

    assert.equal(AcunUI.plugins.get(drawer(), 'drawer'), instance);
    assert.equal(instance.isOpen, true);
    assert.equal(drawer().hidden, false);
    assert.ok(drawer().querySelector('[data-slot="drawer-panel"]').hasAttribute('data-open'));

    instance.close();
    await settle();
    assert.equal(drawer().hidden, true);
});
