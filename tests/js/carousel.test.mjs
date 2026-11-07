import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, document, mount, press, render, settle, window } from './browser.mjs';
import { withCarouselLayout as withLayout } from './carousel-layout.mjs';

// acun:carousel's options on Swiper (js/plugins/carousel-engine.js): each prop reaches Swiper as its option, the
// counter, the thumbnails, the ends of a carousel that does not loop, and taking Swiper off and on again
// (AcunUI.destroy / AcunUI.init, as a Livewire re-render does through the observer). The sliding itself is checked in a
// real browser (carousel-chrome.test.mjs). jsdom has no layout: the carousel and its slides get a width here.

const slides = (count, tag = 'acun:carousel.item') => Array.from({ length: count }, (_, i) => `<${tag}><p>Slayt ${i + 1}</p></${tag}>`).join('');
const thumbs = (count) => `<acun:slot:thumbnails>${Array.from({ length: count }, (_, i) => `<acun:carousel.thumb label="Görsel ${i + 1}"><img src="/k${i}.jpg" alt=""></acun:carousel.thumb>`).join('')}</acun:slot:thumbnails>`;

const html = render({
    options: `<acun:carousel id="secenekler" loop="infinite" centered :draggable="false" :snap="false" auto-height counter>${slides(4)}</acun:carousel>`,
    strip: `<acun:carousel id="serit" :per-view="['base' => 2, 'lg' => 4]" :gap="3" :loop="false" label="Önerilen ürünler">${slides(6)}</acun:carousel>`,
    gallery: `<acun:carousel id="galeri" thumbs="vertical" zoom :pagination="false" counter>${slides(4)}${thumbs(4)}</acun:carousel>`,
    plain: `<acun:carousel id="duz">${slides(3)}</acun:carousel>`,
});

async function ready(id) {
    const root = document.getElementById(id);
    for (let i = 0; i < 100 && !root.hasAttribute('data-ready'); i++) await settle();
    assert.equal(root.hasAttribute('data-ready'), true, `${id}: Swiper runs.`);

    return { root, carousel: AcunUI.plugins.get(root, 'carousel') };
}

test('each prop reaches Swiper as its option; the counter shows the position', () => withLayout(async () => {
    await mount(html.options);
    const { root, carousel } = await ready('secenekler');
    const { params } = carousel.swiper;

    assert.equal(root.dataset.loop, 'infinite');
    assert.deepEqual([params.loop, params.rewind, params.centeredSlides, params.simulateTouch, params.grabCursor, params.freeMode.enabled, params.autoHeight], [true, false, true, false, false, true, true]);
    assert.ok(root.querySelector('[data-slot="carousel-track"]').classList.contains('items-start'), 'Auto height: the slides keep their own height.');
    assert.equal(root.querySelector('[data-slot="carousel-counter"]').textContent, '1 / 4');
    assert.equal(root.querySelector('[data-slot="carousel-counter"]').getAttribute('aria-hidden'), 'true', 'Screen readers hear the slide\'s own name.');

    carousel.goTo(2);
    await settle();
    assert.equal(carousel.index, 2);
    assert.equal(root.querySelector('[data-slot="carousel-counter"]').textContent, '3 / 4');
    assert.deepEqual([...root.querySelectorAll('[data-slot="carousel-dot-button"]')].map((dot) => dot.getAttribute('aria-current')), ['false', 'false', 'true', 'false']);
    // The infinite loop moves slides around; their names stay their place among the slides as written.
    const names = [...root.querySelectorAll('[data-slot="carousel-item"]')].map((slide) => slide.getAttribute('aria-label')).sort();
    assert.deepEqual(names, ['1 / 4', '2 / 4', '3 / 4', '4 / 4']);
}));

test('a strip: several in view by breakpoint, the CSS gap is Swiper\'s space, and it stops at the ends', () => withLayout(async () => {
    await mount(html.strip);
    const root = document.getElementById('serit');
    // 2xl (96rem) is a breakpoint too (2026-11-07): left out, it takes xl's.
    assert.equal(root.getAttribute('style'), '--carousel-per-view-base: 2; --carousel-per-view-sm: 2; --carousel-per-view-md: 2; --carousel-per-view-lg: 4; --carousel-per-view-xl: 4; --carousel-per-view-2xl: 4');
    assert.match(root.className, /2xl:\[--carousel-per-view:var\(--carousel-per-view-2xl\)\]/);
    assert.doesNotMatch(root.className, /@container/, 'By the screen unless per-view-base="container".');
    assert.ok(root.hasAttribute('data-strip'));
    assert.match(root.className, /\[--carousel-gap:--spacing\(3\)\]/);
    const { carousel } = await ready('serit');

    assert.equal(carousel.swiper.params.rewind, false);
    assert.equal(root.querySelector('[data-slot="carousel-prev"]').getAttribute('aria-disabled'), 'true', 'At the start: nothing before.');
    assert.equal(root.querySelector('[data-slot="carousel-next"]').getAttribute('aria-disabled'), 'false');
    assert.ok([...root.querySelectorAll('[data-slot="carousel-item"]')].every((slide) => !slide.inert), 'A strip keeps every card reachable with Tab.');
}));

test('thumbnails (vertical) follow the shown slide and show theirs; zoom is on', () => withLayout(async () => {
    await mount(html.gallery);
    const { root, carousel } = await ready('galeri');
    const thumbButtons = [...root.querySelectorAll('[data-slot="carousel-thumb"]')];

    assert.equal(carousel.thumbs.params.direction, 'vertical', 'Swiper Thumbs, a column beside the slides.');
    assert.equal(carousel.swiper.params.zoom.enabled, true);
    assert.equal(root.querySelectorAll('.acun-carousel-zoom').length, 4, 'Each slide\'s picture is in a zoom container.');
    assert.equal(root.querySelector('[data-slot="carousel-thumbs"]').getAttribute('aria-label'), 'Küçük görseller');
    assert.deepEqual(thumbButtons.map((thumb) => thumb.getAttribute('aria-current')), ['true', 'false', 'false', 'false']);

    thumbButtons[3].click();
    assert.equal(carousel.index, 3);
    assert.deepEqual(thumbButtons.map((thumb) => thumb.getAttribute('aria-current')), ['false', 'false', 'false', 'true']);
    assert.equal(root.querySelector('[data-slot="carousel-counter"]').textContent, '4 / 4');

    const zoom = root.querySelector('[data-slot="carousel-zoom"]');
    assert.equal(zoom.getAttribute('aria-label'), 'Yakınlaştır');
}));

test('AcunUI.destroy takes Swiper off and leaves the server\'s markup; AcunUI.init starts it again', () => withLayout(async () => {
    await mount(html.plain);
    const { root } = await ready('duz');
    const track = root.querySelector('[data-slot="carousel-track"]');
    assert.equal(root.querySelectorAll('[data-slot="carousel-dot-button"]').length, 3);

    AcunUI.destroy(root);
    assert.equal(root.hasAttribute('data-ready'), false);
    assert.equal(root.querySelectorAll('[data-slot="carousel-dot-button"]').length, 0);
    assert.equal(track.getAttribute('style') ?? '', '', 'Swiper\'s styles are gone: the no-JS layout is back.');
    assert.deepEqual([...track.children].map((slide) => [slide.inert, slide.getAttribute('aria-label')]), [[false, null], [false, null], [false, null]]);

    AcunUI.init(root);
    const again = await ready('duz');
    assert.notEqual(again.carousel.swiper, null);
    assert.equal(root.querySelectorAll('[data-slot="carousel-dot-button"]').length, 3);
    assert.deepEqual([...track.children].map((slide) => slide.getAttribute('aria-label')), ['1 / 3', '2 / 3', '3 / 3']);

    // Removed from the page (a Livewire re-render), the observer takes it off.
    const swiper = again.carousel.swiper;
    root.remove();
    await settle();
    assert.equal(swiper.destroyed, true);
}));

const setsHtml = render({
    sets: `<acun:carousel id="renkler" set="siyah" counter>
        <acun:carousel.item><p>Siyah 1</p></acun:carousel.item><acun:carousel.item><p>Siyah 2</p></acun:carousel.item>
        <acun:slot:thumbnails><acun:carousel.thumb label="Siyah 1">S1</acun:carousel.thumb><acun:carousel.thumb label="Siyah 2">S2</acun:carousel.thumb></acun:slot:thumbnails>
        <acun:carousel.set name="siyah" label="Siyah renk görselleri gösteriliyor"><acun:carousel.item><p>Siyah 1</p></acun:carousel.item><acun:carousel.item><p>Siyah 2</p></acun:carousel.item><acun:slot:thumbnails><acun:carousel.thumb label="Siyah 1">S1</acun:carousel.thumb><acun:carousel.thumb label="Siyah 2">S2</acun:carousel.thumb></acun:slot:thumbnails></acun:carousel.set>
        <acun:carousel.set name="kirmizi" label="Kırmızı renk görselleri gösteriliyor"><acun:carousel.item><p>Kırmızı 1</p></acun:carousel.item><acun:carousel.item><p>Kırmızı 2</p></acun:carousel.item><acun:carousel.item><p>Kırmızı 3</p></acun:carousel.item><acun:slot:thumbnails><acun:carousel.thumb label="Kırmızı 1">K1</acun:carousel.thumb><acun:carousel.thumb label="Kırmızı 2">K2</acun:carousel.thumb><acun:carousel.thumb label="Kırmızı 3">K3</acun:carousel.thumb></acun:slot:thumbnails></acun:carousel.set>
    </acun:carousel>`,
    fullscreen: `<acun:carousel id="tam" fullscreen zoom label="Ürün">
        <acun:carousel.item><div class="swiper-zoom-target" role="img" aria-label="Bir">1</div></acun:carousel.item>
        <acun:carousel.item><div class="swiper-zoom-target" role="img" aria-label="İki">2</div></acun:carousel.item>
        <acun:carousel.item><div class="swiper-zoom-target" role="img" aria-label="Üç">3</div></acun:carousel.item>
        <acun:slot:thumbnails><acun:carousel.thumb label="Bir">1</acun:carousel.thumb><acun:carousel.thumb label="İki">2</acun:carousel.thumb><acun:carousel.thumb label="Üç">3</acun:carousel.thumb></acun:slot:thumbnails>
    </acun:carousel>`,
});

test('a set (a product\'s other colour) takes the slides\' and thumbnails\' place at the first slide and is announced; one asked for early waits', () => withLayout(async () => {
    await mount(setsHtml.sets);
    const root = document.getElementById('renkler');
    // Asked for before the sliding has loaded (the purchase box starts with another colour): kept, then shown.
    root.dispatchEvent(new window.CustomEvent('acun:carousel:set', { detail: { name: 'kirmizi' } }));
    const { carousel } = await ready('renkler');
    const texts = () => [...root.querySelectorAll('[data-slot="carousel-track"] > [data-slot="carousel-item"]')].map((slide) => slide.textContent.trim());
    const thumbTexts = () => [...root.querySelectorAll(':scope > [data-slot="carousel-thumbs"] [data-slot="carousel-thumb"]')].map((thumb) => `${thumb.textContent.trim()}:${thumb.getAttribute('aria-current')}`);
    const status = root.querySelector('[data-slot="carousel-status"]');

    assert.equal(status.getAttribute('role'), 'status');
    for (let i = 0; i < 20 && root.dataset.set !== 'kirmizi'; i++) await settle();
    assert.equal(root.dataset.set, 'kirmizi');
    assert.deepEqual(texts(), ['Kırmızı 1', 'Kırmızı 2', 'Kırmızı 3']);
    assert.deepEqual(thumbTexts(), ['K1:true', 'K2:false', 'K3:false']);
    assert.equal(root.querySelector('[data-slot="carousel-counter"]').textContent, '1 / 3');
    await settle();
    assert.equal(status.textContent, 'Kırmızı renk görselleri gösteriliyor');

    // Back to black from the second slide: the first slide again.
    carousel.goTo(1);
    root.dispatchEvent(new window.CustomEvent('acun:carousel:set', { detail: { name: 'siyah' } }));
    await settle();
    assert.deepEqual(texts(), ['Siyah 1', 'Siyah 2']);
    assert.equal(carousel.index, 0);
    assert.deepEqual(thumbTexts(), ['S1:true', 'S2:false']);
    assert.equal(status.textContent, 'Siyah renk görselleri gösteriliyor');
    assert.deepEqual([...root.querySelectorAll('[data-slot="carousel-item"]:not(template *)')].map((slide) => slide.getAttribute('aria-label')), ['1 / 2', '2 / 2']);
    assert.equal(carousel.showSet('yok'), false, 'An unknown set changes nothing.');
}));

test('full screen: the dialog opens at the shown slide with copies of the slides, Escape zooms out first, then closes and leaves the page at its slide', () => withLayout(async () => {
    await mount(html.plain + setsHtml.fullscreen);
    const { root, carousel } = await ready('tam');
    const lightbox = root.querySelector('[data-slot="carousel-lightbox"]');
    const inner = lightbox.querySelector('[data-carousel-lightbox]');
    const button = root.querySelector('[data-slot="carousel-fullscreen"]');
    assert.equal(lightbox.getAttribute('data-acun-modal'), root.dataset.fullscreen);
    assert.equal(button.getAttribute('aria-haspopup'), 'dialog');
    assert.equal(button.getAttribute('aria-label'), 'Tam ekran');
    assert.equal(carousel.swiper.params.zoom.toggle, false, 'A tap opens the full-screen view: no double-tap zoom on the page.');

    carousel.goTo(1);
    button.click();
    for (let i = 0; i < 50 && !inner.hasAttribute('data-ready'); i++) await settle();
    const innerCarousel = AcunUI.plugins.get(inner, 'carousel');
    assert.equal(lightbox.hasAttribute('data-open'), true, 'The modal plugin opened it.');
    assert.equal(innerCarousel.index, 1, 'At the shown slide.');
    assert.deepEqual([...inner.querySelectorAll('[data-slot="carousel-item"]')].map((slide) => slide.textContent.trim()), ['1', '2', '3']);
    assert.equal(inner.querySelectorAll('[data-slot="carousel-thumb"]').length, 3, 'The thumbnails come too.');
    assert.equal(inner.querySelectorAll('[data-slot="carousel-track"] [id], [data-slot="carousel-thumbs"] [id]').length, 0, 'The copies carry no id of the page.');
    assert.notEqual(inner.querySelector('[data-slot="carousel-viewport"]').id, root.querySelector('[data-slot="carousel-viewport"]').id, 'The dialog\'s viewport has an id of its own (its arrows\' aria-controls).');
    assert.ok([...inner.querySelectorAll('[data-slot="carousel-item"]')].every((slide) => slide.querySelector('.acun-carousel-zoom > .swiper-zoom-target')), 'Every picture zooms there.');
    assert.equal(innerCarousel.swiper.params.zoom.toggle, true, 'In the dialog a double tap zooms.');

    // Its arrows move it; the page's carousel stays where it was meanwhile.
    innerCarousel.next();
    assert.equal(innerCarousel.index, 2);
    assert.equal(carousel.index, 1);

    // Escape: zoomed in, it zooms out (the dialog stays); then it closes.
    innerCarousel.swiper.zoom.in();
    assert.equal(inner.hasAttribute('data-zoomed'), true);
    const slide = inner.querySelectorAll('[data-slot="carousel-item"]')[2];
    press('Escape', {}, slide);
    assert.equal(inner.hasAttribute('data-zoomed'), false, 'The first Escape zooms out.');
    assert.equal(lightbox.hasAttribute('data-open'), true, '…and leaves the dialog open.');
    press('Escape', {}, slide);
    for (let i = 0; i < 20 && !lightbox.hidden; i++) await settle();
    assert.equal(lightbox.hidden, true, 'The second Escape closes it.');
    assert.equal(carousel.index, 2, 'The page\'s carousel shows the slide the dialog showed last.');
    assert.equal(inner.querySelectorAll('[data-slot="carousel-item"]').length, 0, 'The copies are gone.');

    // A click on a slide's picture opens it too; on a link in a slide it does not.
    carousel.swiper.emit('click', { target: root.querySelectorAll('[data-slot="carousel-viewport"] [data-slot="carousel-item"]')[0].querySelector('.swiper-zoom-target') });
    assert.equal(lightbox.hasAttribute('data-open'), true);
    AcunUI.modal.close(lightbox.dataset.acunModal);
    for (let i = 0; i < 20 && !lightbox.hidden; i++) await settle();
}));

const fewHtml = render({
    few: `<acun:carousel id="tek" set="tek" :pagination="false">
        <acun:carousel.item><p>Tek</p></acun:carousel.item>
        <acun:slot:thumbnails><!-- one picture --></acun:slot:thumbnails>
        <acun:carousel.set name="tek" label="Tek"><acun:carousel.item><p>Tek</p></acun:carousel.item></acun:carousel.set>
        <acun:carousel.set name="cok" label="Çok"><acun:carousel.item><p>Bir</p></acun:carousel.item><acun:carousel.item><p>İki</p></acun:carousel.item><acun:carousel.item><p>Üç</p></acun:carousel.item><acun:slot:thumbnails><acun:carousel.thumb label="Bir">1</acun:carousel.thumb><acun:carousel.thumb label="İki">2</acun:carousel.thumb><acun:carousel.thumb label="Üç">3</acun:carousel.thumb></acun:slot:thumbnails></acun:carousel.set>
    </acun:carousel>`,
});

test('a set with one picture hides the thumbnails\' strip; one with several shows it (made then) and its thumbnails work', () => withLayout(async () => {
    await mount(fewHtml.few);
    const { root, carousel } = await ready('tek');
    const strip = root.querySelector(':scope > [data-slot="carousel-thumbs"]');
    const thumbButtons = () => [...strip.querySelectorAll('[data-slot="carousel-thumb"]')];
    assert.equal(strip.hidden, true, 'One picture: no strip.');
    assert.equal(carousel.thumbs, null);

    carousel.showSet('cok');
    assert.equal(strip.hidden, false, 'Several pictures: the strip shows.');
    assert.notEqual(carousel.thumbs, null, 'Its Swiper is made now.');
    assert.equal(carousel.swiper.thumbs.swiper, carousel.thumbs, 'Swiper Thumbs follows it.');
    assert.deepEqual(thumbButtons().map((thumb) => thumb.getAttribute('aria-current')), ['true', 'false', 'false']);
    thumbButtons()[2].click();
    assert.equal(carousel.index, 2);
    assert.deepEqual(thumbButtons().map((thumb) => thumb.getAttribute('aria-current')), ['false', 'false', 'true']);

    carousel.showSet('tek');
    assert.equal(strip.hidden, true, 'Back to one picture: the strip hides…');
    assert.equal(thumbButtons().length, 0, '…and keeps no thumbnail of slides that are gone.');
    assert.equal(carousel.index, 0);
}));

test('a full-screen view closed before it started stays empty', () => withLayout(async () => {
    await mount(setsHtml.fullscreen);
    const { root, carousel } = await ready('tam');
    const lightbox = root.querySelector('[data-slot="carousel-lightbox"]');
    const inner = lightbox.querySelector('[data-carousel-lightbox]');
    carousel.openFullscreen();
    AcunUI.modal.close(lightbox.dataset.acunModal);
    for (let i = 0; i < 20 && !lightbox.hidden; i++) await settle();
    await settle();
    assert.equal(lightbox.hidden, true);
    assert.equal(inner.hasAttribute('data-ready'), false, 'No carousel started in the closed dialog.');
    assert.equal(inner.querySelectorAll('[data-slot="carousel-item"]').length, 0);
}));
