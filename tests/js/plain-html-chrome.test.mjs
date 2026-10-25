import test from 'node:test';
import assert from 'node:assert/strict';
import { chrome, openPage, wait } from './cdp.mjs';

// examples/html/index.html (npm run build:example) in headless Chrome, with real clicks and keys: a plain HTML
// page with the compiled stylesheet and the runtime's browser build (dist/acun-ui.js), no Alpine and no
// Livewire. Its modal and drawer open from buttons (data-acun-open) and close by Escape, the backdrop and their
// buttons; the focus goes back to the button that opened them, the page scroll is locked while they are open,
// and they send their events under the new and the first names.

const page = new URL('../../../../examples/html/index.html', import.meta.url);
const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';
// Longer than the leave transitions (150–200 ms), so the closed dialog is hidden again.
const TRANSITIONS = 350;

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

test('the modal and the drawer of the HTML example work with dist/acun-ui.js alone', { skip }, async () => {
    const browser = await openPage(page.href);
    try {
        assert.deepEqual(await browser.evaluate('({ alpine: typeof window.Alpine, livewire: typeof window.Livewire, runtime: window.AcunUI?.initialized, plugins: window.AcunUI?.plugins.names })'), {
            alpine: 'undefined', livewire: 'undefined', runtime: true, plugins: ['modal', 'drawer'],
        });
        await browser.evaluate(`window.seen = []; for (const name of ['acun:modal:opened', 'acun:modal:closed', 'modal-closed', 'acun:drawer:opened', 'acun:drawer:closed', 'drawer-state-changed']) window.addEventListener(name, (event) => window.seen.push([name, event.detail]));`);

        const modal = () => browser.evaluate(state('siparis-ozeti', 'data-acun-modal'));
        const drawer = () => browser.evaluate(state('siparis-filtreleri', 'data-acun-drawer'));
        assert.deepEqual(await modal(), { hidden: true, panelHidden: false, open: false, visible: false, focusInside: false, focused: 'BODY', locked: false });

        // The modal: a click opens it, the focus goes in (its X first), the page is locked.
        await browser.click('#open-modal');
        await wait(TRANSITIONS);
        assert.deepEqual(await modal(), { hidden: false, panelHidden: false, open: true, visible: true, focusInside: true, focused: 'Kapat', locked: true });

        // Tab goes round inside it: X, Kapat, Onayla, back to the X.
        for (let i = 0; i < 3; i++) await browser.press('Tab');
        assert.equal((await modal()).focused, 'Kapat', 'Back on the X after the last button.');
        await browser.press('Tab', { shift: true });
        assert.equal((await modal()).focused, 'modal-confirm', 'Shift+Tab from the first goes to the last.');

        // Escape closes it; the focus goes back to the button that opened it.
        await browser.press('Escape');
        await wait(TRANSITIONS);
        assert.deepEqual(await modal(), { hidden: true, panelHidden: false, open: false, visible: false, focusInside: false, focused: 'open-modal', locked: false });

        // The backdrop closes it.
        await browser.click('#open-modal');
        await wait(TRANSITIONS);
        await browser.clickAt(8, 8);
        await wait(TRANSITIONS);
        assert.equal((await modal()).hidden, true, 'A click on the backdrop closes it.');
        assert.equal((await modal()).focused, 'open-modal');

        // A button with data-acun-close in it closes it.
        await browser.click('#open-modal');
        await wait(TRANSITIONS);
        await browser.click('#modal-confirm');
        await wait(TRANSITIONS);
        assert.equal((await modal()).hidden, true, 'Its own button closes it.');

        // The drawer: opens from its button, takes the focus, locks the page; Escape closes it.
        await browser.click('#open-drawer');
        await wait(TRANSITIONS);
        assert.deepEqual(await drawer(), { hidden: false, panelHidden: false, open: true, visible: true, focusInside: true, focused: 'Kapat', locked: true });
        assert.equal(await browser.evaluate(`document.querySelector('[data-acun-drawer="siparis-filtreleri"]').getAttribute('aria-modal')`), 'true');
        await browser.press('Escape');
        await wait(TRANSITIONS);
        assert.deepEqual(await drawer(), { hidden: true, panelHidden: false, open: false, visible: false, focusInside: false, focused: 'open-drawer', locked: false });

        // The backdrop beside the panel closes it.
        await browser.click('#open-drawer');
        await wait(TRANSITIONS);
        await browser.clickAt(40, 450);
        await wait(TRANSITIONS);
        assert.equal((await drawer()).hidden, true, 'A click beside the panel closes it.');
        assert.equal((await drawer()).focused, 'open-drawer');

        // The JavaScript API and the first event names (as a server sends them) work on the page too.
        await browser.evaluate(`AcunUI.drawer.open('siparis-filtreleri')`);
        await wait(TRANSITIONS);
        assert.equal((await drawer()).open, true);
        await browser.evaluate(`window.dispatchEvent(new CustomEvent('close-drawer', { detail: { name: 'siparis-filtreleri' } }))`);
        await browser.evaluate(`window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'siparis-ozeti' } }))`);
        await wait(TRANSITIONS);
        assert.deepEqual([(await drawer()).hidden, (await modal()).open], [true, true]);
        await browser.evaluate(`AcunUI.modal.close()`);
        await wait(TRANSITIONS);
        assert.equal((await modal()).hidden, true);

        const seen = await browser.evaluate('window.seen');
        assert.deepEqual(seen.slice(0, 3), [
            ['acun:modal:opened', { name: 'siparis-ozeti' }],
            ['acun:modal:closed', { name: 'siparis-ozeti' }],
            ['modal-closed', { name: 'siparis-ozeti' }],
        ]);
        assert.ok(seen.some(([name, detail]) => name === 'drawer-state-changed' && detail.name === 'siparis-filtreleri' && detail.open === true));
        assert.deepEqual(browser.problems, [], 'No error and no warning in the console.');
    } finally {
        browser.close();
    }
});
