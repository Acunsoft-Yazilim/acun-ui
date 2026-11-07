import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chrome, openPage } from './cdp.mjs';
import { renderBlade } from './css-samples.mjs';
import { stylesheet } from './chrome.mjs';

// The kit's modal and drawer, rendered from their Blade components, in headless Chrome with real clicks and keys: a
// page with the compiled stylesheet and the runtime's browser build (dist/acun-ui.js, what @acunUiScripts loads),
// no Alpine and no Livewire, so the plugins alone drive them (a Livewire page runs the same plugins). They open from
// buttons (data-acun-open) and close by Escape, the backdrop and their buttons; the focus goes back to the button
// that opened them, the page scroll is locked while they are open, and they send their events under the new and the
// first names.

const dist = new URL('../../dist/', import.meta.url);

const markup = renderBlade({ dialogs: `<div>
    <acun:button id="open-modal" data-acun-open="siparis-ozeti">Sipariş özetini aç</acun:button>
    <acun:button id="open-drawer" variant="white" data-acun-open="siparis-filtreleri">Filtreler</acun:button>
</div>
<acun:modal name="siparis-ozeti" title="Sipariş SP-1048">
    <acun:description-list>
        <acun:description-list.item label="Müşteri">Ayşe Yılmaz</acun:description-list.item>
        <acun:description-list.item label="Tutar">₺2.499,00</acun:description-list.item>
    </acun:description-list>
    <acun:slot:footer>
        <acun:button variant="white" id="modal-cancel" data-acun-close>Kapat</acun:button>
        <acun:button id="modal-confirm" data-acun-close>Onayla</acun:button>
    </acun:slot:footer>
</acun:modal>
<acun:drawer name="siparis-filtreleri" title="Filtreler">
    <acun:select name="filtre_durum" label="Durum" :options="['' => 'Tümü', 'paid' => 'Ödendi', 'new' => 'Yeni']" />
    <acun:input name="filtre_tutar" label="En düşük tutar" />
    <acun:slot:footer>
        <acun:button id="drawer-apply" data-acun-close>Uygula</acun:button>
    </acun:slot:footer>
</acun:drawer>` });

// The page's CSS: the kit's Tailwind sources compiled for this markup (as an application's build does).
const css = await stylesheet(Object.values(markup).join('\n'));

function page() {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-dialogs-'));
    const file = path.join(dir, 'index.html');
    fs.writeFileSync(file, `<!doctype html><html lang="tr" data-theme="light"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>${css}</style>
</head><body><main style="padding: 2rem">${markup.dialogs}</main>
<script src="${new URL('acun-ui.js', dist).href}"></script>
</body></html>`);

    return { url: pathToFileURL(file).href, remove: () => fs.rmSync(dir, { recursive: true, force: true }) };
}

const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';

const state = (name, attribute) => `(() => {
    const root = document.querySelector('[${attribute}="${name}"]');
    const panel = root.querySelector('[data-slot$="-panel"]');
    const active = document.activeElement;
    return {
        hidden: root.hidden,
        panelHidden: panel.hidden,
        open: root.hasAttribute('data-open') || panel.hasAttribute('data-open'),
        visible: panel.checkVisibility(),
        focusInside: panel.contains(active),
        focused: active?.id || active?.getAttribute('aria-label') || active?.tagName,
        locked: document.documentElement.classList.contains('scroll-locked'),
    };
})()`;

test('the modal and the drawer work with dist/acun-ui.js alone', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url);
    try {
        assert.deepEqual(await browser.evaluate('({ alpine: typeof window.Alpine, livewire: typeof window.Livewire, runtime: window.AcunUI?.initialized, plugins: window.AcunUI?.plugins.names })'), {
            alpine: 'undefined', livewire: 'undefined', runtime: true, plugins: ['modal', 'drawer', 'dropdown', 'popover', 'tooltip', 'tabs', 'accordion', 'collapse', 'toasts', 'copy', 'inline-confirm', 'dismissible', 'card', 'reveal', 'color-input', 'search', 'command-palette', 'theme-switcher', 'passkey', 'scroll-shadows', 'download-text', 'table-scroll', 'pagination', 'carousel', 'carousel-controls'],
        });
        await browser.evaluate(`window.seen = []; for (const name of ['acun:modal:opened', 'acun:modal:closed', 'modal-closed', 'acun:drawer:opened', 'acun:drawer:closed', 'drawer-state-changed']) window.addEventListener(name, (event) => window.seen.push([name, event.detail]));`);

        const modal = () => browser.evaluate(state('siparis-ozeti', 'data-acun-modal'));
        const drawer = () => browser.evaluate(state('siparis-filtreleri', 'data-acun-drawer'));
        assert.deepEqual(await modal(), { hidden: true, panelHidden: false, open: false, visible: false, focusInside: false, focused: 'BODY', locked: false });

        // The modal: a click opens it, the focus goes in (its X first), the page is locked.
        await browser.click('#open-modal');
        await browser.settle();
        assert.deepEqual(await modal(), { hidden: false, panelHidden: false, open: true, visible: true, focusInside: true, focused: 'Kapat', locked: true });

        // Tab goes round inside it: X, Kapat, Onayla, back to the X.
        for (let i = 0; i < 3; i++) await browser.press('Tab');
        assert.equal((await modal()).focused, 'Kapat', 'Back on the X after the last button.');
        await browser.press('Tab', { shift: true });
        assert.equal((await modal()).focused, 'modal-confirm', 'Shift+Tab from the first goes to the last.');

        // Escape closes it; the focus goes back to the button that opened it.
        await browser.press('Escape');
        await browser.settle();
        assert.deepEqual(await modal(), { hidden: true, panelHidden: false, open: false, visible: false, focusInside: false, focused: 'open-modal', locked: false });

        // The backdrop closes it.
        await browser.click('#open-modal');
        await browser.settle();
        await browser.clickAt(8, 8);
        await browser.settle();
        assert.equal((await modal()).hidden, true, 'A click on the backdrop closes it.');
        assert.equal((await modal()).focused, 'open-modal');

        // A button with data-acun-close in it closes it.
        await browser.click('#open-modal');
        await browser.settle();
        await browser.click('#modal-confirm');
        await browser.settle();
        assert.equal((await modal()).hidden, true, 'Its own button closes it.');

        // The drawer: opens from its button, takes the focus, locks the page; Escape closes it.
        await browser.click('#open-drawer');
        await browser.settle();
        assert.deepEqual(await drawer(), { hidden: false, panelHidden: false, open: true, visible: true, focusInside: true, focused: 'Kapat', locked: true });
        assert.equal(await browser.evaluate(`document.querySelector('[data-acun-drawer="siparis-filtreleri"]').getAttribute('aria-modal')`), 'true');
        await browser.press('Escape');
        await browser.settle();
        assert.deepEqual(await drawer(), { hidden: true, panelHidden: false, open: false, visible: false, focusInside: false, focused: 'open-drawer', locked: false });

        // The backdrop beside the panel closes it.
        await browser.click('#open-drawer');
        await browser.settle();
        await browser.clickAt(40, 450);
        await browser.settle();
        assert.equal((await drawer()).hidden, true, 'A click beside the panel closes it.');
        assert.equal((await drawer()).focused, 'open-drawer');

        // The JavaScript API and the first event names (as a server sends them) work on the page too.
        await browser.evaluate(`AcunUI.drawer.open('siparis-filtreleri')`);
        await browser.settle();
        assert.equal((await drawer()).open, true);
        await browser.evaluate(`window.dispatchEvent(new CustomEvent('close-drawer', { detail: { name: 'siparis-filtreleri' } }))`);
        await browser.evaluate(`window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'siparis-ozeti' } }))`);
        await browser.settle();
        assert.deepEqual([(await drawer()).hidden, (await modal()).open], [true, true]);
        await browser.evaluate(`AcunUI.modal.close()`);
        await browser.settle();
        assert.equal((await modal()).hidden, true);

        const seen = await browser.evaluate('window.seen');
        assert.deepEqual(seen.slice(0, 3), [
            ['acun:modal:opened', { name: 'siparis-ozeti' }],
            ['acun:modal:closed', { name: 'siparis-ozeti', reason: 'user' }],
            ['modal-closed', { name: 'siparis-ozeti', reason: 'user' }],
        ]);
        assert.ok(seen.some(([name, detail]) => name === 'drawer-state-changed' && detail.name === 'siparis-filtreleri' && detail.open === true));
        assert.deepEqual(browser.problems, [], 'No error and no warning in the console.');
    } finally {
        await browser.close();
        remove();
    }
});
