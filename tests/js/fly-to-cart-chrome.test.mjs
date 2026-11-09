import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chrome, openPage } from './cdp.mjs';

// AcunUI.flyToCart in headless Chrome with the runtime's browser build (dist/acun-ui.js): a copy of a picture (an
// image, or a drawing copied whole) flies to a cart and is removed, the cart bounces, several flights at once each
// arrive, and nothing moves under reduced motion or when the source or the cart is not on screen. Only the look: the
// page has no cart and no Livewire.

const dist = new URL('../../dist/', import.meta.url);
const picture = `data:image/svg+xml,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="160"><rect width="200" height="160" fill="#7b6cf6"/></svg>')}`;

function page() {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-fly-'));
    const file = path.join(dir, 'index.html');
    fs.writeFileSync(file, `<!doctype html><html lang="tr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head><body style="margin: 0">
<header style="position: fixed; top: 0; inset-inline: 0; height: 64px; display: flex; justify-content: flex-end; align-items: center; padding-inline: 24px">
    <a id="cart" href="#cart" style="display: inline-flex; width: 40px; height: 40px">Sepet</a>
</header>
<a id="hidden-cart" href="#cart" style="position: fixed; top: -200px; width: 40px; height: 40px">Sepet</a>
<main style="padding: 120px 24px">
    <img id="photo" src="${picture}" alt="Aura kulaklık" width="200" height="160" style="border-radius: 12px">
    <div data-top-layer id="layer" style="position: fixed; inset-inline-start: 300px; top: 120px"><img id="in-dialog" src="${picture}" alt="" width="120" height="96"></div>
    <div id="drawing" style="width: 160px; height: 160px; border-radius: 12px; background: #e4dfff"><svg id="shape" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></svg><button id="inside">İncele</button></div>
</main>
<script src="${new URL('acun-ui.js', dist).href}"></script>
</body></html>`);

    return { url: pathToFileURL(file).href, remove: () => fs.rmSync(dir, { recursive: true, force: true }) };
}

const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';

// Counts every copy that appears, the most in the air at once, and the arrivals at the cart.
const watch = `(() => {
    window.flights = { copies: [], most: 0, arrived: 0 };
    new MutationObserver((records) => {
        for (const record of records) for (const node of record.addedNodes) {
            if (node.nodeType !== 1 || !node.hasAttribute('data-acun-fly-to-cart')) continue;
            window.flights.copies.push({ tag: node.tagName, hidden: node.getAttribute('aria-hidden'), inert: node.hasAttribute('inert'), position: node.style.position, ids: node.querySelectorAll('[id]').length + (node.id ? 1 : 0), tabindex: node.querySelector('button')?.getAttribute('tabindex') ?? null });
            window.flights.most = Math.max(window.flights.most, document.querySelectorAll('[data-acun-fly-to-cart]').length);
        }
    }).observe(document.body, { childList: true });
    document.getElementById('cart').addEventListener('acun:fly-to-cart:arrived', () => window.flights.arrived++);
    return true;
})()`;

test('a copy of the picture flies to the cart, is removed, and the cart bounces', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url);
    try {
        await browser.waitFor('window.AcunUI?.initialized === true');
        await browser.evaluate(watch);

        assert.equal(await browser.evaluate(`AcunUI.flyToCart('#photo', '#cart', { duration: 300 })`), true);
        assert.deepEqual(await browser.evaluate('window.flights'), {
            copies: [{ tag: 'IMG', hidden: 'true', inert: true, position: 'fixed', ids: 0, tabindex: null }],
            most: 1,
            arrived: 1,
        }, 'An image of the picture, out of the accessibility tree, fixed above the page; it arrived.');
        assert.equal(await browser.evaluate(`document.querySelectorAll('[data-acun-fly-to-cart]').length`), 0, 'Removed on arrival.');
        assert.deepEqual(await browser.evaluate(`document.getElementById('cart').getAnimations().map((animation) => animation.playState)`), ['running'], 'The cart bounces.');

        // A drawing (no image): copied whole, without its ids, its controls out of the tab order.
        assert.equal(await browser.evaluate(`AcunUI.flyToCart(document.getElementById('drawing'), document.getElementById('cart'), { duration: 300 })`), true);
        assert.deepEqual((await browser.evaluate('window.flights.copies'))[1], { tag: 'DIV', hidden: 'true', inert: true, position: 'fixed', ids: 0, tabindex: '-1' });
        assert.equal(await browser.evaluate(`document.querySelectorAll('[id="shape"], [id="inside"]').length`), 2, 'The source keeps its own ids.');

        // A picture in a dialog the browser's top layer holds: the copy flies inside that layer, not under it.
        await browser.evaluate(`window.parents = []; new MutationObserver((records) => { for (const record of records) for (const node of record.addedNodes) if (node.nodeType === 1 && node.hasAttribute('data-acun-fly-to-cart')) window.parents.push(node.parentElement.id || node.parentElement.tagName); }).observe(document.body, { childList: true, subtree: true }); true`);
        assert.equal(await browser.evaluate(`AcunUI.flyToCart('#in-dialog', '#cart', { duration: 200 })`), true);
        assert.deepEqual(await browser.evaluate('window.parents'), ['layer']);
    } finally {
        await browser.close();
        remove();
    }
});

test('several adds in a row fly side by side and each arrives; the bounce starts again', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url);
    try {
        await browser.waitFor('window.AcunUI?.initialized === true');
        await browser.evaluate(watch);

        assert.deepEqual(await browser.evaluate(`Promise.all([0, 80, 160].map((delay) => new Promise((resolve) => setTimeout(() => resolve(AcunUI.flyToCart('#photo', '#cart', { duration: 400 })), delay))))`), [true, true, true]);
        const flights = await browser.evaluate('window.flights');
        assert.equal(flights.copies.length, 3);
        assert.equal(flights.most, 3, 'Three in the air at once.');
        assert.equal(flights.arrived, 3);
        assert.equal(await browser.evaluate(`document.querySelectorAll('[data-acun-fly-to-cart]').length`), 0);
        assert.equal(await browser.evaluate(`document.getElementById('cart').getAnimations().length`), 1, 'One bounce: each arrival starts it again.');
    } finally {
        await browser.close();
        remove();
    }
});

test('nothing moves under reduced motion, off screen or without the elements', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url);
    try {
        await browser.waitFor('window.AcunUI?.initialized === true');
        await browser.evaluate(watch);

        assert.equal(await browser.evaluate(`AcunUI.flyToCart('#photo', '#hidden-cart')`), false, 'The cart is not on screen.');
        assert.equal(await browser.evaluate(`AcunUI.flyToCart('#yok', '#cart')`), false, 'No source.');
        assert.equal(await browser.evaluate(`AcunUI.flyToCart('#photo', null)`), false, 'No cart.');

        await browser.emulateMedia([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        assert.equal(await browser.evaluate(`AcunUI.flyToCart('#photo', '#cart')`), false, 'Reduced motion.');
        assert.deepEqual(await browser.evaluate('window.flights'), { copies: [], most: 0, arrived: 0 });
        assert.equal(await browser.evaluate(`document.getElementById('cart').getAnimations().length`), 0, 'No bounce either.');
    } finally {
        await browser.close();
        remove();
    }
});
