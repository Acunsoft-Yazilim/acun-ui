import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, dispatch, document, mount, render, settle, sleep, window } from './browser.mjs';

// Closing a modal or a drawer from one of its parts (the X of the header, acun:modal.close,
// acun:drawer.close, the backdrop) gives the page back: the scroll lock is released and the
// dialog leaves the overlay stack. (The 2.0 Alpine components once looked for the X instead of
// the dialog: Alpine binds this.$el to the element whose directive runs. They kept the page
// locked and left a stale entry that stopped the next opening from locking at all.) Since 2.1
// the parts are data-acun-close / data-acun-close-now and the plugin closes the dialog the
// click is in; the same checks run on a page without Alpine in plain-html.test.mjs.

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
const isOpen = (element, kind) => AcunUI.plugins.get(element, kind).isOpen && element.hasAttribute('data-open') && !element.hidden;

async function openModal(name) {
    dispatch('acun:modal:open', { name });
    await settle();
    assert.ok(isOpen(modal(name), 'modal'), `${name} opened`);
    assert.ok(locked(), `${name} locks the page`);
}

async function openDrawer(name) {
    dispatch('acun:drawer:open', { name });
    await settle();
    assert.ok(isOpen(drawer(name), 'drawer'), `${name} opened`);
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
        assert.equal(isOpen(modal('klasik'), 'modal'), false);
        assert.equal(modal('klasik').hidden, true, 'Hidden after its leave transition.');
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
