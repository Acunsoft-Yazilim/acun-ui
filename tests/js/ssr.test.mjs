import test from 'node:test';
import assert from 'node:assert/strict';

// Server-side rendering (Next.js, Nuxt) imports '@acunsoft/acun-ui' in Node, where there is no
// window, document or localStorage: the import must not throw and must touch none of them. The
// runtime starts only in a browser.

test('importing the runtime without a DOM does not throw and starts nothing', async () => {
    assert.equal(typeof window, 'undefined');
    assert.equal(typeof document, 'undefined');

    const runtime = await import('../../js/index.js');

    assert.equal(typeof runtime.VERSION, 'string');
    assert.equal(typeof runtime.plugins.define, 'function');
    assert.equal(typeof runtime.init, 'function');
    assert.equal(typeof runtime.destroy, 'function');
    assert.equal(typeof runtime.modalPlugin.mount, 'function');
    assert.equal(typeof globalThis.AcunUI, 'undefined', 'No global without a browser.');
    // A component's effect cleanup that runs on the server anyway does nothing.
    assert.doesNotThrow(() => runtime.destroy());
    assert.doesNotThrow(() => runtime.init());
});

test('the plugin modules (deep imports) load without a DOM too', async () => {
    for (const module of ['core/plugins.js', 'plugins/modal.js', 'plugins/drawer.js', 'adapters/alpine.js', 'adapters/livewire.js', 'core/transition.js']) {
        await assert.doesNotReject(import(`../../js/${module}`), module);
    }
});
