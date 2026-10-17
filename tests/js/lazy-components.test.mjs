import test from 'node:test';
import assert from 'node:assert/strict';
import { Alpine, AcunUI, document, mount, settle, window } from './browser.mjs';

// Lazy components (core/components.js): an element with data-acun-component="name" waits
// for its module, which is loaded once, the first time such an element is on the page.

const { loadComponentsOnPage } = await import('../../js/core/components.js');

/** A component whose module "arrives" when the test says so; counts its imports. */
function lazyComponent(name, { defer = true } = {}) {
    const state = { imports: 0, release: null };
    const arrived = new Promise((resolve) => { state.release = resolve; });

    AcunUI.components.define(name, async () => {
        state.imports++;
        await arrived;
        Alpine.data(name, () => ({ label: `${name} hazır` }));
    }, { defer });

    return state;
}

const ready = (element) => element.textContent.trim();

test('the element waits for its module, then Alpine starts it; the server HTML shows meanwhile', async () => {
    const component = lazyComponent('tablo');
    const main = await mount('<div data-acun-component="tablo" x-data="tablo"><span x-text="label">Sunucu içeriği</span></div>');
    const element = main.firstElementChild;

    assert.equal(component.imports, 1);
    assert.equal(ready(element), 'Sunucu içeriği', 'Untouched (and no "tablo is not defined").');

    component.release();
    await settle();

    assert.equal(ready(element), 'tablo hazır');
    assert.equal(AcunUI.components.isLoaded('tablo'), true);
});

test('five elements of one component: its module is imported once', async () => {
    const component = lazyComponent('grafik');
    const main = await mount('<div data-acun-component="grafik" x-data="grafik"><b x-text="label"></b></div>'.repeat(5));

    component.release();
    await settle();

    assert.equal(component.imports, 1);
    assert.deepEqual([...main.children].map(ready), Array(5).fill('grafik hazır'));
});

test('an element added later (conditional rendering, a Livewire morph) loads its module then', async () => {
    const component = lazyComponent('yukleme');
    const main = await mount('<p>Önce yok</p>');
    assert.equal(component.imports, 0, 'Not loaded while there is no such element.');

    main.insertAdjacentHTML('beforeend', '<div data-acun-component="yukleme" x-data="yukleme"><i x-text="label"></i></div>');
    await settle();
    assert.equal(component.imports, 1);

    component.release();
    await settle();
    assert.equal(ready(main.lastElementChild), 'yukleme hazır');

    // One more after the module is here: Alpine starts it right away.
    main.insertAdjacentHTML('beforeend', '<div data-acun-component="yukleme" x-data="yukleme"><i x-text="label"></i></div>');
    await settle();
    assert.equal(ready(main.lastElementChild), 'yukleme hazır');
    assert.equal(component.imports, 1);
});

test('a page swapped in by wire:navigate and a nested Livewire component load as well', async () => {
    const component = lazyComponent('secici');
    await mount('<p>İlk sayfa</p>');

    // A new page: the old body goes, the new one comes in (as wire:navigate does).
    const main = await mount('<div wire:id="dis"><div wire:id="ic"><div data-acun-component="secici" x-data="secici"><i x-text="label"></i></div></div></div>');
    component.release();
    await settle();

    assert.equal(ready(main.querySelector('[data-acun-component]')), 'secici hazır');
});

test('an element removed before its module arrived is not started', async () => {
    const component = lazyComponent('gecici');
    const main = await mount('<div data-acun-component="gecici" x-data="gecici"><i x-text="label">eski</i></div>');
    const element = main.firstElementChild;

    element.remove();
    component.release();
    await settle();

    assert.equal(ready(element), 'eski');
});

test('a component that only has to be on the page (defer false) does not hold up Alpine', async () => {
    const component = lazyComponent('form-yoneticisi', { defer: false });
    const main = await mount('<div data-acun-component="form-yoneticisi" x-data="{ hazir: \'şimdi\' }"><i x-text="hazir"></i></div>');

    assert.equal(component.imports, 1);
    assert.equal(ready(main.firstElementChild), 'şimdi', 'Started at once.');
    component.release();
});

test('a Livewire component root is never held back (Livewire starts it in the same pass)', async () => {
    const component = lazyComponent('kok');
    Alpine.data('kokHazir', () => ({ label: 'kök' }));
    const main = await mount('<div wire:id="k1" data-acun-component="kok" x-data="kokHazir"><i x-text="label"></i></div>');

    assert.equal(ready(main.firstElementChild), 'kök');
    assert.equal(component.imports, 1, 'Its module still loads.');
    component.release();
});

test('elements without a definition are left to Alpine as usual', async () => {
    const main = await mount('<div data-acun-component="tanimsiz" x-data="{ label: \'normal\' }"><i x-text="label"></i></div>');

    assert.equal(ready(main.firstElementChild), 'normal');
});

test('AcunUI.load imports a module ahead (prefetch), once', async () => {
    const component = lazyComponent('editor');

    const first = AcunUI.load('editor');
    const second = AcunUI.load('editor');
    component.release();
    await Promise.all([first, second]);

    assert.equal(component.imports, 1);
    assert.equal(AcunUI.components.isLoaded('editor'), true);
    await assert.rejects(AcunUI.load('yok'), /Unknown component: yok/);
});

test('the first page starts loading what it has before Alpine reaches it', async () => {
    const component = lazyComponent('harita');
    const page = document.createElement('div');
    page.innerHTML = '<div data-acun-component="harita"></div><div data-acun-component="harita"></div>';

    loadComponentsOnPage(page);
    loadComponentsOnPage(page);
    await settle();

    assert.equal(component.imports, 1);
    component.release();
});

test('a module that failed to load is tried again, and its waiting element starts then', async () => {
    let attempts = 0;
    AcunUI.components.define('kargo', async () => {
        attempts++;
        if (attempts === 1) throw new Error('Failed to fetch dynamically imported module');
        Alpine.data('kargo', () => ({ label: 'kargo hazır' }));
    });
    const errors = [];
    const consoleError = console.error;
    console.error = (...args) => errors.push(args[0]);

    try {
        const main = await mount('<div data-acun-component="kargo" x-data="kargo"><span x-text="label">Sunucu içeriği</span></div>');
        await settle();
        assert.equal(attempts, 1);
        assert.equal(ready(main.firstElementChild), 'Sunucu içeriği', 'Left as the server drew it.');
        assert.match(errors[0], /"kargo" component could not be loaded; it is tried again/);

        // Back online (or the automatic retry a moment later): the module loads and the element starts.
        window.dispatchEvent(new window.Event('online'));
        await settle();
        assert.equal(attempts, 2);
        assert.equal(ready(main.firstElementChild), 'kargo hazır');
        assert.equal(AcunUI.components.isLoaded('kargo'), true);
    } finally {
        console.error = consoleError;
    }
});
