import test from 'node:test';
import assert from 'node:assert/strict';

// The plugin core (js/core/plugins.js) as another package uses it (Acun Pro registers its own
// components the same way): define, mount once per element, instance or cleanup, a plugin defined
// later (from a chunk of its own) mounting on the elements already there, named components for the
// triggers, and the attributes a plugin manages, kept through a morph.

globalThis.acunTestPage = { alpine: false };
const { AcunUI, document, mount, settle } = await import('./browser.mjs');

const byId = (id) => document.getElementById(id);

test('define needs a selector (or attribute) and mount or load; the first definition of a name wins', () => {
    assert.throws(() => AcunUI.plugins.define('bos', {}), /needs a selector/);
    assert.throws(() => AcunUI.plugins.define('bos', { selector: '[data-bos]' }), /and mount\(\)/);

    const first = () => () => {};
    AcunUI.plugins.define('tekrar', { selector: '[data-tekrar]', mount: first });
    AcunUI.plugins.define('tekrar', { selector: '[data-tekrar]', mount: () => assert.fail('the second definition is ignored') });
    assert.ok(AcunUI.plugins.has('tekrar'));
});

test('a plugin mounts once per element, on the page and when added; its cleanup runs when removed', async () => {
    const log = [];
    AcunUI.plugins.define('sayac', {
        selector: '[data-sayac]',
        setup: () => log.push('setup'),
        mount(element) {
            log.push(`mount ${element.id}`);
            return () => log.push(`cleanup ${element.id}`);
        },
    });
    assert.deepEqual(log, ['setup'], 'setup runs once, when defined.');

    await mount('<div id="a" data-sayac></div>');
    AcunUI.init();
    AcunUI.init(byId('a'));
    byId('a').insertAdjacentHTML('afterend', '<div id="b" data-sayac></div>');
    await settle();
    byId('a').remove();
    await settle();

    assert.deepEqual(log, ['setup', 'mount a', 'mount b', 'cleanup a']);
    assert.equal(AcunUI.plugins.isMounted(byId('b'), 'sayac'), true);
    assert.equal(AcunUI.plugins.get(byId('b'), 'sayac'), null, 'A cleanup function is not an instance.');
});

test('a plugin defined after the page started mounts on the elements already there', async () => {
    await mount('<div id="gec" data-gec></div>');

    AcunUI.plugins.define('gec', { selector: '[data-gec]', mount: (element) => ({ element, destroy() {} }) });
    assert.equal(AcunUI.plugins.get(byId('gec'), 'gec').element, byId('gec'));
});

test('a named plugin of another package is found by name and opened by data-acun-open', async () => {
    const opened = [];
    AcunUI.plugins.define('panel-menu', {
        attribute: 'data-pro-menu',
        mount: (element) => ({ isOpen: false, open: () => opened.push(element.id), close() {} }),
    });

    await mount('<nav id="menu" data-pro-menu="ana-menu"></nav><button id="ac" data-acun-open="ana-menu">Menü</button>');
    byId('ac').click();
    assert.deepEqual(opened, ['menu']);
    assert.equal(AcunUI.plugins.find('ana-menu')[0], AcunUI.plugins.get(byId('menu')));
    assert.deepEqual(AcunUI.plugins.find('ana-menu', 'modal'), [], 'Only of that kind.');
});

test('managed attributes keep their values through a morph; others take the new markup', async () => {
    await mount('<div id="kok" hidden class="eski" data-acun-modal="morf"><div data-slot="modal-panel" role="dialog"></div></div>');
    const element = byId('kok');
    AcunUI.modal.open('morf');
    await settle();
    assert.equal(element.hidden, false);

    // What the server sends on an update: the modal closed, a new class.
    const next = document.createElement('div');
    next.innerHTML = '<div id="kok" hidden class="yeni" data-acun-modal="morf"></div>';
    const to = next.firstElementChild;
    AcunUI.plugins.keepOwned(element, to);

    assert.equal(to.hasAttribute('hidden'), false, 'hidden is the browser\'s (open).');
    assert.equal(to.hasAttribute('data-open'), true);
    assert.equal(to.getAttribute('class'), 'yeni');
    AcunUI.modal.close();
    await settle();
});

test('a mount that throws is reported and does not stop the others', async () => {
    AcunUI.plugins.define('bozuk', { selector: '[data-bozuk]', mount: () => { throw new Error('bozuldu'); } });
    const errors = [];
    const { error } = console;
    console.error = (...args) => errors.push(String(args[0]));
    try {
        await mount('<div data-bozuk></div><div id="saglam" data-sayac></div>');
        await settle();
    } finally {
        console.error = error;
    }

    assert.match(errors[0], /"bozuk" component could not start/);
    assert.equal(AcunUI.plugins.isMounted(byId('saglam'), 'sayac'), true);
});
