import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { syncSelectValues } from '../js/behavior/select-sync.js';

// Mounts `html` inside a Livewire component root whose client state (ephemeral)
// is `data`, installs the hook on a fake Livewire and runs one commit of that
// component through it. `rootAttributes` go on the component root (e.g.
// data-acun-select-sync); `head` lands in <head> (e.g. the runtime settings).
function commitWith(html, data, { rootAttributes = 'data-acun-select-sync', head = '', canonical = data } = {}) {
    const dom = new JSDOM(`<!doctype html><head>${head}</head><body><div wire:id="c1" ${rootAttributes}>${html}</div></body>`);
    const root = dom.window.document.body.firstElementChild;
    const component = { el: root, ephemeral: data, canonical };
    root.__livewire = component;

    // jsdom's selector engine does not match escaped colons (`[wire\:id]`),
    // which browsers do; resolve that one selector by hand.
    const closest = dom.window.Element.prototype.closest;
    dom.window.Element.prototype.closest = function (selector) {
        if (selector !== '[wire\\:id]') return closest.call(this, selector);
        let element = this;
        while (element && !element.hasAttribute('wire:id')) element = element.parentElement;
        return element;
    };

    let commitHook;
    syncSelectValues({ hook: (name, callback) => { if (name === 'commit') commitHook = callback; } });
    commitHook({ component, respond: (callback) => callback() });

    return dom.window.document;
}

const selected = (select) => Array.from(select.selectedOptions, (option) => option.value);

const singleSelect = `<select wire:model="status">
    <option value="taslak" selected>Taslak</option><option value="aktif">Aktif</option>
</select>`;

test('it is off by default: without the setting or data-acun-select-sync no box is touched', () => {
    const document = commitWith(singleSelect, { status: 'aktif' }, { rootAttributes: '' });

    assert.equal(document.querySelector('select').value, 'taslak');
});

test('the runtime setting (config acun-ui.select_sync) turns it on for every box', () => {
    const document = commitWith(singleSelect, { status: 'aktif' }, {
        rootAttributes: '',
        head: '<script type="application/json" data-acun-ui-config>{"selectSync":true}</script>',
    });

    assert.equal(document.querySelector('select').value, 'aktif');
});

test('it reads the client state (ephemeral), not the last server-confirmed one', () => {
    const document = commitWith(singleSelect, { status: 'aktif' }, { canonical: { status: 'taslak' } });

    assert.equal(document.querySelector('select').value, 'aktif');
});

test('a nested component keeps what the user picked: only the committing component is synced', () => {
    const document = commitWith(`${singleSelect}
        <div wire:id="c2"><select wire:model="group">
            <option value="a">A</option><option value="b" selected>B</option>
        </select></div>`, { status: 'aktif', group: 'a' });

    const [own, nested] = document.querySelectorAll('select');
    assert.equal(own.value, 'aktif');
    assert.equal(nested.value, 'b');
});

test('a multiple select is synced option by option from the array value', () => {
    const document = commitWith(`<select multiple wire:model="tags">
        <option value="1">Bir</option><option value="2">İki</option><option value="3" selected>Üç</option>
    </select>`, { tags: [1, '2'] });

    assert.deepEqual(selected(document.querySelector('select')), ['1', '2']);
});

test('an empty or null value clears a multiple select', () => {
    const document = commitWith(`<select multiple wire:model="tags">
        <option value="1" selected>Bir</option><option value="2" selected>İki</option>
    </select>`, { tags: null });

    assert.deepEqual(selected(document.querySelector('select')), []);
});

test('a single select still gets its value from nested properties', () => {
    const document = commitWith(`<select wire:model.live="editing.type">
        <option value="">Seçiniz</option><option value="a">A</option><option value="b">B</option>
    </select>`, { editing: { type: 'b' } });

    assert.equal(document.querySelector('select').value, 'b');
});
