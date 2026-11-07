import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chrome, openPage } from './cdp.mjs';
import { renderBlade } from './css-samples.mjs';
import { stylesheet } from './chrome.mjs';

// acun:carousel per-view-base="container" (2026-11-07) in headless Chrome with the browser build: the per-view
// breakpoints read the strip's own width (a container query), not the screen's, so on one screen a narrow strip holds
// fewer slides than a wide one; Swiper follows when the strip's width changes (its ResizeObserver), with its stops.

const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';
const dist = new URL('../../dist/', import.meta.url);
const items = Array.from({ length: 8 }, (_, i) => `<acun:carousel.item label="Ürün ${i + 1}"><a href="#u${i}" style="display: block; height: 6rem">Ürün ${i + 1}</a></acun:carousel.item>`).join('');
const perView = `:per-view="['base' => 2, 'md' => 3, 'lg' => 4, 'xl' => 5, '2xl' => 6]" :loop="false" :pagination="false"`;

const markup = renderBlade({
    narrow: `<div id="dar-kutu" style="width: 800px"><acun:carousel id="dar" per-view-base="container" ${perView} label="Dar">${items}</acun:carousel></div>`,
    wide: `<div id="genis-kutu" style="width: 1100px; margin-top: 2rem"><acun:carousel id="genis" per-view-base="container" ${perView} label="Geniş">${items}</acun:carousel></div>`,
    screen: `<div style="width: 600px; margin-top: 2rem"><acun:carousel id="ekran" ${perView} label="Ekran">${items}</acun:carousel></div>`,
});
const css = await stylesheet(Object.values(markup).join('\n'));

function page() {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-carousel-container-'));
    const file = path.join(dir, 'index.html');
    fs.writeFileSync(file, `<!doctype html><html lang="tr" data-theme="light"><head><meta charset="utf-8">
<style>${css}</style>
<script src="${new URL('acun-ui.js', dist).href}" defer data-acun-ui></script>
</head><body style="padding: 1rem; margin: 0">${markup.narrow}${markup.wide}${markup.screen}</body></html>`);

    return { url: pathToFileURL(file).href, remove: () => fs.rmSync(dir, { recursive: true, force: true }) };
}

// The slides in view: the viewport's width over a slide's step (its width and the gap after it), and Swiper's stops.
const inView = (id) => `(() => {
    const root = document.getElementById('${id}');
    const view = root.querySelector('[data-slot=carousel-viewport]').getBoundingClientRect().width;
    const [first, second] = root.querySelectorAll('[data-slot=carousel-track] > *');
    const step = second.getBoundingClientRect().left - first.getBoundingClientRect().left;
    return { inView: Math.round(view / step), stops: window.AcunUI.plugins.get(root, 'carousel').swiper.snapGrid.length };
})()`;

test('per-view-base="container": the strip\'s own width picks the slides in view, and Swiper follows a change', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url, { width: 1600, height: 900 });
    try {
        await browser.waitFor(`['dar', 'genis', 'ekran'].every((id) => document.getElementById(id).hasAttribute('data-ready'))`, { message: 'Swiper to start' });

        // One 1600px screen: an 800px strip is md (48rem ≤ 800px < 64rem): 3; 1100px is lg (64rem ≤ 1100px < 80rem): 4.
        // By the screen, a 600px strip takes the screen's 2xl (96rem ≤ 1600px): 6.
        assert.deepEqual(await browser.evaluate(inView('dar')), { inView: 3, stops: 6 });
        assert.deepEqual(await browser.evaluate(inView('genis')), { inView: 4, stops: 5 });
        assert.equal((await browser.evaluate(inView('ekran'))).inView, 6, 'The screen\'s breakpoints, 2xl included.');

        // The strip grows (a wider page container): 1300px is xl, 1540px 2xl; it shrinks to 400px: base.
        for (const [width, count] of [[1300, 5], [1540, 6], [400, 2]]) {
            await browser.evaluate(`document.getElementById('dar-kutu').style.width = '${width}px'`);
            await browser.waitFor(`${inView('dar')}.inView === ${count} && ${inView('dar')}.stops === ${8 - count + 1}`, { message: `${count} in view at ${width}px` });
        }
    } finally {
        await browser.close();
        remove();
    }
});
