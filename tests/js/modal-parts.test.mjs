import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, document, mount, settle, window } from './browser.mjs';
import { chrome, inChrome } from './chrome.mjs';
import { component, morphComponent, renderLivewire } from './livewire.mjs';
import { kitCss } from './tailwind.mjs';

// acun:modal with its parts in a nested Livewire component, in real Livewire output
// (tests/Fixtures/livewire): the page (musteri-sayfasi) draws the modal around the nested
// customer form (musteri-formu), which starts with modal.header. Livewire draws the form
// whole only on the first render; the page's own updates leave an empty stub there, and the
// form's own updates draw its parts without the modal around them (no name on the server).

const { page, classic } = renderLivewire({ page: 'musteri-sayfasi', classic: 'sepet-sayfasi' });
const dialog = () => document.querySelector('[data-acun-modal="musteri-ekle"] [role="dialog"]');

test('the header of a nested component names the dialog, also after the page\'s and its own updates', async () => {
    await mount(page.first);

    // The first render: the server finds the header in the nested component.
    assert.equal(dialog().getAttribute('aria-labelledby'), 'musteri-ekle-title');
    assert.equal(dialog().getAttribute('aria-describedby'), 'musteri-ekle-description');
    assert.equal(dialog().hasAttribute('aria-label'), false);

    for (const round of [1, 2]) {
        // The page's update: a stub where the form is, so the server prints the fallback name.
        assert.match(page.updates['musteri-sayfasi'], /aria-label="Pencere"/);
        morphComponent(component('musteri-sayfasi'), page.updates['musteri-sayfasi']);
        await settle();

        assert.ok(document.getElementById('musteri-ekle-title'), `round ${round}: the form is still there`);
        assert.equal(dialog().getAttribute('aria-labelledby'), 'musteri-ekle-title', `round ${round}: the page's update keeps the header's name`);
        assert.equal(dialog().getAttribute('aria-describedby'), 'musteri-ekle-description', `round ${round}`);
        assert.equal(dialog().hasAttribute('aria-label'), false, `round ${round}`);

        // The form's own update: its header has no name on the server, the browser keeps the ids.
        morphComponent(component('musteri-formu'), page.updates['musteri-formu']);
        await settle();

        assert.equal(document.getElementById('musteri-ekle-title')?.textContent.trim(), 'Yeni müşteri', `round ${round}: the title keeps its id`);
        assert.ok(document.getElementById('musteri-ekle-description'), `round ${round}: the description keeps its id`);
        assert.equal(dialog().getAttribute('aria-labelledby'), 'musteri-ekle-title', `round ${round}`);
    }
});

test('the parts of a nested component take the panel in place of the padded body, on every update', async () => {
    await mount(page.first);
    const body = () => component('musteri-formu').parentElement;

    // The modal cannot know on the server what a nested component draws (a stub on the page's
    // updates): the padded body stays, and gives way to the parts in the browser (CSS :has,
    // see PartsModalDrawerTest). The page's update keeps exactly the same classes.
    assert.equal(body().parentElement, dialog());
    const classes = body().getAttribute('class');
    assert.match(classes, /\[&:has\(>\[wire\\:id\]>:first-child:is\(\[data-slot=modal-header\],\[data-slot=modal-body\],\[data-slot=modal-footer\]\)\)\]:contents/);

    morphComponent(component('musteri-sayfasi'), page.updates['musteri-sayfasi']);
    await settle();

    assert.equal(body().parentElement, dialog(), 'the nested component stays in the same place');
    assert.equal(body().getAttribute('class'), classes);
});

// The layout as Chrome paints it (jsdom cannot evaluate :has); skipped without Chrome.
test('Chrome lays out a nested component\'s parts as the panel\'s rows, also after the page\'s update', { skip: !chrome && 'Chrome is not installed (CHROME_PATH)' }, async () => {
    // The DOM after the page's update (the nested component's DOM kept), and a nested
    // component with classic content (cart-line), which keeps the padded body.
    await mount(page.first);
    morphComponent(component('musteri-sayfasi'), page.updates['musteri-sayfasi']);
    await settle();
    const updated = document.querySelector('main').innerHTML;

    const layout = inChrome({
        // The kit's classes as the application's Tailwind builds them (its @source of the views).
        css: await kitCss(['modal.blade.php', 'modal/header.blade.php', 'modal/body.blade.php', 'modal/footer.blade.php'].map((file) => `packages/acun-ui/resources/views/components/ui/${file}`), '@import "tailwindcss";'),
        body: [page.first, updated, classic.first].map((html) => `<section>${html}</section>`).join(''),
        // Shown as when open (the server renders the dialog closed: hidden).
        measure: `
            document.querySelectorAll('[hidden]').forEach((element) => element.removeAttribute('hidden'));
            document.querySelectorAll('[data-acun-modal], [data-slot="modal-backdrop"], [data-slot="modal-panel"]').forEach((element) => element.setAttribute('data-open', ''));
            return [...document.querySelectorAll('section')].map((section) => {
                const panel = section.querySelector('[data-slot="modal-panel"]');
                const root = [...panel.querySelectorAll('*')].find((element) => element.hasAttribute('wire:id'));
                const left = (element) => element ? Math.round(element.getBoundingClientRect().left - panel.getBoundingClientRect().left) : null;
                return {
                    body: getComputedStyle(root.parentElement).display,
                    root: getComputedStyle(root).display,
                    header: left(section.querySelector('[data-slot="modal-header"]')),
                    footer: left(section.querySelector('[data-slot="modal-footer"]')),
                    content: left(root.firstElementChild),
                };
            });
        `,
    });

    // The parts are the panel's rows: no padded body around them (they used to sit 20px in).
    assert.deepEqual(layout[0], { body: 'contents', root: 'contents', header: 0, footer: 0, content: 0 });
    assert.deepEqual(layout[1], layout[0], 'the same after the page\'s update');
    // Classic content keeps the padded body.
    assert.deepEqual(layout[2], { body: 'block', root: 'block', header: null, footer: null, content: 20 });
});

test('the parts still close the modal after the nested component\'s own update', async () => {
    await mount(page.first);
    morphComponent(component('musteri-formu'), page.updates['musteri-formu']);
    await settle();
    const modal = AcunUI.plugins.get(document.querySelector('[data-acun-modal="musteri-ekle"]'), 'modal');

    modal.open();
    await settle();
    assert.equal(modal.isOpen, true);

    document.querySelector('[data-slot="modal-close"] button').click();
    await settle();
    assert.equal(modal.isOpen, false, 'modal.close (data-acun-close-now) closes the modal the click is in.');

    modal.open();
    await settle();
    document.querySelector('[data-slot="modal-header"] button[aria-label]').click();
    await settle();
    assert.equal(modal.isOpen, false, 'The header X (data-acun-close) closes it too.');
});

test('a header that ends up outside a modal says so instead of throwing', async () => {
    // The nested component's own update, put on a page without the modal around it.
    await mount(`<div x-data>${page.updates['musteri-formu']}</div>`);
    const errors = [];
    const uncaught = [];
    const consoleError = console.error;
    const consoleWarn = console.warn;
    const onError = (event) => { uncaught.push(event.error ?? event.message); event.preventDefault(); };
    console.error = (...args) => errors.push(args.join(' '));
    console.warn = (...args) => errors.push(args.join(' '));
    window.addEventListener('error', onError);

    try {
        assert.equal(document.querySelector('[data-slot="modal-header"] h3').hasAttribute('id'), false, 'no modal, no id');

        document.querySelector('[data-slot="modal-header"] button[aria-label]').click();
        document.querySelector('[data-slot="modal-close"] button').click();
        await settle();
    } finally {
        console.error = consoleError;
        console.warn = consoleWarn;
        window.removeEventListener('error', onError);
    }

    assert.deepEqual(uncaught, [], 'no ReferenceError');
    assert.equal(errors.length, 2);
    assert.match(errors[0], /^\[Acun UI\] data-acun-close is not inside a component it can close\./);
    assert.match(errors[1], /^\[Acun UI\] data-acun-close-now is not inside a component it can close\./);
});

test('a page update while the modal is open keeps it open (Livewire renders it closed on every update)', async () => {
    await mount(page.first);
    const root = document.querySelector('[data-acun-modal="musteri-ekle"]');
    const modal = AcunUI.plugins.get(root, 'modal');

    modal.open();
    await settle();
    assert.match(page.updates['musteri-sayfasi'], /data-acun-modal="musteri-ekle"\s+hidden/, 'The server renders it closed.');

    for (const update of [page.updates['musteri-sayfasi'], page.updates['musteri-formu']]) {
        morphComponent(component(update === page.updates['musteri-formu'] ? 'musteri-formu' : 'musteri-sayfasi'), update);
        await settle();

        assert.equal(AcunUI.plugins.get(root, 'modal'), modal, 'The same element and instance.');
        assert.equal(modal.isOpen, true);
        assert.equal(root.hidden, false, 'The morph kept the browser\'s state attributes.');
        assert.ok(root.hasAttribute('data-open'));
        assert.ok(root.querySelector('[data-slot="modal-panel"]').hasAttribute('data-open'));
        assert.equal(AcunUI.scrollLock.count, 1);
    }

    modal.close();
    await settle();
    assert.equal(root.hidden, true);
    assert.equal(AcunUI.scrollLock.count, 0);
});
