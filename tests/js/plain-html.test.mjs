import test from 'node:test';
import assert from 'node:assert/strict';

// The modal and the drawer on a page with no Alpine and no Livewire: the free npm package's
// HTML (as the documentation shows it, written by hand), the runtime alone. Opening and closing
// by the triggers, the API and the events; Escape, the backdrop, the close guard; focus first,
// trap and restore; the page scroll lock and its release (close-releases-page.test.mjs in plain
// HTML); one modal replacing another; the state on the markup; elements added and removed later.

globalThis.acunTestPage = { alpine: false };
const { AcunUI, dispatch, document, mount, press, settle, sleep, window } = await import('./browser.mjs');

const modalHtml = (name, { title = 'Sipariş #4821', body = '<input id="alan">', guard = '', backdrop = true } = {}) => `
    <div data-acun-modal="${name}" ${guard ? `data-request-close-event="${guard}"` : ''} hidden class="fixed inset-0 z-50">
        <div data-slot="modal-backdrop" ${backdrop ? 'data-acun-close' : ''} aria-hidden="true"></div>
        <div data-slot="modal-panel" role="dialog" aria-modal="true" tabindex="-1">
            <div data-slot="modal-header">
                <h3 data-slot="modal-title">${title}</h3>
                <p data-slot="modal-description">Ayşe Yılmaz, 3 ürün</p>
                <button type="button" data-acun-close aria-label="Kapat" id="${name}-x">×</button>
            </div>
            <div data-slot="modal-body">${body}</div>
            <div data-slot="modal-footer">
                <button type="button" data-acun-close-now id="${name}-vazgec">Vazgeç</button>
            </div>
        </div>
    </div>`;

const drawerHtml = (name, { attributes = '' } = {}) => `
    <div data-acun-drawer="${name}" id="${name}" ${attributes} hidden role="dialog" aria-modal="true" class="relative z-50">
        <div data-slot="drawer-backdrop" data-acun-close aria-hidden="true"></div>
        <div>
            <div data-slot="drawer-panel" tabindex="-1">
                <div data-slot="drawer-header"><h3 data-slot="drawer-title">Filtreler</h3><button type="button" data-acun-close aria-label="Kapat">×</button></div>
                <input id="${name}-alan"><a id="${name}-link" href="#kargo">Kargo</a>
                <div data-drawer-keep-open><a id="${name}-kalsin" href="#sekme">Sekme</a></div>
            </div>
        </div>
    </div>`;

const page = `
    <button type="button" id="ac" data-acun-open="siparis" aria-haspopup="dialog">Siparişi göster</button>
    <button type="button" id="filtre-dugme" data-acun-toggle="filtreler" aria-expanded="false">Filtreler</button>
    <span data-acun-open="musteri" class="contents"><button type="button" id="sarili" aria-expanded="false">Müşteri</button></span>
    ${modalHtml('siparis')}
    ${modalHtml('musteri', { title: 'Müşteri', body: '<button type="button" id="onay-ac" data-acun-open="onay">Sil</button>' })}
    ${modalHtml('onay', { title: 'Silinsin mi?', body: '<button id="evet">Evet</button>' })}
    ${modalHtml('sorulu', { title: 'Kaydedilmemiş değişiklikler', guard: 'sorulu-kapat' })}
    ${drawerHtml('filtreler')}`;

const byId = (id) => document.getElementById(id);
const root = (name) => document.querySelector(`[data-acun-modal="${name}"], [data-acun-drawer="${name}"]`);
const instance = (name) => AcunUI.plugins.get(root(name));
const isOpen = (name) => instance(name).isOpen && root(name).hasAttribute('data-open') && !root(name).hidden;
const locked = () => document.documentElement.classList.contains('scroll-locked');

function assertReleased(what) {
    assert.equal(locked(), false, `${what}: the page scrolls again`);
    assert.equal(AcunUI.scrollLock.count, 0, `${what}: no lock left`);
    assert.equal(AcunUI.overlays.count, 0, `${what}: the dialog left the stack`);
}

test('no Alpine on the page: the runtime alone mounts the plugins', async () => {
    await mount(page);

    assert.equal(window.Alpine, undefined);
    assert.equal(window.Livewire, undefined);
    assert.ok(AcunUI.plugins.isMounted(root('siparis'), 'modal'));
    assert.ok(AcunUI.plugins.isMounted(root('filtreler'), 'drawer'));
    assert.equal(root('siparis').hidden, true);
});

test('a trigger opens the modal: data-open, the focus inside, the page locked; the title names it', async () => {
    await mount(page);
    const panel = root('siparis').querySelector('[data-slot="modal-panel"]');

    assert.equal(byId(panel.getAttribute('aria-labelledby')).textContent, 'Sipariş #4821', 'The title got an id and names the dialog.');
    assert.equal(panel.getAttribute('aria-labelledby'), 'siparis-title');
    assert.equal(panel.getAttribute('aria-describedby'), 'siparis-description');

    byId('ac').focus();
    byId('ac').click();
    await settle();

    assert.ok(isOpen('siparis'));
    for (const part of root('siparis').querySelectorAll('[data-slot="modal-backdrop"], [data-slot="modal-panel"]')) assert.ok(part.hasAttribute('data-open'));
    assert.equal(document.activeElement, byId('siparis-x'), 'The first focusable element (the X).');
    assert.ok(locked());

    // Tab wraps around inside the panel.
    byId('siparis-vazgec').focus();
    assert.ok(press('Tab').defaultPrevented);
    assert.equal(document.activeElement, byId('siparis-x'));
});

for (const [how, close] of [
    ['the X (data-acun-close)', () => byId('siparis-x').click()],
    ['data-acun-close-now', () => byId('siparis-vazgec').click()],
    ['the backdrop', () => root('siparis').querySelector('[data-slot="modal-backdrop"]').click()],
    ['Escape', () => press('Escape')],
    ['AcunUI.modal.close(name)', () => AcunUI.modal.close('siparis')],
    ['acun:modal:close without a name', () => dispatch('acun:modal:close', {})],
]) {
    test(`${how} closes the modal, gives the page and the focus back, and the next opening locks it again`, async () => {
        await mount(page);

        for (const round of [1, 2]) {
            byId('ac').focus();
            byId('ac').click();
            await settle();
            assert.ok(isOpen('siparis'), `round ${round}: open`);

            close();
            await settle();
            assert.equal(instance('siparis').isOpen, false, `round ${round}: closed`);
            assert.equal(root('siparis').hasAttribute('data-open'), false);
            assert.equal(root('siparis').hidden, true, 'Hidden (no transition in jsdom: at once).');
            assert.equal(document.activeElement, byId('ac'), `round ${round}: the focus is back on the trigger`);
            assertReleased(`round ${round}`);
        }
    });
}

test('the API and the events: open, close, the first names, opened / closed / modal-closed', async () => {
    await mount(page);
    const seen = [];
    const listener = (event) => seen.push([event.type, event.detail.name]);
    ['acun:modal:opened', 'acun:modal:closed', 'modal-closed'].forEach((name) => window.addEventListener(name, listener));

    AcunUI.modal.open('siparis');
    await settle();
    dispatch('close-modal', { name: 'siparis' });
    await settle();
    dispatch('open-modal', { name: 'siparis' });
    await settle();
    AcunUI.plugins.get(root('siparis'), 'modal').close();
    await settle();
    ['acun:modal:opened', 'acun:modal:closed', 'modal-closed'].forEach((name) => window.removeEventListener(name, listener));

    assert.deepEqual(seen, [
        ['acun:modal:opened', 'siparis'], ['acun:modal:closed', 'siparis'], ['modal-closed', 'siparis'],
        ['acun:modal:opened', 'siparis'], ['acun:modal:closed', 'siparis'], ['modal-closed', 'siparis'],
    ]);
    assertReleased('API');
});

test('one modal at a time; one opened from inside another goes on top, Escape closes the top one', async () => {
    await mount(page);

    AcunUI.modal.open('siparis');
    await settle();
    document.activeElement.blur();
    AcunUI.modal.open('musteri');
    await settle();
    assert.equal(instance('siparis').isOpen, false, 'Opening another modal closes this one.');
    assert.equal(AcunUI.scrollLock.count, 1);

    byId('onay-ac').focus();
    byId('onay-ac').click();
    await settle();
    assert.deepEqual([instance('musteri').isOpen, instance('onay').isOpen], [true, true], 'The confirmation stacks on the customer form.');
    assert.equal(AcunUI.overlays.top(), root('onay'));

    press('Escape');
    await settle();
    assert.deepEqual([instance('musteri').isOpen, instance('onay').isOpen], [true, false]);
    assert.equal(document.activeElement, byId('onay-ac'), 'Back to the button in the form.');

    press('Escape');
    await settle();
    assertReleased('both closed');
});

test('a close guard (data-request-close-event): the X, Escape and the backdrop ask; data-acun-close-now and the API close', async () => {
    await mount(page);
    const asked = [];
    const listener = (event) => asked.push(event.target);
    window.addEventListener('sorulu-kapat', listener);

    AcunUI.modal.open('sorulu');
    await settle();
    byId('sorulu-x').click();
    press('Escape');
    root('sorulu').querySelector('[data-slot="modal-backdrop"]').click();
    await settle();
    assert.equal(instance('sorulu').isOpen, true);
    assert.deepEqual(asked, [root('sorulu'), root('sorulu'), root('sorulu')]);

    byId('sorulu-vazgec').click();
    await settle();
    assert.equal(instance('sorulu').isOpen, false);
    window.removeEventListener('sorulu-kapat', listener);
    assertReleased('guarded');
});

test('a trigger\'s aria-expanded follows its component, also on the button inside a wrapper', async () => {
    await mount(page);

    byId('filtre-dugme').click();
    await settle();
    assert.equal(byId('filtre-dugme').getAttribute('aria-expanded'), 'true');
    assert.ok(isOpen('filtreler'));

    byId('filtre-dugme').click();
    await settle();
    assert.equal(byId('filtre-dugme').getAttribute('aria-expanded'), 'false', 'data-acun-toggle closes it again.');
    assert.equal(instance('filtreler').isOpen, false);

    byId('sarili').click();
    await settle();
    assert.equal(byId('sarili').getAttribute('aria-expanded'), 'true');
    AcunUI.modal.close('musteri');
    await settle();
    assert.equal(byId('sarili').getAttribute('aria-expanded'), 'false');
});

test('the drawer: opens, takes the focus, locks the page; a followed link and the backdrop close it, a kept link does not', async () => {
    await mount(page);
    const events = [];
    const listener = (event) => events.push(event.detail);
    window.addEventListener('drawer-state-changed', listener);

    byId('filtre-dugme').focus();
    AcunUI.drawer.open('filtreler');
    await settle();
    assert.ok(isOpen('filtreler'));
    assert.equal(document.activeElement.getAttribute('aria-label'), 'Kapat');
    assert.ok(locked());

    byId('filtreler-kalsin').click();
    await settle();
    assert.ok(isOpen('filtreler'), 'A link inside [data-drawer-keep-open] keeps it open.');

    byId('filtreler-link').click();
    await settle();
    assert.equal(instance('filtreler').isOpen, false, 'A followed link closes it (a menu).');
    assertReleased('link');
    assert.equal(document.activeElement, byId('filtre-dugme'));

    AcunUI.drawer.toggle('filtreler');
    await settle();
    root('filtreler').querySelector('[data-slot="drawer-backdrop"]').click();
    await settle();
    assert.equal(instance('filtreler').isOpen, false);
    window.removeEventListener('drawer-state-changed', listener);

    assert.deepEqual(events, [{ name: 'filtreler', open: true }, { name: 'filtreler', open: false }, { name: 'filtreler', open: true }, { name: 'filtreler', open: false }]);
    assertReleased('backdrop');
});

test('data-lock-scroll="false": the page keeps scrolling while the drawer is open', async () => {
    await mount(drawerHtml('yardim', { attributes: 'data-lock-scroll="false"' }));

    AcunUI.drawer.open('yardim');
    await settle();
    assert.ok(isOpen('yardim'));
    assert.equal(locked(), false);
    AcunUI.drawer.close();
    await settle();
});

test('with a transition the root is hidden once it ends (transitionend), or after its duration at the latest', async () => {
    await mount(`<style>[data-acun-modal] [data-slot="modal-panel"] { transition-property: opacity; transition-duration: 120ms; }</style>${modalHtml('yavas')}`);

    AcunUI.modal.open('yavas');
    await settle();
    AcunUI.modal.close('yavas');
    assert.equal(root('yavas').hasAttribute('data-open'), false);
    assert.equal(root('yavas').hidden, false, 'Still shown while the panel fades out.');
    assertReleased('closing');

    // Opened again before it is hidden: it stays open.
    AcunUI.modal.open('yavas');
    await sleep(200);
    assert.ok(isOpen('yavas'));

    AcunUI.modal.close('yavas');
    root('yavas').querySelector('[data-slot="modal-panel"]').dispatchEvent(new window.Event('transitionend'));
    assert.equal(root('yavas').hidden, true, 'transitionend hides it at once.');

    // jsdom fires no transitionend: the timeout hides it.
    AcunUI.modal.open('yavas');
    AcunUI.modal.close('yavas');
    await sleep(200);
    assert.equal(root('yavas').hidden, true);
});

test('elements added later are mounted, removed ones stop (the lock goes), moved ones keep their state', async () => {
    const main = await mount('<div id="yer"></div><div id="baska-yer"></div>');

    byId('yer').insertAdjacentHTML('beforeend', modalHtml('sonradan'));
    await settle();
    AcunUI.modal.open('sonradan');
    await settle();
    assert.ok(isOpen('sonradan'), 'Added after the page loaded: the observer mounted it.');

    // Moved within the page (a morph, @persist): the same instance, still open.
    const element = root('sonradan');
    const before = AcunUI.plugins.get(element);
    byId('baska-yer').append(element);
    await settle();
    assert.equal(AcunUI.plugins.get(element), before);
    assert.ok(isOpen('sonradan'));
    assert.ok(locked());

    // Removed while open: unmounted, the page is given back.
    element.remove();
    await settle();
    assert.equal(AcunUI.plugins.isMounted(element), false);
    assertReleased('removed');
    assert.ok(main.isConnected);
});

test('AcunUI.init / AcunUI.destroy by hand, any number of times (React StrictMode, route changes)', async () => {
    await mount('<div id="kutu"></div>');
    assert.equal(AcunUI.observe(false), false, 'The observer is off.');

    try {
        byId('kutu').innerHTML = modalHtml('elle');
        await settle();
        assert.equal(AcunUI.plugins.isMounted(root('elle')), false, 'Not mounted without the observer.');

        AcunUI.init(byId('kutu'));
        AcunUI.init(byId('kutu'));
        const first = AcunUI.plugins.get(root('elle'));
        assert.ok(first);
        AcunUI.modal.open('elle');
        await settle();
        assert.ok(isOpen('elle'));

        // StrictMode: destroy and init again at once.
        AcunUI.destroy(byId('kutu'));
        assertReleased('destroyed while open');
        AcunUI.destroy(byId('kutu'));
        AcunUI.init(byId('kutu'));
        assert.notEqual(AcunUI.plugins.get(root('elle')), first, 'A new instance.');
        AcunUI.modal.open('elle');
        await settle();
        assert.ok(isOpen('elle'));
        AcunUI.destroy(byId('kutu'));
        byId('kutu').remove();
        assertReleased('route change');
    } finally {
        assert.equal(AcunUI.observe(true), true);
    }
});

test('a close button outside any component says so in the console instead of throwing', async () => {
    await mount('<button type="button" id="yalniz" data-acun-close>Kapat</button><button type="button" id="isimli" data-acun-open="yok">Aç</button>');
    const messages = [];
    const { error, warn } = console;
    console.error = (...args) => messages.push(String(args[0]));
    console.warn = (...args) => messages.push(String(args[0]));

    try {
        byId('yalniz').click();
        byId('isimli').click();
    } finally {
        console.error = error;
        console.warn = warn;
    }

    assert.deepEqual(messages, ['[Acun UI] data-acun-close is not inside a component it can close.', '[Acun UI] Nothing named "yok" to open on this page.']);
});

// A modal <dialog> the page opens inside a modal (its own confirmation): Escape is the browser's, it
// closes the native dialog (the cancel event), not the modal around it.
test('Escape inside a native modal <dialog> in a modal leaves the modal open', async () => {
    await mount(modalHtml('icteki', { body: '<dialog id="yerli"><button id="yerli-tamam">Tamam</button></dialog>' }));
    AcunUI.modal.open('icteki');
    await settle();
    const native = byId('yerli');
    native.setAttribute('open', '');
    byId('yerli-tamam').focus();

    const escape = press('Escape');
    await settle();
    assert.equal(escape.defaultPrevented, false, "the browser's own close of the <dialog> is not cancelled");
    assert.equal(isOpen('icteki'), true, 'the modal stays open');

    native.removeAttribute('open');
    byId('icteki-x').focus();
    press('Escape');
    await sleep(250);
    assert.equal(isOpen('icteki'), false, 'without the native dialog, Escape closes the modal');
});

// The opener of a menu item hides with the menu, which gives the focus to its own button (a dropdown
// item with data-acun-open): after the dialog, the focus goes to that visible button, not to <body>.
test('when the opener is hidden by the time the dialog closes, the focus goes to the element focused at opening', async () => {
    await mount(`
        <button type="button" id="menu-dugme">İşlemler</button>
        <div id="menu"><button type="button" id="menu-oge" data-acun-open="menuden">Düzenle</button></div>
        ${modalHtml('menuden', { title: 'Düzenle' })}`);
    // The menu closes on the item's click (before the document's trigger handler) and focuses its button.
    byId('menu-oge').addEventListener('click', () => {
        byId('menu').hidden = true;
        byId('menu-dugme').focus();
    });

    byId('menu-oge').focus();
    byId('menu-oge').click();
    await settle();
    assert.equal(isOpen('menuden'), true);

    press('Escape');
    await sleep(250);
    assert.equal(isOpen('menuden'), false);
    assert.equal(document.activeElement, byId('menu-dugme'), 'the menu button, not <body>');
});

test('a hidden opener without a visible fallback: the focus does not stay inside the closed dialog', async () => {
    await mount(`
        <button type="button" id="tek-ac" data-acun-open="tek">Aç</button>
        ${modalHtml('tek', { title: 'Tek' })}`);
    byId('tek-ac').focus();
    byId('tek-ac').click();
    await settle();
    byId('tek-ac').hidden = true;

    press('Escape');
    await sleep(250);
    assert.equal(root('tek').contains(document.activeElement), false);
});

test('a modal removed while open: its trigger is collapsed again and the closed event is sent', async () => {
    await mount(`<button type="button" id="sil-ac" data-acun-open="silinen" aria-expanded="false">Aç</button>${modalHtml('silinen')}`);
    byId('sil-ac').click();
    await settle();
    assert.equal(byId('sil-ac').getAttribute('aria-expanded'), 'true');

    const closed = [];
    window.addEventListener('acun:modal:closed', (event) => closed.push(event.detail.name), { once: true });
    root('silinen').remove();
    await settle();
    assert.equal(byId('sil-ac').getAttribute('aria-expanded'), 'false');
    assert.deepEqual(closed, ['silinen']);
    assertReleased('removed while open');
});

test('a link trigger opens the component without leaving the page; a click with a modifier key is the link\'s', async () => {
    await mount(`<a id="baglanti" href="/siparisler/4821" data-acun-open="baglantili">Sipariş</a>${modalHtml('baglantili')}`);

    const plain = new window.MouseEvent('click', { bubbles: true, cancelable: true });
    byId('baglanti').dispatchEvent(plain);
    await settle();
    assert.equal(plain.defaultPrevented, true, 'the page stays');
    assert.equal(isOpen('baglantili'), true);
    AcunUI.modal.close('baglantili');
    await sleep(250);

    const newTab = new window.MouseEvent('click', { bubbles: true, cancelable: true, ctrlKey: true });
    byId('baglanti').dispatchEvent(newTab);
    await settle();
    assert.equal(newTab.defaultPrevented, false);
    assert.equal(isOpen('baglantili'), false, 'Ctrl+click opens the link in a new tab, not the dialog');
});

// Lazy components without Alpine (core/components.js, watchLazyComponents): the module of an element on the page, or
// of one added later, is imported once; a component defined after its element is on the page loads at once.
test('without Alpine a lazy component\'s module loads once, for elements on the page and added later', async () => {
    let imports = 0;
    AcunUI.components.define('duz-sayac', async () => { imports++; });
    await mount('<div data-acun-component="duz-sayac">Sayaç</div>');
    await settle();
    assert.equal(imports, 1);
    assert.equal(AcunUI.components.isLoaded('duz-sayac'), true);

    document.body.insertAdjacentHTML('beforeend', '<div data-acun-component="duz-sayac">İkinci</div>');
    await settle();
    assert.equal(imports, 1, 'once, however many elements');

    let late = 0;
    document.body.insertAdjacentHTML('beforeend', '<div data-acun-component="gec-tanimli">Geç</div>');
    await settle();
    AcunUI.components.define('gec-tanimli', async () => { late++; });
    await settle();
    assert.equal(late, 1, 'defined after its element: loads at once');
});
