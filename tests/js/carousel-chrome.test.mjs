import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chrome, openPage, wait } from './cdp.mjs';
import { renderBlade } from './css-samples.mjs';
import { stylesheet } from './chrome.mjs';

// acun:carousel in headless Chrome with real input, on a plain HTML page with the compiled stylesheet and the browser
// build alone (dist/acun-ui.js, no Alpine, no Livewire): the runtime loads dist/acun-ui-carousel.js (Swiper) from
// beside itself only because a carousel is on the page; a mouse drag, the arrows and the Left / Right keys slide it;
// the auto-advance runs, stops under the pointer and the focus, pauses with its button and never runs under reduced
// motion; a strip stops at its ends.

const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';
const dist = new URL('../../dist/', import.meta.url);

const markup = renderBlade({
    hero: `<acun:carousel id="vitrin" :interval="1200" label="Kampanyalar" style="width: 480px">
        <acun:carousel.item><a href="#bir" id="bir" style="display: block; height: 12rem">Bir</a></acun:carousel.item>
        <acun:carousel.item><a href="#iki" style="display: block; height: 12rem">İki</a></acun:carousel.item>
        <acun:carousel.item><a href="#uc" style="display: block; height: 12rem">Üç</a></acun:carousel.item>
    </acun:carousel>`,
    strip: `<acun:carousel id="serit" :per-view="2" :loop="false" :pagination="false" label="Ürünler" style="width: 480px; margin-top: 2rem">
        ${Array.from({ length: 5 }, (_, i) => `<acun:carousel.item label="Ürün ${i + 1}"><a href="#u${i}" style="display: block; height: 6rem">Ürün ${i + 1}</a></acun:carousel.item>`).join('')}
    </acun:carousel>`,
    gallery: `<acun:carousel id="galeri" zoom fullscreen :pagination="false" :arrows="false" label="Ürün" style="width: 360px; margin-top: 2rem">
        ${Array.from({ length: 3 }, (_, i) => `<acun:carousel.item><div class="swiper-zoom-target" role="img" aria-label="Görsel ${i + 1}" style="height: 240px; background: linear-gradient(135deg, #888, #222)"></div></acun:carousel.item>`).join('')}
        <acun:slot:thumbnails>${Array.from({ length: 3 }, (_, i) => `<acun:carousel.thumb label="Görsel ${i + 1}"><div style="height: 60px; background: #999"></div></acun:carousel.thumb>`).join('')}</acun:slot:thumbnails>
    </acun:carousel>`,
    // A product gallery with photos: the magnifier, small thumbnails of one width and the full-screen view.
    magnify: `<acun:carousel id="buyutec" zoom fullscreen hover-zoom thumb-width="3rem" :pagination="false" :arrows="false" label="Ceket" style="width: 360px; margin-top: 2rem">
        ${Array.from({ length: 3 }, (_, i) => `<acun:carousel.item><img class="swiper-zoom-target" style="display: block; width: 100%; aspect-ratio: 3 / 4; object-fit: cover" alt="Ceket ${i + 1}" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='800'%3E%3Crect width='600' height='800' fill='%23${['c44', '4c4', '44c'][i]}'/%3E%3C/svg%3E"></acun:carousel.item>`).join('')}
        <acun:slot:thumbnails>${Array.from({ length: 3 }, (_, i) => `<acun:carousel.thumb label="Ceket ${i + 1}"><div style="aspect-ratio: 3 / 4; background: #999"></div></acun:carousel.thumb>`).join('')}</acun:slot:thumbnails>
    </acun:carousel>`,
    // One photo, no thumbnails (a product with a single picture): the frame is the viewport's, the pane is not cut.
    magnifyOne: `<acun:carousel id="tek" hover-zoom fullscreen :pagination="false" :thumbs="null" label="Kupa" style="width: 360px; margin-top: 2rem">
        <acun:carousel.item><img class="swiper-zoom-target" style="display: block; width: 100%; aspect-ratio: 1; object-fit: cover" data-zoom-src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1200' height='1200'%3E%3Crect width='1200' height='1200' fill='%23c84'/%3E%3C/svg%3E" alt="Kupa" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='600'%3E%3Crect width='600' height='600' fill='%23c84'/%3E%3C/svg%3E"></acun:carousel.item>
    </acun:carousel>`,
    // A cropped photo in a frame with a background picture (a store's product photo on its tile): full screen shows
    // the photo whole, so the tile must not show round it.
    framed: `<acun:carousel id="cerceve" fullscreen :pagination="false" :thumbs="null" label="Tezgah" style="width: 360px; margin-top: 2rem">
        <acun:carousel.item><div class="swiper-zoom-target" style="position: relative; aspect-ratio: 1; background-color: rgb(255, 255, 255); background-image: linear-gradient(#a0f, #a0f)"><img class="absolute inset-0 size-full object-cover" data-zoom-src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1600' height='800'%3E%3Crect width='1600' height='800' fill='%23444'/%3E%3C/svg%3E" alt="Tezgah" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='400'%3E%3Crect width='800' height='400' fill='%23888'/%3E%3C/svg%3E"></div></acun:carousel.item>
    </acun:carousel>`,
    // The thumbnails beside the slides on a wide screen, under them below.
    side: `<acun:carousel id="yan" thumbs="vertical-wide" thumb-width="4rem" :pagination="false" :arrows="false" label="Ürün" style="width: 480px; margin-top: 2rem">
        ${Array.from({ length: 6 }, (_, i) => `<acun:carousel.item><div role="img" aria-label="Görsel ${i + 1}" style="height: 320px; background: #888"></div></acun:carousel.item>`).join('')}
        <acun:slot:thumbnails>${Array.from({ length: 6 }, (_, i) => `<acun:carousel.thumb label="Görsel ${i + 1}"><div style="aspect-ratio: 1; background: #999"></div></acun:carousel.thumb>`).join('')}</acun:slot:thumbnails>
    </acun:carousel>`,
    // A slide with its own time (a video slide longer): the first stays 2 s, the others the carousel's 0.4 s.
    timed: `<acun:carousel id="sureli" :interval="400" label="Vitrin" style="width: 480px">
        <acun:carousel.item :interval="2000"><div style="height: 8rem">Video</div></acun:carousel.item>
        <acun:carousel.item><div style="height: 8rem">İki</div></acun:carousel.item>
        <acun:carousel.item><div style="height: 8rem">Üç</div></acun:carousel.item>
    </acun:carousel>`,
    controls: `<div style="width: 480px; margin-top: 2rem; display: flex; align-items: end; justify-content: space-between"><h2 style="margin: 0">Çok satanlar</h2><acun:carousel.controls for="uzak" counter /></div>
    <acun:carousel id="uzak" :arrows="false" :per-view="2" :loop="false" :pagination="false" label="Çok satanlar" style="width: 480px">
        ${Array.from({ length: 4 }, (_, i) => `<acun:carousel.item label="Ürün ${i + 1}"><a href="#k${i}" style="display: block; height: 6rem">Ürün ${i + 1}</a></acun:carousel.item>`).join('')}
    </acun:carousel>
    <acun:carousel id="ust" arrows="top-end" :per-view="2" :loop="false" :pagination="false" label="Yeni gelenler" style="width: 480px; margin-top: 2rem">
        ${Array.from({ length: 4 }, (_, i) => `<acun:carousel.item label="Yeni ${i + 1}"><a href="#y${i}" style="display: block; height: 6rem">Yeni ${i + 1}</a></acun:carousel.item>`).join('')}
    </acun:carousel>`,
});

// The page's CSS: the kit's Tailwind sources compiled for this markup (as an application's build does).
const css = await stylesheet(Object.values(markup).join('\n'));

function page(body = markup.hero + markup.strip + markup.gallery) {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-carousel-'));
    const file = path.join(dir, 'index.html');
    fs.writeFileSync(file, `<!doctype html><html lang="tr" data-theme="light"><head><meta charset="utf-8">
<style>${css}</style>
<script src="${new URL('acun-ui.js', dist).href}" defer data-acun-ui></script>
</head><body style="padding: 2rem">${body}</body></html>`);

    return { url: pathToFileURL(file).href, remove: () => fs.rmSync(dir, { recursive: true, force: true }) };
}

const state = (id) => `(() => {
    const root = document.getElementById('${id}');
    const carousel = window.AcunUI.plugins.get(root, 'carousel');
    return { ready: root.hasAttribute('data-ready'), index: carousel.index, live: root.querySelector('[data-slot=carousel-track]').getAttribute('aria-live') };
})()`;

const center = (selector) => `(() => { const rect = document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect(); return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 }; })()`;

test('the browser build loads the carousel file beside itself; drag, arrows, keys and the auto-advance move it', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url, { width: 900, height: 700 });
    try {
        await browser.waitFor(`document.getElementById('vitrin').hasAttribute('data-ready') && document.getElementById('serit').hasAttribute('data-ready')`, { message: 'Swiper to start' });
        assert.deepEqual(await browser.evaluate(`[...document.scripts].map((script) => script.src.split('/').pop()).filter(Boolean)`), ['acun-ui.js', 'acun-ui-carousel.js'], 'The carousel file is loaded on demand, beside acun-ui.js.');
        assert.equal(await browser.evaluate('typeof window.Alpine'), 'undefined');

        // The auto-advance turns on its own (the pointer is not over it).
        await browser.evaluate(`window.AcunUI.plugins.get(document.getElementById('vitrin'), 'carousel').goTo(0)`);
        const before = (await browser.evaluate(state('vitrin'))).index;
        await browser.waitFor(`${state('vitrin')}.index !== ${before}`, { timeout: 4000, message: 'the auto-advance' });
        assert.equal((await browser.evaluate(state('vitrin'))).live, 'off', 'Not announced on every turn.');

        // The pause button (a real click: the pointer is over the carousel then too, and stays paused when it leaves).
        await browser.click('#vitrin [data-slot="carousel-pause"]');
        await browser.clickAt(880, 680);
        const paused = (await browser.evaluate(state('vitrin'))).index;
        await wait(1600);
        assert.equal((await browser.evaluate(state('vitrin'))).index, paused, 'Paused stays paused.');
        assert.equal(await browser.evaluate(`document.querySelector('#vitrin [data-slot="carousel-pause"]').getAttribute('aria-label')`), 'Oynat');

        // A mouse drag to the left shows the next slide.
        await browser.evaluate(`window.AcunUI.plugins.get(document.getElementById('vitrin'), 'carousel').goTo(0)`);
        await browser.settle();
        const { x, y } = await browser.evaluate(center('#vitrin [data-slot="carousel-viewport"]'));
        await browser.drag(x + 120, y, x - 120, y);
        await browser.waitFor(`${state('vitrin')}.index === 1`, { message: 'the drag to slide' });
        assert.equal(await browser.evaluate('location.hash'), '', 'A drag does not follow the link it started on.');

        // The arrows and the keys.
        await browser.click('#vitrin [data-slot="carousel-next"]');
        await browser.waitFor(`${state('vitrin')}.index === 2`, { message: 'the Next button' });
        await browser.press('ArrowRight');
        await browser.waitFor(`${state('vitrin')}.index === 0`, { message: 'Right from the last goes back to the first (loop)' });
        await browser.press('ArrowLeft');
        await browser.waitFor(`${state('vitrin')}.index === 2`, { message: 'Left' });
        // Only the shown slide's link is in the Tab order.
        assert.deepEqual(await browser.evaluate(`[...document.querySelectorAll('#vitrin [data-slot="carousel-item"]')].map((slide) => slide.inert)`), [true, true, false]);

        // A strip stops at its ends: Previous is disabled at the start, Next at the end.
        assert.equal(await browser.evaluate(`document.querySelector('#serit [data-slot="carousel-prev"]').getAttribute('aria-disabled')`), 'true');
        for (let i = 0; i < 4; i++) {
            await browser.click('#serit [data-slot="carousel-next"]');
            await browser.settle();
        }
        assert.equal(await browser.evaluate(`document.querySelector('#serit [data-slot="carousel-next"]').getAttribute('aria-disabled')`), 'true');
        assert.equal((await browser.evaluate(state('serit'))).index, 3, 'Two in view: four positions.');
        // The gallery: a drag slides it with zoom on, a thumbnail shows its slide, the zoom button and Escape.
        await browser.waitFor(`document.getElementById('galeri').hasAttribute('data-ready')`);
        await browser.evaluate(`document.getElementById('galeri').scrollIntoView({ block: 'center' })`);
        const picture = await browser.evaluate(center('#galeri [data-slot="carousel-viewport"]'));
        await browser.drag(picture.x + 100, picture.y, picture.x - 100, picture.y);
        await browser.waitFor(`${state('galeri')}.index === 1`, { message: 'the drag on a zoomable gallery' });
        await browser.click('#galeri [data-slot="carousel-thumb"]:nth-child(3)');
        await browser.waitFor(`${state('galeri')}.index === 2`, { message: 'the third thumbnail' });
        assert.equal(await browser.evaluate(`document.querySelector('#galeri [data-slot="carousel-thumb"]:nth-child(3)').getAttribute('aria-current')`), 'true');
        await browser.click('#galeri [data-slot="carousel-zoom"]');
        await browser.waitFor(`document.getElementById('galeri').hasAttribute('data-zoomed')`, { message: 'the zoom button' });
        await browser.press('Escape');
        await browser.waitFor(`!document.getElementById('galeri').hasAttribute('data-zoomed')`, { message: 'Escape zooms out' });

        // Full screen: the modal opens at the shown slide with the focus inside, the arrows move it, Escape closes it,
        // the focus goes back to the button and the page's carousel shows the slide looked at last.
        await browser.click('#galeri [data-slot="carousel-fullscreen"]');
        const lightbox = `document.querySelector('#galeri [data-slot="carousel-lightbox"]')`;
        const inner = `${lightbox}.querySelector('[data-carousel-lightbox]')`;
        await browser.waitFor(`${lightbox}.hasAttribute('data-open') && ${inner}.hasAttribute('data-ready')`, { message: 'the full-screen view' });
        assert.equal(await browser.evaluate(`window.AcunUI.plugins.get(${inner}, 'carousel').index`), 2, 'At the shown slide.');
        assert.equal(await browser.evaluate(`${lightbox}.contains(document.activeElement)`), true, 'The focus is in the dialog.');
        assert.equal(await browser.evaluate(`document.documentElement.classList.contains('scroll-locked')`), true);
        await browser.press('ArrowLeft');
        await browser.waitFor(`window.AcunUI.plugins.get(${inner}, 'carousel').index === 1`, { message: 'Left in the full-screen view' });
        await browser.press('Escape');
        await browser.waitFor(`${lightbox}.hidden`, { message: 'Escape closes the full-screen view' });
        assert.equal((await browser.evaluate(state('galeri'))).index, 1, 'The page shows the slide looked at last.');
        assert.equal(await browser.evaluate(`document.activeElement.dataset.slot`), 'carousel-fullscreen', 'The focus is back on the button.');
        assert.deepEqual(browser.problems, []);
    } finally {
        await browser.close();
        remove();
    }
});

test('a gallery magnifies the photo under a mouse, its thumbnails keep one width, the full-screen view takes the focus itself', { skip }, async () => {
    const { url, remove } = page(markup.magnify);
    const browser = await openPage(url, { width: 1280, height: 900 });
    try {
        await browser.waitFor(`document.getElementById('buyutec').hasAttribute('data-ready') && document.querySelector('#buyutec img').naturalWidth > 0`, { message: 'Swiper to start' });
        const look = (x, y) => browser.evaluate(`(() => {
            const root = document.getElementById('buyutec');
            const view = root.querySelector('[data-slot="carousel-viewport"]');
            const box = view.getBoundingClientRect();
            view.dispatchEvent(new PointerEvent('pointermove', { clientX: box.left + box.width * ${x}, clientY: box.top + box.height * ${y}, pointerType: 'mouse', bubbles: true }));
            const lens = root.querySelector('[data-slot="carousel-lens"]');
            const pane = root.querySelector('[data-slot="carousel-magnifier"]');
            const at = (element) => { const r = element.getBoundingClientRect(); return [r.left - box.left, r.top - box.top, r.width, r.height].map(Math.round); };
            return { lens: lens.hidden ? null : at(lens), pane: pane.hidden ? null : at(pane), size: pane.style.backgroundSize, root: Math.round(root.getBoundingClientRect().width), view: [box.width, box.height].map(Math.round) };
        })()`);

        const middle = await look(0.5, 0.5);
        assert.deepEqual(middle.view, [360, 480]);
        assert.deepEqual(middle.lens, [108, 144, 144, 192], 'The lens (2.5 times smaller than the pane) is centred on the pointer…');
        assert.deepEqual(middle.pane, [middle.root + 24, 0, 360, 480], '… the pane beside the gallery is as big as its pictures…');
        assert.equal(middle.size, '900px 1200px', '… and shows the photo 2.5 times larger.');
        assert.deepEqual((await look(0.02, 0.98)).lens, [0, 288, 144, 192], 'At a corner the lens stays on the photo.');
        await browser.evaluate(`document.querySelector('#buyutec [data-slot="carousel-viewport"]').dispatchEvent(new PointerEvent('pointerleave', { pointerType: 'mouse' }))`);
        assert.deepEqual(await browser.evaluate(`[...document.querySelectorAll('#buyutec [data-slot="carousel-lens"], #buyutec [data-slot="carousel-magnifier"]')].map((element) => element.hidden)`), [true, true], 'Leaving hides them.');

        const thumbs = (scope) => browser.evaluate(`[...document.querySelectorAll('${scope} [data-slot="carousel-thumb"]')].filter((thumb) => thumb.getClientRects().length).map((thumb) => [Math.round(thumb.getBoundingClientRect().left), Math.round(thumb.getBoundingClientRect().width)])`);
        const strip = await thumbs('#buyutec > [data-slot="carousel-thumbs"]');
        assert.deepEqual(strip.map(([, width]) => width), [48, 48, 48], 'thumb-width: one width (3rem)…');
        assert.equal(strip[1][0] - strip[0][0], 60, '… 12px apart.');

        await browser.click('#buyutec [data-slot="carousel-fullscreen"]');
        const inner = `document.querySelector('#buyutec [data-carousel-lightbox]')`;
        await browser.waitFor(`${inner}.hasAttribute('data-ready')`, { message: 'the full-screen view' });
        await wait(300);
        const big = await thumbs('#buyutec [data-carousel-lightbox]');
        assert.deepEqual(big.map(([, width]) => width), [56, 56, 56], 'The full-screen view\'s thumbnails are small too…');
        assert.equal(big[1][0] - big[0][0], 68, '… with their gap (the page\'s ready carousel had turned the CSS gap off).');
        assert.equal(await browser.evaluate(`document.activeElement.getAttribute('role')`), 'dialog', 'The dialog takes the focus itself: no ring on the close button.');
        await browser.press('ArrowRight');
        await browser.waitFor(`window.AcunUI.plugins.get(${inner}, 'carousel').index === 1`, { message: 'Right from the dialog' });
        assert.deepEqual(browser.problems, []);
    } finally {
        await browser.close();
        remove();
    }
});

test('thumbs="vertical-wide": a strip on the left on a wide screen (it slides up and down), under the slides on a narrow one', { skip }, async () => {
    const { url, remove } = page(markup.side);
    const strip = `(() => {
        const root = document.getElementById('yan');
        const view = root.querySelector('[data-slot="carousel-viewport"]').getBoundingClientRect();
        const thumbs = [...root.querySelectorAll(':scope > [data-slot="carousel-thumbs"] [data-slot="carousel-thumb"]')].map((thumb) => thumb.getBoundingClientRect());
        return { view: [view.left, view.top, view.width, view.height].map(Math.round), thumbs: thumbs.slice(0, 3).map((r) => [r.left, r.top, r.width].map(Math.round)), direction: window.AcunUI.plugins.get(root, 'carousel').thumbs?.params.direction };
    })()`;
    for (const [width, side] of [[1280, true], [900, false]]) {
        const browser = await openPage(url, { width, height: 900 });
        try {
            await browser.waitFor(`document.getElementById('yan').hasAttribute('data-ready')`, { message: 'Swiper to start' });
            const { view, thumbs, direction } = await browser.evaluate(strip);
            if (side) {
                assert.equal(direction, 'vertical', 'Wide: a vertical strip…');
                assert.ok(thumbs.every(([left, , w]) => left < view[0] && w === 64), '… on the left of the slides, 64px wide…');
                assert.ok(thumbs[1][1] - thumbs[0][1] === 76 && thumbs[0][0] === thumbs[1][0], '… one under another, 12px apart.');
                assert.equal(view[3], 320);
            } else {
                assert.equal(direction, 'horizontal', 'Narrow: a row…');
                assert.ok(thumbs.every(([, top]) => top > view[1] + view[3]), '… under the slides…');
                assert.equal(thumbs[1][0] - thumbs[0][0], 76, '… side by side, 12px apart.');
                assert.equal(view[2], 480, 'The slides keep the whole width.');
            }
            assert.deepEqual(browser.problems, []);
        } finally {
            await browser.close();
        }
    }
    remove();
});

test('under reduced motion the carousel does not advance by itself', { skip }, async () => {
    const { url, remove } = page();
    const browser = await openPage(url, { width: 900, height: 700 });
    try {
        // The page again, now with the operating system asking for reduced motion.
        await browser.emulateMedia([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await browser.evaluate(`document.documentElement.dataset.first = '1'; setTimeout(() => location.reload()); true`);
        await browser.waitFor(`document.readyState === 'complete' && !document.documentElement.dataset.first && !!document.getElementById('vitrin')?.hasAttribute('data-ready')`, { message: 'Swiper to start again' });
        const first = (await browser.evaluate(state('vitrin'))).index;
        await wait(1800);
        assert.deepEqual(await browser.evaluate(state('vitrin')), { ready: true, index: first, live: 'polite' });
        assert.equal(await browser.evaluate(`window.AcunUI.plugins.get(document.getElementById('vitrin'), 'carousel').swiper.params.speed`), 0, 'No sliding animation either.');
    } finally {
        await browser.close();
        remove();
    }
});

test('arrows in a section heading (acun:carousel.controls) and above the strip (top-end) show, move it and stop at its ends', { skip }, async () => {
    const { url, remove } = page(markup.controls);
    const browser = await openPage(url, { width: 900, height: 900 });
    try {
        await browser.waitFor(`document.getElementById('uzak').hasAttribute('data-ready') && document.getElementById('ust').hasAttribute('data-ready')`, { message: 'Swiper to start' });
        const box = (selector) => browser.evaluate(`(() => { const r = document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect(); return { top: r.top, bottom: r.bottom, right: r.right, width: r.width }; })()`);
        const controls = '[data-acun-carousel-controls="uzak"]';

        // Shown in the heading, at its end, above the slides; the counter between the arrows.
        assert.equal(await browser.evaluate(`getComputedStyle(document.querySelector('${controls}')).display`), 'flex');
        assert.ok((await box(controls)).bottom <= (await box('#uzak-viewport')).top, 'In the heading, above the slides.');
        assert.equal(await browser.evaluate(`document.querySelector('${controls} [data-slot="carousel-counter"]').textContent`), '1 / 3');
        assert.equal(await browser.evaluate(`document.querySelector('${controls} [data-slot="carousel-prev"]').getAttribute('aria-disabled')`), 'true');

        for (let i = 0; i < 2; i++) {
            await browser.click(`${controls} [data-slot="carousel-next"]`);
            await browser.settle();
        }
        await browser.waitFor(`${state('uzak')}.index === 2`, { message: 'the heading\'s Next' });
        assert.equal(await browser.evaluate(`document.querySelector('${controls} [data-slot="carousel-next"]').getAttribute('aria-disabled')`), 'true', 'At the end.');

        // top-end: side by side above the slides, at their end.
        const [prev, next, viewport] = [await box('#ust [data-slot="carousel-prev"]'), await box('#ust [data-slot="carousel-next"]'), await box('#ust-viewport')];
        assert.ok(next.bottom <= viewport.top, 'Above the slides.');
        assert.ok(Math.abs(next.right - viewport.right) < 1 && prev.right < next.right, 'Side by side, at the end.');
        await browser.click('#ust [data-slot="carousel-next"]');
        await browser.waitFor(`${state('ust')}.index === 1`, { message: 'the top-end Next' });

        // The keyboard focus on an arrow is visible.
        await browser.evaluate(`document.querySelector('#ust [data-slot="carousel-prev"]').blur()`);
        await browser.evaluate(`document.querySelector('${controls} [data-slot="carousel-prev"]').focus({ focusVisible: true })`);
        assert.notEqual(await browser.evaluate(`getComputedStyle(document.activeElement).outlineStyle`), 'none', 'A visible focus ring.');
        assert.deepEqual(browser.problems, []);
    } finally {
        await browser.close();
        remove();
    }
});

test('a slide may stay longer than the others (its own interval)', { skip }, async () => {
    const { url, remove } = page(markup.timed);
    const browser = await openPage(url);
    try {
        await browser.waitFor(`${state('sureli')}.ready`);
        await wait(1000);
        assert.equal((await browser.evaluate(state('sureli'))).index, 0, 'The first slide keeps its own 2 s…');
        await browser.waitFor(`${state('sureli')}.index === 1`, { timeout: 3000, message: 'the first slide\'s time to run out' });
        const started = Date.now();
        await browser.waitFor(`${state('sureli')}.index === 2`, { timeout: 3000, message: 'the second slide\'s (the carousel\'s) time' });
        assert.ok(Date.now() - started < 1500, '… the next one the carousel\'s 0.4 s.');
    } finally {
        await browser.close();
        remove();
    }
});

test('the magnifier works on a single photo without thumbnails (its pane is not cut)', { skip }, async () => {
    const { url, remove } = page(markup.magnifyOne);
    const browser = await openPage(url, { width: 1280, height: 900 });
    try {
        await browser.waitFor(`document.getElementById('tek').hasAttribute('data-ready') && document.querySelector('#tek img').naturalWidth > 0`, { message: 'Swiper to start' });
        assert.equal(await browser.evaluate(`getComputedStyle(document.getElementById('tek')).overflow`), 'visible', 'The root does not cut what opens beside it…');
        assert.equal(await browser.evaluate(`getComputedStyle(document.querySelector('#tek [data-slot=carousel-viewport]')).overflow`), 'hidden', '… the viewport keeps the frame.');
        await browser.evaluate(`(() => { const view = document.querySelector('#tek [data-slot="carousel-viewport"]'); const box = view.getBoundingClientRect(); view.dispatchEvent(new PointerEvent('pointermove', { clientX: box.left + box.width / 2, clientY: box.top + box.height / 2, pointerType: 'mouse', bubbles: true })); return true; })()`);
        await browser.waitFor(`!document.querySelector('#tek [data-slot=carousel-magnifier]').hidden`, { timeout: 3000, message: 'the pane to open' });
        assert.deepEqual(await browser.evaluate(`(() => {
            const pane = document.querySelector('#tek [data-slot=carousel-magnifier]');
            const cutBy = [];
            for (let node = pane.parentElement; node && node !== document.body; node = node.parentElement) if (getComputedStyle(node).overflow !== 'visible') cutBy.push(node.id || node.dataset.slot || node.tagName);
            return { visible: pane.checkVisibility(), width: Math.round(pane.getBoundingClientRect().width), cutBy };
        })()`), { visible: true, width: 360, cutBy: [] }, 'The pane shows beside the photo, nothing round it cuts it.');
        assert.match(await browser.evaluate(`document.querySelector('#tek [data-slot=carousel-magnifier]').style.backgroundImage`), /width='1200'/, 'The pane shows the large copy (data-zoom-src).');
    } finally {
        await browser.close();
        remove();
    }
});

test('full screen shows a cropped photo whole, without the background picture of its frame round it', { skip }, async () => {
    const { url, remove } = page(markup.framed);
    const browser = await openPage(url, { width: 1280, height: 900 });
    try {
        await browser.waitFor(`document.getElementById('cerceve').hasAttribute('data-ready')`, { message: 'Swiper to start' });
        const frame = (where) => `getComputedStyle(document.querySelector('${where} .swiper-zoom-target'))`;
        assert.equal(await browser.evaluate(`${frame('#cerceve [data-slot=carousel-track]')}.backgroundImage`), 'linear-gradient(rgb(170, 0, 255), rgb(170, 0, 255))', 'On the page the tile stays (the photo covers it).');
        await browser.click('#cerceve [data-slot="carousel-fullscreen"]');
        const inner = `document.querySelector('#cerceve [data-carousel-lightbox]')`;
        await browser.waitFor(`${inner}.hasAttribute('data-ready')`, { message: 'the full-screen view' });
        assert.deepEqual(await browser.evaluate(`({ picture: ${frame('#cerceve [data-carousel-lightbox]')}.backgroundImage, fit: getComputedStyle(${inner}.querySelector('img')).objectFit })`), { picture: 'none', fit: 'contain' }, 'Whole, nothing coloured round it.');
        assert.match(await browser.evaluate(`${inner}.querySelector('img').getAttribute('src')`), /width='1600'/, 'Its large copy (data-zoom-src).');
        assert.deepEqual(browser.problems, []);
    } finally {
        await browser.close();
        remove();
    }
});
