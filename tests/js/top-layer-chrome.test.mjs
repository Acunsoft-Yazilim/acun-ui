import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chrome, openPage } from './cdp.mjs';
import { renderBlade } from './css-samples.mjs';
import { stylesheet } from './chrome.mjs';

// A dialog layer inside a box that makes a stacking context or a containing block (a sticky product gallery, a
// transformed card) under a fixed page header: the modal plugin and the carousel's full-screen view put it in the
// browser's top layer (js/behavior/top-layer.js), so it opens above the header, its close button is the element under
// the pointer, Escape closes it and the focus goes back. Headless Chrome with the browser build (dist/acun-ui.js).

const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';
const dist = new URL('../../dist/', import.meta.url);

const slides = Array.from({ length: 3 }, (_, i) => `<acun:carousel.item><div class="swiper-zoom-target" role="img" aria-label="Görsel ${i + 1}" style="height: 160px; background: #888"></div></acun:carousel.item>`).join('');
const markup = renderBlade({
    sticky: `<acun:carousel id="yapiskan" fullscreen zoom label="Ürün" style="width: 320px">${slides}</acun:carousel>
        <button type="button" id="ac-yapiskan" data-acun-open="modal-yapiskan">Aç</button>
        <acun:modal name="modal-yapiskan" title="Sipariş"><p style="height: 400px">Uzun içerik</p></acun:modal>`,
    transformed: `<acun:carousel id="donusen" fullscreen zoom label="Ürün" style="width: 320px">${slides}</acun:carousel>
        <button type="button" id="ac-donusen" data-acun-open="modal-donusen">Aç</button>
        <acun:modal name="modal-donusen" title="Sipariş"><p style="height: 400px">Uzun içerik</p></acun:modal>`,
});

const plainModal = renderBlade({ modal: '<acun:modal name="duz" title="Sipariş">İçerik</acun:modal>' }).modal;
// The pages' CSS: the kit's Tailwind sources compiled for their markup (as an application's build does).
const css = await stylesheet([...Object.values(markup), plainModal].join('\n'));

function page(box) {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-top-layer-'));
    const file = path.join(dir, 'index.html');
    // A fixed header over the page (z-30, as the store's), and the dialogs inside a box that holds fixed layers.
    fs.writeFileSync(file, `<!doctype html><html lang="tr" data-theme="light"><head><meta charset="utf-8">
<style>${css}</style>
<script src="${new URL('acun-ui.js', dist).href}" defer data-acun-ui></script>
</head><body style="margin: 0">
<header id="ust" style="position: fixed; inset: 0 0 auto 0; height: 96px; z-index: 30; background: #222">Mağaza</header>
<main style="padding-top: 110px">
    <div id="kutu" style="${box}">${markup[box.includes('sticky') ? 'sticky' : 'transformed']}</div>
    <p style="height: 1200px">Sayfa</p>
</main>
</body></html>`);

    return { url: pathToFileURL(file).href, remove: () => fs.rmSync(dir, { recursive: true, force: true }) };
}

/** What is under the pointer at the middle of the element selector finds: true when it is that element (or in it). */
const onTop = (selector) => `(() => {
    const element = document.querySelector(${JSON.stringify(selector)});
    const rect = element.getBoundingClientRect();
    const hit = document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2);
    return { onTop: element === hit || element.contains(hit), hit: hit?.id || hit?.tagName, top: Math.round(rect.y) };
})()`;

for (const [label, box, carouselId, modalName] of [
    ['a sticky box', 'position: sticky; top: 0', 'yapiskan', 'modal-yapiskan'],
    ['a transformed box', 'transform: translateZ(0)', 'donusen', 'modal-donusen'],
]) {
    test(`inside ${label}, the full-screen view and a modal open above the page's fixed header`, { skip }, async () => {
        const { url, remove } = page(box);
        const browser = await openPage(url, { width: 1000, height: 320 });
        try {
            await browser.waitFor(`document.getElementById('${carouselId}').hasAttribute('data-ready')`, { message: 'the carousel' });

            // The carousel's full-screen view: its close button at the top is not under the header.
            const lightbox = `document.querySelector('#${carouselId} [data-slot="carousel-lightbox"]')`;
            await browser.click(`#${carouselId} [data-slot="carousel-fullscreen"]`);
            await browser.waitFor(`${lightbox}.hasAttribute('data-open')`, { message: 'the full-screen view' });
            await browser.settle();
            assert.equal(await browser.evaluate(`${lightbox}.matches(':popover-open')`), true, 'In the top layer.');
            const close = await browser.evaluate(onTop(`#${carouselId} [data-slot="carousel-lightbox"] [data-acun-close]`));
            assert.equal(close.onTop, true, `The close button is on top (found ${close.hit} at y ${close.top}).`);
            await browser.press('Escape');
            await browser.waitFor(`${lightbox}.hidden`, { message: 'Escape closes it' });
            assert.equal(await browser.evaluate(`document.activeElement.dataset.slot`), 'carousel-fullscreen', 'The focus is back on the button.');
            assert.equal(await browser.evaluate(`${lightbox}.hasAttribute('popover')`), false, 'Closed, it leaves the top layer.');

            // A modal written in the same box: above the header too, its X clickable.
            const modal = `document.querySelector('[data-acun-modal="${modalName}"]')`;
            await browser.click(`#ac-${carouselId}`);
            await browser.waitFor(`${modal}.hasAttribute('data-open')`, { message: 'the modal' });
            await browser.settle();
            const x = await browser.evaluate(onTop(`[data-acun-modal="${modalName}"] [data-acun-close][aria-label]`));
            assert.equal(x.onTop, true, `The modal's X is on top (found ${x.hit} at y ${x.top}).`);
            await browser.click(`[data-acun-modal="${modalName}"] [data-acun-close][aria-label]`);
            await browser.waitFor(`${modal}.hidden`, { message: 'its X closes it' });
            assert.equal(await browser.evaluate('document.activeElement.id'), `ac-${carouselId}`, 'The focus is back on its button.');
            assert.deepEqual(browser.problems, []);
        } finally {
            await browser.close();
            remove();
        }
    });
}

test('a modal no box holds stays out of the top layer (toasts and date pickers appended to <body> stay above it)', { skip }, async () => {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-top-layer-'));
    const file = path.join(dir, 'index.html');
    fs.writeFileSync(file, `<!doctype html><html lang="tr" data-theme="light"><head><meta charset="utf-8">
<style>${css}</style>
<script src="${new URL('acun-ui.js', dist).href}" defer data-acun-ui></script>
</head><body><button type="button" id="ac" data-acun-open="duz">Aç</button>${plainModal}</body></html>`);
    const browser = await openPage(pathToFileURL(file).href, { width: 1000, height: 600 });
    try {
        await browser.waitFor('!!window.AcunUI?.initialized');
        await browser.click('#ac');
        await browser.waitFor(`document.querySelector('[data-acun-modal="duz"]').hasAttribute('data-open')`);
        assert.equal(await browser.evaluate(`document.querySelector('[data-acun-modal="duz"]').hasAttribute('popover')`), false);
    } finally {
        await browser.close();
        fs.rmSync(dir, { recursive: true, force: true });
    }
});
