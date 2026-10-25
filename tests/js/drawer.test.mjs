import test from 'node:test';
import assert from 'node:assert/strict';
import { Alpine, dispatch, document, mount, press, render, settle, sleep, viewport, window } from './browser.mjs';

// acun:drawer in the browser, on a page with Livewire's Alpine (as in a Laravel app): its parts,
// focus (initial, trap, restore), the page scroll lock, Escape, the backdrop, the events and the
// wire:model / x-model binding. The behaviour is the drawer plugin (js/plugins/drawer.js); its
// state shows on the markup (hidden, data-open). Without Alpine: plain-html.test.mjs.

const html = render({
    parts: `
        <acun:drawer.trigger name="detay"><acun:button id="opener">Detay</acun:button></acun:drawer.trigger>
        <button id="outside">Dışarıda</button>
        <acun:drawer name="detay">
            <acun:drawer.header title="Sipariş #4821" description="Ayşe Yılmaz" />
            <acun:drawer.body>
                <input id="first" type="text">
                <span x-data="{ shown: false }"><button id="hidden" x-show="shown">Gizli</button></span>
                <a id="link" href="#kargo">Kargo</a>
                <acun:dropdown>
                    <acun:dropdown.trigger><acun:button id="menu" variant="white">İşlemler</acun:button></acun:dropdown.trigger>
                    <acun:dropdown.content><acun:dropdown.item id="edit">Düzenle</acun:dropdown.item></acun:dropdown.content>
                </acun:dropdown>
                <p id="text">Metin</p>
            </acun:drawer.body>
            <acun:drawer.footer>
                <acun:drawer.close><acun:button id="cancel" variant="white">Vazgeç</acun:button></acun:drawer.close>
                <acun:button id="save">Kaydet</acun:button>
            </acun:drawer.footer>
        </acun:drawer>`,
    autofocus: `
        <acun:drawer name="not" title="Not"><input id="subject"><textarea id="note" autofocus></textarea></acun:drawer>`,
    textOnly: `
        <acun:drawer name="bilgi" aria-label="Bilgi"><p>Yalnız metin</p></acun:drawer>`,
    options: `
        <acun:drawer name="form" title="Form" :closable-on-backdrop="false">
            <input id="field">
            <acun:popover>
                <acun:popover.trigger><acun:button id="rule" variant="white">Kural</acun:button></acun:popover.trigger>
                <acun:popover.content title="İade kuralı">14 gün içinde iade.</acun:popover.content>
            </acun:popover>
        </acun:drawer>
        <acun:drawer name="yardim" title="Yardım" :backdrop="false" :lock-scroll="false">Yardım metni</acun:drawer>`,
    bound: `
        <div id="host" x-data="{
            show: false,
            sets: 0,
            // What wire:model hands the drawer: a getter and a setter of the Livewire property.
            get wire() { return { get: () => this.show, set: (value) => { this.show = value; this.sets++; } }; },
        }">
            <acun:drawer name="bagli" title="Bağlı" x-model="wire"><input id="bound-field"></acun:drawer>
        </div>`,
    alpine: `
        <div id="alpine-host" x-data="{ open: true }">
            <acun:drawer name="alpine" title="Alpine" x-model="open">İçerik</acun:drawer>
        </div>`,
    legacy: `
        <acun:drawer name="eski" title="Eski">
            <span id="eski-durum" x-text="opened ? 'açık' : 'kapalı'"></span>
            <button id="eski-kapat" x-on:click="close()">Kapat</button>
        </acun:drawer>`,
    docked: `
        <div id="docked-host" x-data="{ filters: false }">
            <acun:drawer.trigger name="filtre" toggle><acun:button id="filter-button">Filtreler</acun:button></acun:drawer.trigger>
            <acun:drawer name="filtre" docked title="Filtreler" x-model="filters">
                <input id="filter-field">
                <acun:slot:footer><acun:button id="apply">Uygula</acun:button></acun:slot:footer>
            </acun:drawer>
        </div>`,
});

const byId = (id) => document.getElementById(id);
const drawer = (name) => window.AcunUI.plugins.get(byId(name), 'drawer');
const isOpen = (name) => drawer(name).isOpen && !byId(name).hidden && byId(name).hasAttribute('data-open');
const panelOf = (name) => byId(name).querySelector('[data-slot="drawer-panel"]');
const locks = () => window.AcunUI.scrollLock.count;
const closed = () => sleep(250);

async function open(name, from = 'outside') {
    byId(from)?.focus();
    dispatch('open-drawer', { name });
    await settle();
}

// --- parts ---------------------------------------------------------------------

test('the trigger opens the drawer and its button follows the open state', async () => {
    await mount(html.parts);
    const button = byId('opener');

    assert.equal(button.getAttribute('aria-haspopup'), 'dialog');
    assert.equal(button.getAttribute('aria-expanded'), 'false');

    button.focus();
    button.click();
    await settle();

    assert.ok(isOpen('detay'));
    assert.equal(button.getAttribute('aria-expanded'), 'true');

    press('Escape');
    await settle();
    // Already while the panel slides out.
    assert.equal(button.getAttribute('aria-expanded'), 'false');
});

test('the header names the dialog and its X closes it', async () => {
    await mount(html.parts);
    const dialog = byId('detay');

    assert.equal(dialog.getAttribute('role'), 'dialog');
    assert.equal(dialog.getAttribute('aria-modal'), 'true');
    assert.equal(byId(dialog.getAttribute('aria-labelledby')).textContent.trim(), 'Sipariş #4821');
    assert.equal(byId(dialog.getAttribute('aria-describedby')).textContent.trim(), 'Ayşe Yılmaz');

    await open('detay');
    dialog.querySelector('[data-slot="drawer-header"] button[aria-label]').click();
    await settle();

    assert.equal(drawer('detay').isOpen, false);
    assert.equal(dialog.hasAttribute('data-open'), false, 'data-open goes at once (the slide out starts).');
    await closed();
    assert.equal(dialog.hidden, true, 'Hidden once the panel has slid out.');
});

test('drawer.close closes it from the footer', async () => {
    await mount(html.parts);
    await open('detay');

    byId('cancel').click();
    await settle();

    assert.equal(drawer('detay').isOpen, false);
});

// --- focus ---------------------------------------------------------------------

test('opening moves the focus to the first focusable element', async () => {
    await mount(html.parts);
    await open('detay');

    assert.equal(document.activeElement.getAttribute('aria-label'), 'Kapat', 'The header X comes first.');
});

test('an autofocus element takes the focus first', async () => {
    await mount(html.autofocus);
    await open('not');

    assert.equal(document.activeElement, byId('note'));
});

test('a drawer without focusable content focuses the panel', async () => {
    await mount(html.textOnly);
    await open('bilgi');

    assert.equal(document.activeElement, panelOf('bilgi'));

    const tab = press('Tab');
    assert.ok(tab.defaultPrevented, 'Tab stays in the panel.');
    assert.equal(document.activeElement, panelOf('bilgi'));
});

test('Tab wraps around inside the panel and skips hidden elements', async () => {
    await mount(html.parts);
    await open('detay');
    const first = document.activeElement;

    byId('save').focus();
    assert.ok(press('Tab').defaultPrevented);
    assert.equal(document.activeElement, first, 'From the last element to the first.');

    assert.ok(press('Tab', { shiftKey: true }).defaultPrevented);
    assert.equal(document.activeElement, byId('save'), 'From the first element to the last.');

    const order = drawer('detay').focusables().map((element) => element.id || element.getAttribute('aria-label'));
    assert.deepEqual(order, ['Kapat', 'first', 'link', 'menu', 'cancel', 'save'], 'The hidden button and the closed menu\'s items are skipped.');

    byId('first').focus();
    assert.equal(press('Tab').defaultPrevented, false, 'In the middle the browser moves the focus.');
});

test('closing gives the focus back to the element that opened it', async () => {
    await mount(html.parts);
    byId('opener').focus();
    byId('opener').click();
    await settle();
    assert.notEqual(document.activeElement, byId('opener'));

    press('Escape');
    await settle();

    assert.equal(document.activeElement, byId('opener'));
});

test('a removed trigger does not break closing', async () => {
    await mount(html.parts);
    await open('detay');
    byId('outside').remove();

    drawer('detay').close();
    await settle();

    assert.equal(drawer('detay').isOpen, false);
});

test('opening twice keeps the first trigger to return to', async () => {
    await mount(html.parts);
    await open('detay', 'opener');
    dispatch('open-drawer', { name: 'detay' });
    await settle();

    press('Escape');
    await settle();

    assert.equal(document.activeElement, byId('opener'));
    assert.equal(locks(), 0);
});

// --- scroll lock ---------------------------------------------------------------

test('an open drawer locks the page scroll and closing releases it', async () => {
    await mount(html.parts);
    await open('detay');
    assert.equal(locks(), 1);

    press('Escape');
    await settle();
    assert.equal(locks(), 0);
});

test('lock-scroll false leaves the page scrolling', async () => {
    await mount(html.options);
    await open('yardim');

    assert.ok(isOpen('yardim'));
    assert.equal(locks(), 0);
});

test('two open drawers hold the lock until both close', async () => {
    await mount(html.parts + html.autofocus);
    await open('detay');
    await open('not');
    assert.equal(locks(), 2);

    dispatch('close-drawer');
    await settle();

    assert.equal(drawer('detay').isOpen, false);
    assert.equal(drawer('not').isOpen, false);
    assert.equal(locks(), 0);
});

// --- Escape ----------------------------------------------------------------------

test('Escape inside the panel closes it', async () => {
    await mount(html.parts);
    await open('detay');
    byId('first').focus();

    press('Escape');
    await settle();

    assert.equal(drawer('detay').isOpen, false);
});

test('Escape with the focus nowhere closes it', async () => {
    await mount(html.parts);
    await open('detay');
    document.activeElement.blur();

    press('Escape', {}, document.body);
    await settle();

    assert.equal(drawer('detay').isOpen, false);
});

test('Escape in a window opened over the panel leaves the panel open', async () => {
    await mount(html.parts);
    await open('detay');
    byId('outside').focus();

    press('Escape');
    await settle();

    assert.ok(isOpen('detay'));
});

test('Escape in an open menu inside the panel closes only the menu', async () => {
    await mount(html.parts);
    await open('detay');
    byId('menu').click();
    await settle();
    byId('edit').focus();
    const menu = Alpine.$data(byId('menu').closest('[data-slot="dropdown"]'));
    assert.ok(menu.open);

    press('Escape');
    await settle();

    assert.equal(menu.open, false);
    assert.ok(isOpen('detay'));
    assert.equal(document.activeElement, byId('menu'), 'The menu gives the focus back to its button.');

    press('Escape');
    await settle();
    assert.equal(drawer('detay').isOpen, false, 'The next Escape closes the panel.');
});

test('Escape in an open popover inside the panel closes only the popover', async () => {
    await mount(html.options);
    await open('form');
    byId('rule').click();
    await settle();
    const popover = Alpine.$data(byId('rule').closest('[data-slot="popover"]'));
    assert.ok(popover.open);

    press('Escape', {}, byId('rule'));
    await settle();

    assert.equal(popover.open, false);
    assert.ok(isOpen('form'));
});

// --- backdrop and clicks --------------------------------------------------------

const backdrop = (name) => byId(name).querySelector(':scope > [aria-hidden="true"]');

test('a click on the backdrop closes it', async () => {
    await mount(html.parts);
    await open('detay');

    backdrop('detay').click();
    await settle();

    assert.equal(drawer('detay').isOpen, false);
});

test('closable-on-backdrop false keeps it open on a backdrop click', async () => {
    await mount(html.options);
    await open('form');

    backdrop('form').click();
    await settle();

    assert.ok(isOpen('form'));
});

test('a click inside the panel keeps it open and still reaches click-outside handlers', async () => {
    await mount(html.parts);
    await open('detay');
    byId('menu').click();
    await settle();
    const menu = Alpine.$data(byId('menu').closest('[data-slot="dropdown"]'));
    assert.ok(menu.open);

    byId('text').click();
    await settle();

    assert.ok(isOpen('detay'));
    assert.equal(menu.open, false, 'The menu closes on a click elsewhere in the panel.');
});

test('a followed link closes it', async () => {
    await mount(html.parts);
    await open('detay');

    byId('link').click();
    await settle();

    assert.equal(drawer('detay').isOpen, false);
});

test('links the page handles itself, opens elsewhere or marks keep it open; wire:navigate closes it', async () => {
    await mount(html.parts);
    const body = document.querySelector('[data-acun-drawer="detay"] #first').parentElement;
    body.insertAdjacentHTML('beforeend', `
        <a id="sayfa" href="?sayfa=2">2</a>
        <a id="yeni" href="/rapor" target="_blank">Rapor</a>
        <a id="indir" href="/rapor.pdf" download>İndir</a>
        <div data-drawer-keep-open><a id="sekme" href="#ikinci">İkinci</a></div>
        <a id="git" href="/siparisler" wire:navigate>Siparişler</a>`);
    // As wire:click.prevent and wire:navigate do on the link itself.
    for (const id of ['sayfa', 'git']) byId(id).addEventListener('click', (event) => event.preventDefault());
    await open('detay');

    for (const id of ['sayfa', 'yeni', 'indir', 'sekme']) {
        byId(id).dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
        await settle();
        assert.equal(drawer('detay').isOpen, true, `#${id} keeps the panel open`);
    }

    byId('first').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    const cmd = new window.MouseEvent('click', { bubbles: true, cancelable: true, metaKey: true });
    document.querySelector('#link').dispatchEvent(cmd);
    await settle();
    assert.equal(drawer('detay').isOpen, true, 'A link opened in a new tab (⌘-click) keeps it open.');

    byId('git').dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
    await settle();
    assert.equal(drawer('detay').isOpen, false, 'A wire:navigate link leaves the page.');
});

// --- events ------------------------------------------------------------------------

test('toggle-drawer opens and closes; a quick double toggle stays closed', async () => {
    await mount(html.parts);

    dispatch('toggle-drawer', { name: 'detay' });
    await settle();
    assert.ok(isOpen('detay'));

    dispatch('toggle-drawer', { name: 'detay' });
    await settle();
    assert.equal(drawer('detay').isOpen, false);
    await closed();

    dispatch('toggle-drawer', { name: 'detay' });
    dispatch('toggle-drawer', { name: 'detay' });
    await closed();
    assert.equal(byId('detay').hasAttribute('data-open'), false, 'It does not slide in after it was closed.');
    assert.equal(byId('detay').hidden, true);
    assert.equal(locks(), 0);
});

test('events for another drawer are ignored', async () => {
    await mount(html.parts);

    dispatch('open-drawer', { name: 'baska' });
    await settle();
    assert.equal(drawer('detay').isOpen, false);

    await open('detay');
    dispatch('close-drawer', { name: 'baska' });
    await settle();
    assert.ok(isOpen('detay'));
});

test('drawer-state-changed reports opening and the start of closing', async () => {
    await mount(html.parts);
    const events = [];
    const listener = (event) => events.push(event.detail);
    window.addEventListener('drawer-state-changed', listener);

    await open('detay');
    press('Escape');
    await settle();
    window.removeEventListener('drawer-state-changed', listener);

    assert.deepEqual(events, [{ name: 'detay', open: true }, { name: 'detay', open: false }]);
});

// --- wire:model / x-model ------------------------------------------------------

test('a bound property opens and closes the drawer', async () => {
    await mount(html.bound);
    const host = Alpine.$data(byId('host'));

    host.show = true;
    await settle();
    assert.ok(isOpen('bagli'));
    assert.equal(locks(), 1);
    assert.equal(document.activeElement.getAttribute('aria-label'), 'Kapat');

    host.show = false;
    await settle();
    assert.equal(drawer('bagli').isOpen, false);
    assert.equal(locks(), 0);
});

test('closing the drawer sets the bound property', async () => {
    await mount(html.bound);
    const host = Alpine.$data(byId('host'));

    host.show = true;
    await settle();
    press('Escape');
    await settle();

    assert.equal(host.show, false);
    assert.equal(drawer('bagli').isOpen, false);
});

test('opening by event sets the bound property once', async () => {
    await mount(html.bound);
    const host = Alpine.$data(byId('host'));

    await open('bagli');

    assert.equal(host.show, true);
    assert.equal(host.sets, 1);
    assert.equal(locks(), 1, 'The property change does not open it a second time.');
});

test('a property that starts true opens the drawer on load', async () => {
    await mount(html.alpine);

    assert.ok(isOpen('alpine'));

    press('Escape');
    await settle();
    assert.equal(Alpine.$data(byId('alpine-host')).open, false);
});

// --- docked ------------------------------------------------------------------------

const dockedPanel = () => byId('docked-host').querySelector('.filter-panel');
const docked = () => window.AcunUI.plugins.get(byId('docked-host').querySelector('[data-acun-drawer]'), 'drawer');

test('on a phone the docked panel is a modal overlay', async () => {
    viewport.width = 390;
    await mount(html.docked);
    byId('filter-button').focus();
    byId('filter-button').click();
    await settle();

    assert.equal(docked().isOpen, true);
    assert.equal(dockedPanel().getAttribute('aria-modal'), 'true');
    assert.equal(locks(), 1);
    assert.equal(document.activeElement, byId('filter-field'));

    byId('apply').focus();
    assert.ok(press('Tab').defaultPrevented, 'Tab wraps around in the overlay.');

    press('Escape');
    await settle();
    assert.equal(docked().isOpen, false);
    assert.equal(Alpine.$data(byId('docked-host')).filters, false);
    assert.equal(document.activeElement, byId('filter-button'));
    assert.equal(locks(), 0);
});

test('on desktop the docked panel sits beside the page', async () => {
    viewport.width = 1280;
    await mount(html.docked);
    const host = Alpine.$data(byId('docked-host'));
    byId('filter-button').focus();

    host.filters = true;
    await settle();

    assert.equal(docked().isOpen, true);
    assert.equal(dockedPanel().getAttribute('aria-modal'), 'false');
    assert.equal(locks(), 0);
    assert.equal(document.activeElement, byId('filter-button'), 'The focus stays beside the panel.');

    byId('apply').focus();
    assert.equal(press('Tab').defaultPrevented, false, 'No focus trap.');

    byId('filter-button').click();
    await settle();
    assert.equal(host.filters, false, 'The toggle trigger closes it and sets the property.');
    viewport.width = 390;
});

test('the first focus skips an autofocus in a hidden part and counts a summary as focusable', async () => {
    const { focusFirst } = await import('../../js/behavior/focus-trap.js');
    const { focusable } = await import('../../js/core/dom.js');
    const main = await mount('<div id="box"><div hidden><input id="tab-two" autofocus></div><details><summary id="more">Ayrıntılar</summary><p>Metin</p></details><input id="visible"></div>');
    const box = main.querySelector('#box');

    assert.deepEqual(focusable(box).map((element) => element.id), ['more', 'visible']);
    assert.equal(focusFirst(box).id, 'more', 'The hidden autofocus cannot take the focus.');
});

test('Escape while a character is being composed (IME) leaves the drawer open; a handled Escape is marked', async () => {
    await mount(html.parts);
    await open('detay');

    press('Escape', { isComposing: true });
    await settle();
    assert.equal(isOpen('detay'), true);

    const escape = press('Escape');
    await settle();
    assert.equal(isOpen('detay'), false);
    assert.equal(escape.defaultPrevented, true);
});

test('an open docked panel changes mode when the window crosses lg', async () => {
    const { setViewportWidth } = await import('./browser.mjs');
    setViewportWidth(1280);
    await mount(html.docked);
    Alpine.$data(byId('docked-host')).filters = true;
    await settle();
    assert.equal(dockedPanel().getAttribute('aria-modal'), 'false');
    assert.equal(locks(), 0);

    // A rotated tablet: below lg it becomes a modal overlay at once, with the focus inside.
    setViewportWidth(800);
    await settle();
    assert.equal(docked().isOpen, true);
    assert.equal(dockedPanel().getAttribute('aria-modal'), 'true');
    assert.equal(locks(), 1);
    assert.ok(dockedPanel().contains(document.activeElement));

    // Back on a wide window: beside the page again, the page scrolls.
    setViewportWidth(1280);
    await settle();
    assert.equal(dockedPanel().getAttribute('aria-modal'), 'false');
    assert.equal(locks(), 0);

    Alpine.$data(byId('docked-host')).filters = false;
    await settle();
    setViewportWidth(390);
});

test('a docked panel is hidden while closed and carries data-open while open; the root stays', async () => {
    viewport.width = 1280;
    await mount(html.docked);
    const root = byId('docked-host').querySelector('[data-acun-drawer]');
    const backdrop = root.querySelector('[data-slot="drawer-backdrop"]');

    assert.equal(root.hidden, false);
    assert.equal(dockedPanel().hidden, true);
    assert.equal(backdrop.hidden, true);

    byId('filter-button').click();
    await settle();
    assert.equal(dockedPanel().hidden, false);
    assert.ok(dockedPanel().hasAttribute('data-open'), 'The content area reads .filter-panel[data-open].');
    assert.equal(byId('filter-button').getAttribute('aria-expanded'), 'true');

    byId('filter-button').click();
    await closed();
    assert.equal(dockedPanel().hidden, true);
    assert.equal(dockedPanel().hasAttribute('data-open'), false);
    viewport.width = 390;
});

// --- the 2.0 Alpine scope (deprecated, until 3.0) -------------------------------------------------

test('content written for the 2.0 Alpine drawer still works: close() and opened', async () => {
    await mount(html.legacy);
    assert.equal(byId('eski-durum').textContent, 'kapalı', 'Alpine starts the content: the drawer root is an Alpine root.');

    await open('eski');
    assert.equal(byId('eski-durum').textContent, 'açık');

    byId('eski-kapat').click();
    await settle();
    assert.equal(drawer('eski').isOpen, false);
    assert.equal(byId('eski-durum').textContent, 'kapalı');
    assert.equal(locks(), 0);
});
