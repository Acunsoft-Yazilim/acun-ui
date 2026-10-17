import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, Alpine, dispatch, document, mount, render, settle, sleep, window } from './browser.mjs';

// Closing a modal or a drawer from one of its parts (the X of the header, acun:modal.close,
// acun:drawer.close, the backdrop) gives the page back: the scroll lock is released and the
// dialog leaves the overlay stack. Alpine binds this.$el to the element whose directive runs,
// so a component that used this.$el in close() looked for the X instead of the dialog, kept
// the page locked (it could not scroll) and left a stale entry that stopped the next opening
// from locking at all.

const html = render({
    modals: `
        <acun:modal name="klasik" title="Klasik"><p>İçerik</p></acun:modal>
        <acun:modal name="parcali">
            <acun:modal.header title="Parçalı" />
            <acun:modal.body><p>İçerik</p></acun:modal.body>
            <acun:modal.footer>
                <acun:modal.close><acun:button id="modal-vazgec" variant="white">Vazgeç</acun:button></acun:modal.close>
            </acun:modal.footer>
        </acun:modal>`,
    drawers: `
        <acun:drawer name="detay">
            <acun:drawer.header title="Detay" />
            <acun:drawer.body><p>İçerik</p></acun:drawer.body>
            <acun:drawer.footer>
                <acun:drawer.close><acun:button id="drawer-vazgec" variant="white">Vazgeç</acun:button></acun:drawer.close>
            </acun:drawer.footer>
        </acun:drawer>`,
});

const locked = () => document.documentElement.classList.contains('scroll-locked');
const modal = (name) => document.querySelector(`[data-acun-modal="${name}"]`);
const drawer = (name) => document.querySelector(`[data-acun-drawer="${name}"]`);

async function openModal(name) {
    dispatch('acun:modal:open', { name });
    await settle();
    assert.ok(Alpine.$data(modal(name)).open, `${name} opened`);
    assert.ok(locked(), `${name} locks the page`);
}

async function openDrawer(name) {
    dispatch('acun:drawer:open', { name });
    await settle();
    assert.ok(Alpine.$data(drawer(name)).opened, `${name} opened`);
    assert.ok(locked(), `${name} locks the page`);
}

function assertReleased(what) {
    assert.equal(locked(), false, `${what}: the page scrolls again`);
    assert.equal(AcunUI.scrollLock.count, 0, `${what}: no lock left`);
    assert.equal(AcunUI.overlays.count, 0, `${what}: the dialog left the stack`);
}

test('the X of a classic modal releases the page, and the next opening locks it again', async () => {
    await mount(html.modals);

    for (const round of [1, 2]) {
        await openModal('klasik');
        modal('klasik').querySelector('button[aria-label]').click();
        await settle();
        assert.equal(Alpine.$data(modal('klasik')).open, false);
        assertReleased(`round ${round}`);
    }
});

test('the X of modal.header and acun:modal.close release the page', async () => {
    await mount(html.modals);

    await openModal('parcali');
    modal('parcali').querySelector('[data-slot="modal-header"] button[aria-label]').click();
    await settle();
    assertReleased('modal.header X');

    await openModal('parcali');
    document.getElementById('modal-vazgec').click();
    await settle();
    assertReleased('acun:modal.close');
});

test('a click on the modal backdrop releases the page', async () => {
    await mount(html.modals);
    await openModal('klasik');

    const layer = modal('klasik').querySelector('.ui-backdrop');
    layer.dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    await settle();
    assertReleased('backdrop');
});

test('the X of drawer.header and acun:drawer.close release the page', async () => {
    await mount(html.drawers);

    await openDrawer('detay');
    drawer('detay').querySelector('[data-slot="drawer-header"] button[aria-label]').click();
    await settle();
    await sleep(250);
    assertReleased('drawer.header X');

    await openDrawer('detay');
    document.getElementById('drawer-vazgec').click();
    await settle();
    await sleep(250);
    assertReleased('acun:drawer.close');
});
