import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, document, mount, render, settle } from './browser.mjs';
import { withCarouselLayout as withLayout } from './carousel-layout.mjs';
import { component, morphComponent, renderLivewire } from './livewire.mjs';

// Where acun:carousel's arrows go (arrows: sides, top-end, bottom-center, false) and acun:carousel.controls: a
// carousel's arrows elsewhere on the page (a section heading), linked by the carousel's id. The carousel runs them as its
// own: shown while there is something to slide to, aria-disabled at an end, and still after a Livewire update.

const slides = (count) => Array.from({ length: count }, (_, i) => `<acun:carousel.item label="Ürün ${i + 1}"><p>Ürün ${i + 1}</p></acun:carousel.item>`).join('');

const html = render({
    top: `<acun:carousel id="ust" arrows="top-end" :per-view="2" :loop="false" label="Yeni gelenler">${slides(4)}</acun:carousel>`,
    bottom: `<acun:carousel id="alt" arrows="bottom-center" :per-view="2" :loop="false" label="Markalar">${slides(4)}</acun:carousel>`,
    slider: `<acun:carousel id="slayt" arrows="top-end" label="Kampanyalar">${slides(3)}</acun:carousel>`,
    sides: `<acun:carousel id="yanlar" label="Duyurular">${slides(3)}</acun:carousel>`,
    remote: `<div id="baslik"><h2>Çok satanlar</h2><acun:carousel.controls for="uzak" counter /></div>
        <acun:carousel id="uzak" :arrows="false" :loop="false" :pagination="false" label="Çok satanlar">${slides(4)}</acun:carousel>`,
    looped: `<acun:carousel.controls for="dongu" :mobile="false" /><acun:carousel id="dongu" :arrows="false" label="Markalar">${slides(3)}</acun:carousel>`,
    single: `<acun:carousel.controls for="tek" /><acun:carousel id="tek" :arrows="false" label="Tek">${slides(1)}</acun:carousel>`,
});

async function ready(id) {
    const root = document.getElementById(id);
    for (let i = 0; i < 100 && !root.hasAttribute('data-ready'); i++) await settle();
    assert.equal(root.hasAttribute('data-ready'), true, `${id}: Swiper runs.`);

    return { root, carousel: AcunUI.plugins.get(root, 'carousel') };
}

const click = (element) => element.dispatchEvent(new document.defaultView.MouseEvent('click', { bubbles: true }));

test('arrows="top-end" puts the arrows side by side above the slides, bottom-center under them around a strip\'s dots', async () => {
    await mount(html.top + html.bottom + html.slider + html.sides);

    const top = document.getElementById('ust');
    const viewport = top.querySelector('[data-slot="carousel-viewport"]');
    const row = top.querySelector('[data-slot="carousel-prev"]').parentElement;
    assert.equal(row.nextElementSibling, viewport, 'The arrows come before the slides.');
    assert.deepEqual([...row.children].map((button) => button.dataset.slot), ['carousel-prev', 'carousel-next']);
    assert.match(row.className, /justify-end/);
    assert.match(row.className, /group-data-controls\/carousel:flex/, 'Shown while there is something to slide to.');
    assert.equal(viewport.id, 'ust-viewport');
    assert.deepEqual([...row.children].map((button) => button.getAttribute('aria-controls')), ['ust-viewport', 'ust-viewport']);
    assert.deepEqual([...row.children].map((button) => button.getAttribute('aria-label')), ['Önceki', 'Sonraki']);
    assert.match(row.firstElementChild.className, /focus-visible:outline-focus/);

    const bottom = document.getElementById('alt');
    const around = bottom.querySelector('[data-slot="carousel-prev"]').parentElement;
    assert.equal(around.previousElementSibling, bottom.querySelector('[data-slot="carousel-viewport"]'), 'Under the slides.');
    assert.deepEqual([...around.children].map((child) => child.dataset.slot), ['carousel-prev', 'carousel-dots', 'carousel-next']);
    assert.equal(bottom.querySelectorAll('[data-slot="carousel-dots"]').length, 1);

    // A slider: its dark frame goes round the slides alone, the arrows sit on the page.
    const slider = document.getElementById('slayt');
    assert.doesNotMatch(slider.className, /bg-inverse/);
    assert.match(slider.querySelector('[data-slot="carousel-viewport"]').className, /bg-inverse/);

    // The default: on both sides of the slides, inside them.
    const sides = document.getElementById('yanlar');
    assert.match(sides.className, /bg-inverse/);
    assert.equal(sides.querySelector('[data-slot="carousel-prev"]').parentElement, sides.querySelector('[data-slot="carousel-viewport"]'));
    assert.equal(sides.querySelector('[data-slot="carousel-prev"]').getAttribute('aria-controls'), 'yanlar-viewport');
});

test('the top-end arrows move the strip and stop at its ends', () => withLayout(async () => {
    await mount(html.top);
    const { root, carousel } = await ready('ust');
    const [prev, next] = ['carousel-prev', 'carousel-next'].map((slot) => root.querySelector(`[data-slot="${slot}"]`));

    assert.ok(root.hasAttribute('data-controls'));
    assert.equal(prev.getAttribute('aria-disabled'), 'true');
    click(next);
    await settle();
    assert.equal(carousel.index, 1);
    assert.equal(prev.getAttribute('aria-disabled'), 'false');
}));

test('acun:carousel.controls elsewhere on the page are the carousel\'s arrows and counter', () => withLayout(async () => {
    await mount(html.remote);
    const controls = document.querySelector('[data-acun-carousel-controls="uzak"]');
    const [prev, next, counter] = ['carousel-prev', 'carousel-next', 'carousel-counter'].map((slot) => controls.querySelector(`[data-slot="${slot}"]`));
    assert.match(controls.className, /\bhidden\b/);
    assert.match(controls.className, /(^| )data-controls:flex/);
    assert.deepEqual([prev, next].map((button) => button.getAttribute('aria-controls')), ['uzak-viewport', 'uzak-viewport']);
    assert.deepEqual([prev, next].map((button) => button.getAttribute('aria-label')), ['Önceki', 'Sonraki']);

    const { root, carousel } = await ready('uzak');
    assert.ok(document.getElementById('uzak-viewport'), 'aria-controls names the carousel\'s viewport.');
    assert.equal(root.querySelector('[data-slot="carousel-prev"]'), null, ':arrows="false": none in the carousel.');
    assert.ok(controls.hasAttribute('data-controls'), 'Shown: there is something to slide to.');
    assert.equal(prev.getAttribute('aria-disabled'), 'true', 'At the start.');
    assert.equal(counter.textContent, '1 / 4');

    click(next);
    await settle();
    assert.equal(carousel.index, 1);
    assert.equal(counter.textContent, '2 / 4');
    assert.equal(prev.getAttribute('aria-disabled'), 'false');

    carousel.goTo(3);
    await settle();
    assert.equal(next.getAttribute('aria-disabled'), 'true', 'At the end.');
    click(next);
    await settle();
    assert.equal(carousel.index, 3, 'A disabled arrow does nothing.');
    click(prev);
    await settle();
    assert.equal(carousel.index, 2);

    // Taken off, the carousel hides them again.
    AcunUI.destroy(root);
    assert.equal(controls.hasAttribute('data-controls'), false);
    click(next);
    await settle();
}));

test('with a loop the controls are always enabled; with nothing to slide to they stay hidden; :mobile="false" hides them on phones', () => withLayout(async () => {
    await mount(html.looped + html.single);
    const looped = document.querySelector('[data-acun-carousel-controls="dongu"]');
    assert.match(looped.className, /sm:data-controls:flex/);
    assert.doesNotMatch(looped.className, /(^| )data-controls:flex/);

    const { carousel } = await ready('dongu');
    assert.ok(looped.hasAttribute('data-controls'));
    click(looped.querySelector('[data-slot="carousel-prev"]'));
    await settle();
    assert.equal(carousel.index, 2, 'Previous from the first is the last (loop).');
    assert.deepEqual([...looped.querySelectorAll('button')].map((button) => button.getAttribute('aria-disabled')), [null, null]);

    await ready('tek');
    assert.equal(document.querySelector('[data-acun-carousel-controls="tek"]').hasAttribute('data-controls'), false, 'One slide: nothing to slide to.');
}));

test('after a Livewire update the controls keep their state and still move the carousel; new ones are taken in', () => withLayout(async () => {
    const { page } = renderLivewire({ page: 'vitrin' });
    await mount(page.first);
    const { carousel } = await ready('vitrin-cok-satanlar');
    const controls = () => document.querySelector('[data-acun-carousel-controls="vitrin-cok-satanlar"]');
    const part = (slot) => controls().querySelector(`[data-slot="${slot}"]`);

    click(part('carousel-next'));
    await settle();
    assert.equal(carousel.index, 1);

    for (const round of [1, 2]) {
        morphComponent(component('vitrin'), page.updates.vitrin);
        await settle();
        assert.ok(controls().hasAttribute('data-controls'), `round ${round}: still shown`);
        assert.equal(part('carousel-prev').getAttribute('aria-disabled'), 'false', `round ${round}: the morph keeps the state`);
        assert.equal(part('carousel-counter').textContent, '2 / 4', `round ${round}: the counter keeps its text`);
    }
    click(part('carousel-next'));
    await settle();
    assert.equal(carousel.index, 2, 'The arrows still move it.');

    // A group drawn again from the server's markup (a new element): the carousel takes it in.
    const fresh = document.createElement('div');
    fresh.innerHTML = page.updates.vitrin;
    controls().replaceWith(fresh.querySelector('[data-acun-carousel-controls]'));
    for (let i = 0; i < 5 && !controls().hasAttribute('data-controls'); i++) await settle();
    assert.ok(controls().hasAttribute('data-controls'), 'The new group is shown.');
    assert.equal(part('carousel-counter').textContent, '3 / 4');
    click(part('carousel-prev'));
    await settle();
    assert.equal(carousel.index, 1);
}));
