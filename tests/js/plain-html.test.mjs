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

// --- the dropdown (js/plugins/dropdown.js) without Alpine -------------------------------------------------

const dropdownHtml = `
    <button type="button" id="disarida">Dışarıda</button>
    <div data-acun-dropdown data-slot="dropdown" class="relative inline-block">
        <div data-slot="dropdown-trigger"><button type="button" id="islemler" aria-expanded="false">İşlemler</button></div>
        <div data-slot="dropdown-content" data-side="bottom" hidden>
            <button type="button" id="duzenle">Düzenle</button>
            <button type="button" disabled>Kopyala</button>
            <a href="#sil" id="sil">Sil</a>
        </div>
    </div>`;
const menu = () => document.querySelector('[data-slot="dropdown-content"]');
const menuOpen = () => !menu().hidden && menu().hasAttribute('data-open') && byId('islemler').getAttribute('aria-expanded') === 'true';

test('the dropdown opens, moves between its items and closes with the keyboard, without Alpine', async () => {
    await mount(dropdownHtml);
    assert.equal(byId('islemler').getAttribute('aria-controls'), menu().id, 'the runtime gives the menu an id');
    assert.ok(menu().id);

    byId('islemler').focus();
    press('ArrowDown', {}, byId('islemler'));
    await settle();
    assert.ok(menuOpen());
    assert.equal(document.activeElement?.id, 'duzenle', 'Arrow Down opens it on the first item');

    press('ArrowDown');
    assert.equal(document.activeElement?.id, 'sil', 'the disabled button is skipped');
    press('Home');
    assert.equal(document.activeElement?.id, 'duzenle');
    press('End');
    assert.equal(document.activeElement?.id, 'sil');

    const escape = press('Escape');
    await sleep(150);
    assert.equal(menuOpen(), false);
    assert.equal(menu().hidden, true);
    assert.equal(document.activeElement, byId('islemler'), 'Escape gives the focus back to the trigger');
    assert.equal(escape.defaultPrevented, false);
});

test('the dropdown toggles on its trigger, closes on an item and on a click outside', async () => {
    await mount(dropdownHtml);
    const events = [];
    window.addEventListener('acun:dropdown:closed', (event) => events.push(event.detail.reason));

    byId('islemler').click();
    await settle();
    assert.ok(menuOpen());
    byId('islemler').click();
    await sleep(150);
    assert.equal(menuOpen(), false, 'a second click closes it');

    byId('islemler').click();
    await settle();
    byId('duzenle').focus();
    byId('duzenle').click();
    await sleep(150);
    assert.equal(menuOpen(), false, 'a click on an item closes it');
    assert.equal(document.activeElement, byId('islemler'), 'the focus goes back to the trigger');

    byId('islemler').click();
    await settle();
    byId('disarida').click();
    await sleep(150);
    assert.equal(menuOpen(), false, 'a click outside closes it');
    assert.deepEqual(events, ['user', 'user', 'user']);
});

test('the floating panel flips to the other side when the window has no room below, and back', async () => {
    const { place } = await import('../../js/behavior/floating.js');
    const panel = document.createElement('div');
    const anchor = document.createElement('button');
    const rect = (top, height, left = 100, width = 200) => ({ top, bottom: top + height, left, right: left + width, width, height, x: left, y: top });
    window.innerHeight = 600;
    window.innerWidth = 1000;
    panel.getBoundingClientRect = () => rect(panel.dataset.side === 'top' ? 330 : 580, 200);

    anchor.getBoundingClientRect = () => rect(540, 36);
    place(panel, anchor, 'bottom');
    assert.equal(panel.dataset.side, 'top', '200px below a trigger 24px from the bottom: it opens above');

    anchor.getBoundingClientRect = () => rect(100, 36);
    place(panel, anchor, 'bottom');
    assert.equal(panel.dataset.side, 'bottom', 'room below again: its own side');

    panel.getBoundingClientRect = () => rect(150, 200, 900, 200);
    place(panel, anchor, 'bottom');
    assert.equal(panel.style.translate, '-108px 0', 'past the right edge: shifted back inside');
});

// --- the popover (js/plugins/popover.js) without Alpine ---------------------------------------------------

test('the popover opens on its trigger, stays open on a click inside, closes with Escape or a click outside', async () => {
    await mount(`
        <button type="button" id="baska">Başka</button>
        <div data-acun-popover data-slot="popover" class="relative inline-block">
            <div data-slot="popover-trigger"><button type="button" id="bilgi" aria-haspopup="dialog" aria-expanded="false">Bilgi</button></div>
            <div role="dialog" data-slot="popover-content" data-side="bottom" hidden>
                <div data-slot="popover-title">İade kuralı</div>
                <div><a href="#kosullar" id="kosullar">Koşullar</a> 14 gün içinde iade.</div>
            </div>
        </div>`);
    const bubble = document.querySelector('[data-slot="popover-content"]');
    const isShown = () => !bubble.hidden && bubble.hasAttribute('data-open') && byId('bilgi').getAttribute('aria-expanded') === 'true';
    assert.equal(byId('bilgi').getAttribute('aria-controls'), bubble.id);
    assert.equal(bubble.getAttribute('aria-labelledby'), bubble.querySelector('[data-slot="popover-title"]').id, 'the title names the dialog');

    byId('bilgi').click();
    await settle();
    assert.ok(isShown());
    byId('kosullar').focus();
    bubble.querySelector('div:last-child').click();
    await settle();
    assert.ok(isShown(), 'a click inside keeps it open');

    press('Escape');
    await sleep(150);
    assert.equal(isShown(), false);
    assert.equal(document.activeElement, byId('bilgi'));

    byId('bilgi').click();
    await settle();
    byId('baska').click();
    await sleep(150);
    assert.equal(isShown(), false, 'a click outside closes it');
});

// --- the tooltip (js/plugins/tooltip.js) without Alpine ---------------------------------------------------

test('the tooltip describes its control, shows on focus and hover, and Escape hides it', async () => {
    await mount(`
        <span data-acun-tooltip class="relative">
            <button type="button" id="sil-dugme">x</button>
            <span role="tooltip" data-slot="tooltip-content" data-position="top" popover="manual" hidden>Siparişi sil</span>
        </span>`);
    const tip = document.querySelector('[role="tooltip"]');
    const visible = () => !tip.hidden && tip.hasAttribute('data-open');
    assert.equal(byId('sil-dugme').getAttribute('aria-describedby'), tip.id);

    byId('sil-dugme').focus();
    await settle();
    assert.ok(visible(), 'shown on keyboard focus');

    const escape = press('Escape');
    await sleep(150);
    assert.equal(visible(), false, 'Escape hides it');
    assert.equal(escape.defaultPrevented, false);

    const root = document.querySelector('[data-acun-tooltip]');
    root.dispatchEvent(new window.MouseEvent('mouseenter'));
    await settle();
    assert.ok(visible(), 'shown on hover');
    root.dispatchEvent(new window.MouseEvent('mouseleave'));
    await sleep(60);
    assert.ok(visible(), 'kept for a moment: the pointer may be on its way to the bubble');
    await sleep(250);
    assert.equal(visible(), false);
});

// --- tabs, accordion and collapse without Alpine -----------------------------------------------------------

test('tabs open the first enabled tab, switch on click and with the arrow keys, and keep one tab in the Tab order', async () => {
    await mount(`
        <div data-acun-tabs data-slot="tabs">
            <div role="tablist" data-slot="tabs-list">
                <button type="button" role="tab" data-value="ozet" aria-selected="false" tabindex="-1" disabled>Özet</button>
                <button type="button" role="tab" data-value="kargo" aria-selected="false" tabindex="-1">Kargo</button>
                <button type="button" role="tab" data-value="fatura" aria-selected="false" tabindex="-1">Fatura</button>
            </div>
            <div role="tabpanel" data-value="ozet" hidden>Ö</div>
            <div role="tabpanel" data-value="kargo" hidden>K</div>
            <div role="tabpanel" data-value="fatura" hidden>F</div>
        </div>`);
    const tab = (value) => document.querySelector(`[role="tab"][data-value="${value}"]`);
    const panel = (value) => document.querySelector(`[role="tabpanel"][data-value="${value}"]`);
    const changes = [];
    window.addEventListener('acun:tabs:change', (event) => changes.push(event.detail.value));

    assert.equal(tab('kargo').getAttribute('aria-selected'), 'true', 'the first enabled tab opens');
    assert.equal(panel('kargo').hidden, false);
    assert.equal(panel('ozet').hidden, true);
    assert.equal(tab('kargo').getAttribute('aria-controls'), panel('kargo').id);
    assert.equal(panel('kargo').getAttribute('aria-labelledby'), tab('kargo').id);

    tab('fatura').click();
    assert.equal(panel('fatura').hidden, false);
    assert.equal(panel('kargo').hidden, true);
    assert.deepEqual([...document.querySelectorAll('[role="tab"]')].map((element) => element.tabIndex), [-1, -1, 0]);

    tab('fatura').focus();
    press('ArrowRight');
    assert.equal(document.activeElement, tab('kargo'), 'wraps round, skipping the disabled tab');
    assert.equal(panel('kargo').hidden, false);
    press('End');
    assert.equal(document.activeElement, tab('fatura'));
    assert.deepEqual(changes, ['fatura', 'kargo', 'fatura']);
});

test('the accordion opens one section at a time, renders lazy content once and animates in CSS', async () => {
    await mount(`
        <div data-acun-accordion data-multiple="false">
            <div data-slot="accordion-item" data-name="genel">
                <h3><button type="button" data-slot="accordion-trigger" aria-expanded="true">Genel</button></h3>
                <div data-slot="accordion-content" role="region" data-open><div>Bir</div></div>
            </div>
            <div data-slot="accordion-item" data-name="adres">
                <h3><button type="button" data-slot="accordion-trigger" aria-expanded="false">Adres</button></h3>
                <div data-slot="accordion-content" role="region" hidden><div><template data-acun-lazy><p id="agir">Ağır</p></template></div></div>
            </div>
        </div>`);
    const trigger = (name) => document.querySelector(`[data-name="${name}"] [data-slot="accordion-trigger"]`);
    const content = (name) => document.querySelector(`[data-name="${name}"] [data-slot="accordion-content"]`);

    assert.equal(trigger('genel').getAttribute('aria-controls'), content('genel').id);
    assert.equal(content('genel').getAttribute('aria-labelledby'), trigger('genel').id);
    assert.equal(byId('agir'), null, 'lazy content waits');

    trigger('adres').click();
    await sleep(250);
    assert.equal(trigger('adres').getAttribute('aria-expanded'), 'true');
    assert.equal(content('adres').hidden, false);
    assert.ok(byId('agir'), 'rendered on the first opening');
    assert.equal(trigger('genel').getAttribute('aria-expanded'), 'false', 'one open at a time');
    assert.equal(content('genel').hidden, true);
});

test('acun:collapse opens and closes its area', async () => {
    await mount(`
        <div data-acun-collapse>
            <button type="button" data-slot="collapse-trigger" aria-expanded="false" id="daha">Daha</button>
            <div data-slot="collapse-content" hidden><div><p>Gizli</p></div></div>
        </div>`);
    const area = document.querySelector('[data-slot="collapse-content"]');
    assert.equal(byId('daha').getAttribute('aria-controls'), area.id);

    byId('daha').click();
    await settle();
    assert.equal(byId('daha').getAttribute('aria-expanded'), 'true');
    assert.equal(area.hidden, false);
    assert.ok(area.hasAttribute('data-open'));

    byId('daha').click();
    await sleep(250);
    assert.equal(area.hidden, true);
});

// --- toast, copy and inline-confirm without Alpine ----------------------------------------------------------

test('a toast event shows a toast from the template, with its type, a safe link and a close button', async () => {
    await mount(`
        <div data-acun-toasts aria-live="polite" data-type-classes='{"success":"bg-success","error":"bg-danger","warning":"bg-warning","info":"bg-inverse"}' data-flashed='[{"type":"success","message":"Kaydedildi"}]'>
            <template data-slot="toast-template"><div data-slot="toast"><div><p data-slot="toast-message"></p><a data-slot="toast-link" hidden></a></div><button type="button" data-slot="toast-close" aria-label="Kapat">×</button></div></template>
        </div>`);
    const toasts = () => [...document.querySelectorAll('[data-slot="toast"]')].filter((toast) => !toast.closest('template'));
    assert.equal(toasts().length, 1, 'the flashed toast');
    assert.equal(toasts()[0].getAttribute('role'), 'status');
    assert.ok(toasts()[0].classList.contains('bg-success'));

    dispatch('toast', { type: 'Danger', message: 'Silinemedi', link: 'javascript:alert(1)', linkLabel: 'Aç' });
    dispatch('toast', { message: 'Rapor hazır', link: '/raporlar/5.xlsx', linkLabel: 'İndir', download: true });
    await settle();
    const [, error, report] = toasts();
    assert.equal(error.getAttribute('role'), 'alert');
    assert.ok(error.classList.contains('bg-danger'));
    assert.equal(error.querySelector('a'), null, 'an unsafe link is dropped');
    assert.equal(report.querySelector('a').getAttribute('href'), '/raporlar/5.xlsx');
    assert.equal(report.querySelector('a').getAttribute('download'), '');

    error.querySelector('[data-slot="toast-close"]').click();
    await sleep(250);
    assert.equal(toasts().length, 2);
});

test('the copy button copies its text and says so, without Alpine', async () => {
    await mount(`
        <span class="contents" data-acun-copy data-state="idle" data-timeout="2000" data-copied-label="Kopyalandı" data-failed-label="Kopyalanamadı">
            <button type="button" data-copy-text="TR33 0006" id="kopyala">Kopyala</button>
            <span class="sr-only" aria-live="polite" data-slot="copy-status"></span>
        </span>`);
    const written = [];
    Object.defineProperty(window, 'isSecureContext', { configurable: true, value: true });
    Object.defineProperty(window.navigator, 'clipboard', { configurable: true, value: { writeText: async (text) => { written.push(text); } } });
    const copied = [];
    window.addEventListener('copied', (event) => copied.push(event.detail.text), { once: true });

    byId('kopyala').click();
    await settle();
    assert.deepEqual(written, ['TR33 0006']);
    assert.equal(document.querySelector('[data-acun-copy]').dataset.state, 'copied');
    assert.equal(document.querySelector('[data-slot="copy-status"]').textContent, 'Kopyalandı');
    assert.deepEqual(copied, ['TR33 0006']);
});

test('inline confirm asks in place, runs only on confirm and keeps the focus on the page', async () => {
    await mount(`
        <span data-acun-inline-confirm>
            <button type="button" data-slot="inline-confirm-ask" id="sor">Sil</button>
            <span data-slot="inline-confirm-question" hidden role="group" aria-label="Silinsin mi?">
                <button type="button" data-slot="inline-confirm-confirm" id="evet-sil">Sil</button>
                <button type="button" data-slot="inline-confirm-cancel" id="vazgec">Vazgeç</button>
            </span>
        </span>`);
    const question = document.querySelector('[data-slot="inline-confirm-question"]');
    let deleted = 0;
    byId('evet-sil').addEventListener('click', () => deleted++);

    byId('sor').click();
    assert.equal(question.hidden, false);
    assert.equal(byId('sor').hidden, true);
    assert.equal(document.activeElement, byId('evet-sil'));

    const escape = press('Escape');
    assert.equal(question.hidden, true);
    assert.equal(document.activeElement, byId('sor'), 'Escape gives the focus back');
    assert.equal(escape.defaultPrevented, false);

    byId('sor').click();
    byId('evet-sil').click();
    await settle();
    assert.equal(deleted, 1);
    assert.equal(question.hidden, true);
    assert.equal(document.activeElement, byId('sor'), 'after the action the focus is on the button again, not on <body>');
});

// --- the small components (stage 2.5) without Alpine ---------------------------------------------------------

test('a dismissible alert and a closable card hide with their data-acun-close button; a card folds its body', async () => {
    await mount(`
        <div data-acun-dismissible id="uyari">Uyarı <button type="button" data-acun-close id="uyari-kapat">×</button></div>
        <div data-acun-card id="kart">
            <div data-slot="card-header"><button type="button" data-slot="card-toggle" aria-expanded="true" id="katla">⌃</button><button type="button" data-acun-close id="kart-kapat">×</button></div>
            <div data-slot="card-body" data-open><div>İçerik</div></div>
        </div>`);
    byId('uyari-kapat').click();
    assert.equal(byId('uyari').hidden, true);

    const body = document.querySelector('[data-slot="card-body"]');
    assert.equal(byId('katla').getAttribute('aria-controls'), body.id);
    byId('katla').click();
    await sleep(250);
    assert.equal(byId('katla').getAttribute('aria-expanded'), 'false');
    assert.equal(body.hidden, true);
    byId('katla').click();
    await settle();
    assert.equal(body.hidden, false);

    byId('kart-kapat').click();
    assert.equal(byId('kart').hidden, true);
});

test('the password field shows and hides its value; the search box clears itself and answers its shortcut', async () => {
    await mount(`
        <div class="relative" data-acun-reveal><input type="password" id="sifre" value="gizli"><button type="button" data-slot="reveal-toggle" aria-pressed="false" id="goster">👁</button></div>
        <div role="search" data-acun-search data-shortcut="/"><input type="search" id="ara" value="kulaklık"><button type="button" data-slot="search-clear" id="temizle">×</button></div>
        <button type="button" id="baska-yer">Başka</button>`);
    byId('goster').click();
    assert.equal(byId('sifre').type, 'text');
    assert.equal(byId('goster').getAttribute('aria-pressed'), 'true');
    byId('goster').click();
    assert.equal(byId('sifre').type, 'password');

    const inputs = [];
    byId('ara').addEventListener('input', () => inputs.push(byId('ara').value));
    byId('temizle').click();
    assert.equal(byId('ara').value, '');
    assert.deepEqual(inputs, [''], 'Livewire hears the change');
    assert.equal(document.activeElement, byId('ara'));

    byId('baska-yer').focus();
    const slash = press('/', {}, byId('baska-yer'));
    assert.equal(slash.defaultPrevented, true);
    assert.equal(document.activeElement, byId('ara'), 'the shortcut focuses the search');
});

// --- accessibility fixes (2026-10-27) ------------------------------------------------------------------------

test('a dismissed alert with the focus inside gives the focus to the next control, not to <body>', async () => {
    await mount(`
        <button type="button" id="once">Önce</button>
        <div data-acun-dismissible id="uyari2">Uyarı <button type="button" data-acun-close id="kapat2">×</button></div>
        <button type="button" id="sonra">Sonra</button>`);
    byId('kapat2').focus();
    byId('kapat2').click();
    assert.equal(byId('uyari2').hidden, true);
    assert.equal(document.activeElement, byId('sonra'));
});

test('a table box that scrolls sideways becomes a named, focusable region; one that fits does not', async () => {
    await mount(`
        <div class="ui-table-box" data-acun-table-scroll data-scroll-label="Tablo" id="genis"><table><caption>Siparişler</caption><tr><td>…</td></tr></table></div>
        <div class="ui-table-box" data-acun-table-scroll data-scroll-label="Tablo" id="dar"><table><tr><td>…</td></tr></table></div>`);
    const wide = byId('genis');
    Object.defineProperty(wide, 'scrollWidth', { configurable: true, value: 900 });
    Object.defineProperty(wide, 'clientWidth', { configurable: true, value: 300 });
    AcunUI.destroy(wide);
    AcunUI.init(wide);
    assert.equal(wide.getAttribute('tabindex'), '0');
    assert.equal(wide.getAttribute('role'), 'region');
    assert.equal(wide.getAttribute('aria-label'), 'Siparişler', 'named by its caption');
    assert.equal(byId('dar').hasAttribute('tabindex'), false, 'a box that fits stays out of the Tab order');
});

test('a toast with a link stays until it is closed; one without goes on its timer', async () => {
    await mount(`
        <div data-acun-toasts aria-live="polite" data-type-classes='{}'>
            <template data-slot="toast-template"><div data-slot="toast"><p data-slot="toast-message"></p><a data-slot="toast-link" hidden></a><button type="button" data-slot="toast-close" aria-label="Kapat">×</button></div></template>
        </div>`);
    const container = AcunUI.plugins.get(document.querySelector('[data-acun-toasts]'), 'toasts');
    dispatch('toast', { message: 'Rapor hazır', link: '/rapor.pdf', linkLabel: 'İndir' });
    dispatch('toast', { message: 'Kaydedildi' });
    const [withLink, plain] = container.toasts;
    assert.equal(container.timers.has(withLink.id), false, 'no timer: it waits to be closed');
    assert.ok(container.timers.get(plain.id).handle, 'the plain toast runs its timer');
    container.dismiss(withLink.id);
    container.dismiss(plain.id);
});
