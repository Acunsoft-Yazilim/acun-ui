import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { Alpine, AcunUI, dispatch, document, mount, press, render, settle, sleep, window } from './browser.mjs';

// The @acunsoft/acun-ui runtime (packages/acun-ui/js): window.AcunUI set up once, the counted
// scroll lock, nested dialogs, the events of drawers and modals, focus restore,
// keep-in-view and Livewire navigations / re-renders.

const html = render({
    drawers: `
        <button id="outside">Dışarıda</button>
        <acun:drawer name="sag" title="Sağ panel"><input id="sag-alan"><acun:button id="sag-modal">Modal aç</acun:button></acun:drawer>
        <acun:drawer name="sol" side="left" title="Sol panel"><input id="sol-alan"></acun:drawer>`,
    modals: `
        <button id="opener">Aç</button>
        <acun:modal name="bir" title="Birinci"><input id="bir-alan"></acun:modal>
        <acun:modal name="iki" title="İkinci"><input id="iki-alan"></acun:modal>
        <acun:modal name="sorulu" title="Sorulu" request-close-event="sorulu-kapat"><input id="sorulu-alan"></acun:modal>`,
});

const byId = (id) => document.getElementById(id);
const drawerOf = (name) => AcunUI.plugins.get(document.querySelector(`[data-acun-drawer="${name}"]`), 'drawer');
const modalOf = (name) => AcunUI.plugins.get(document.querySelector(`[data-acun-modal="${name}"]`), 'modal');
const locked = () => document.documentElement.classList.contains('scroll-locked');
const releaseVersion = JSON.parse(fs.readFileSync(new URL('../../package.json', import.meta.url), 'utf8')).version;

function collect(...names) {
    const events = [];
    const listeners = names.map((name) => [name, (event) => events.push([name, event.detail])]);
    listeners.forEach(([name, listener]) => window.addEventListener(name, listener));

    return { events, stop: () => listeners.forEach(([name, listener]) => window.removeEventListener(name, listener)) };
}

// --- one runtime per page ------------------------------------------------------------

test('the runtime sets up window.AcunUI with its public API', () => {
    assert.equal(window.AcunUI, AcunUI);
    assert.equal(AcunUI.version, releaseVersion);
    assert.equal(AcunUI.initialized, true);
    assert.deepEqual(Object.keys(AcunUI).sort(), ['appearance', 'components', 'destroy', 'drawer', 'events', 'focus', 'init', 'initialized', 'isDark', 'load', 'modal', 'observe', 'overlays', 'plugins', 'scrollLock', 'tokenColor', 'version']);
    assert.deepEqual(Object.keys(AcunUI.components).sort(), ['define', 'isLoaded', 'load']);
    assert.deepEqual(AcunUI.plugins.names, ['modal', 'drawer', 'dropdown', 'popover', 'tooltip']);
    assert.deepEqual(Object.keys(AcunUI.drawer).sort(), ['close', 'open', 'toggle']);
    assert.deepEqual(Object.keys(AcunUI.modal).sort(), ['close', 'open']);
    assert.deepEqual(Object.keys(AcunUI.appearance).sort(), ['apply', 'forgetBrandColor', 'get', 'ready', 'reset', 'scope', 'setBrandColor', 'setContentWidth', 'setMode', 'setNavbarMode', 'setSemiDark', 'setSidebarCollapsed', 'setSkin', 'subscribe', 'toggleMode']);
    // Nothing else of its own on window.
    for (const name of ['acunKeepInView', 'acunApplyBrand', 'acunApplySkin', 'acunDrawer', 'acunModal']) assert.equal(name in window, false, name);
});

test('a second copy of the runtime stays idle and registers no listener', async () => {
    const added = [];
    const record = (target) => {
        const original = target.addEventListener;
        target.addEventListener = function (type, ...rest) {
            added.push(type);
            return original.call(this, type, ...rest);
        };
        return () => { target.addEventListener = original; };
    };
    const restore = [record(window), record(document)];
    const dataBefore = Alpine.$data;

    try {
        const copy = await import('../../js/index.js?second-copy');
        assert.equal(copy.VERSION, releaseVersion);
        // What the idle copy exports is the running copy's: code importing it works on the instances in use.
        assert.equal(copy.scrollLock, AcunUI.scrollLock);
        assert.equal(copy.overlays, AcunUI.overlays);
        assert.equal(copy.components, AcunUI.components);
        assert.equal(copy.focusFirst, AcunUI.focus.first);
        assert.equal(copy.focusable, AcunUI.focus.focusable);
        assert.equal(copy.plugins, AcunUI.plugins);
        assert.equal(copy.init, AcunUI.init);
    } finally {
        restore.forEach((undo) => undo());
    }

    assert.deepEqual(added, [], 'No listener from the second copy.');
    assert.equal(window.AcunUI, AcunUI, 'The namespace is the first copy\'s.');
    assert.equal(Alpine.$data, dataBefore);
});

test('with two copies on the page one Escape still closes one dialog', async () => {
    await mount(html.drawers + html.modals);
    byId('outside').focus();
    AcunUI.drawer.open('sag');
    await settle();
    byId('sag-modal').focus();
    AcunUI.modal.open('bir');
    await settle();

    press('Escape');
    await settle();

    assert.equal(modalOf('bir').isOpen, false);
    assert.equal(drawerOf('sag').isOpen, true, 'The drawer under it stays open.');
});

// --- scroll lock -----------------------------------------------------------------------

test('the scroll lock is counted', () => {
    AcunUI.scrollLock.reset();

    AcunUI.scrollLock.lock();
    AcunUI.scrollLock.lock();
    assert.equal(AcunUI.scrollLock.count, 2);
    assert.ok(locked());

    AcunUI.scrollLock.unlock();
    assert.ok(locked(), 'One dialog still open: the page stays locked.');
    AcunUI.scrollLock.unlock();
    assert.equal(locked(), false);

    AcunUI.scrollLock.unlock();
    assert.equal(AcunUI.scrollLock.count, 0, 'Never below zero.');
});

test('the first name window.acunScrollLock still works, with a warning', () => {
    const warnings = [];
    const warn = console.warn;
    console.warn = (message) => warnings.push(message);

    try {
        assert.equal(window.acunScrollLock, AcunUI.scrollLock);
        assert.equal(window.acunScrollLock, AcunUI.scrollLock);
    } finally {
        console.warn = warn;
    }

    assert.equal(warnings.length, 1);
    assert.match(warnings[0], /AcunUI\.scrollLock/);
});

// --- nested dialogs ----------------------------------------------------------------------

test('a click on the backdrop gives the focus back even when it moved to a focusable element around the dialog', async () => {
    await mount(`<div id="icerik" tabindex="-1">${html.modals}</div>`);
    byId('opener').focus();
    AcunUI.modal.open('bir');
    await settle();
    assert.ok(modalOf('bir').panel.contains(document.activeElement));

    // The browser moves the focus of a click on the backdrop to the nearest focusable ancestor.
    byId('icerik').focus();
    document.querySelector('[data-acun-modal="bir"] [data-slot="modal-backdrop"]').click();
    await settle();
    assert.equal(modalOf('bir').isOpen, false);
    assert.equal(document.activeElement, byId('opener'));
});

test('a modal over a drawer: lifted above it, the lock is held until both close, the focus goes back step by step', async () => {
    // The z-index of the components' Tailwind class (no panel.css in jsdom).
    await mount('<style>.z-50 { z-index: 50; }</style>' + html.drawers + html.modals);
    byId('outside').focus();
    AcunUI.drawer.open('sag');
    await settle();
    assert.equal(AcunUI.scrollLock.count, 1);

    byId('sag-modal').focus();
    AcunUI.modal.open('bir');
    await settle();
    const modalRoot = document.querySelector('[data-acun-modal="bir"]');

    assert.equal(AcunUI.overlays.count, 2);
    assert.equal(AcunUI.overlays.top(), modalRoot);
    assert.equal(AcunUI.scrollLock.count, 2);
    assert.equal(modalRoot.style.zIndex, '51', 'One above the drawer (z-50).');
    assert.ok(modalRoot.contains(document.activeElement));

    press('Escape');
    await settle();
    assert.equal(AcunUI.scrollLock.count, 1);
    assert.ok(locked(), 'The drawer is still open.');
    assert.equal(modalRoot.style.zIndex, '', 'Back to its own z-index.');
    assert.equal(document.activeElement, byId('sag-modal'), 'Back to the button in the drawer.');

    press('Escape');
    await settle();
    assert.equal(drawerOf('sag').isOpen, false);
    assert.equal(locked(), false);
    assert.equal(document.activeElement, byId('outside'));
});

test('Tab stays in the newest dialog', async () => {
    await mount(html.drawers + html.modals);
    AcunUI.drawer.open('sag');
    await settle();
    byId('sag-modal').focus();
    AcunUI.modal.open('bir');
    await settle();

    const inModal = AcunUI.focus.focusable(modalOf('bir').panel);
    inModal[inModal.length - 1].focus();
    assert.ok(press('Tab').defaultPrevented);
    assert.equal(document.activeElement, inModal[0]);
});

// --- Livewire navigation ----------------------------------------------------------------

test('a wire:navigate page change resets the lock and forgets the dialogs of the old page', async () => {
    await mount(html.drawers);
    const old = document.createElement('div');
    document.body.append(old);
    AcunUI.overlays.open(old, { close() {} });
    AcunUI.scrollLock.lock();
    assert.equal(AcunUI.scrollLock.count, 2);

    old.remove();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

    assert.equal(AcunUI.overlays.count, 0);
    assert.equal(AcunUI.scrollLock.count, 0);
    assert.equal(locked(), false);
});

test('a dialog kept across the page change (@persist) keeps its lock', async () => {
    await mount(html.drawers);
    AcunUI.drawer.open('sol');
    await settle();

    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

    assert.equal(AcunUI.overlays.count, 1);
    assert.equal(AcunUI.scrollLock.count, 1);
});

// --- drawer events -------------------------------------------------------------------------

test('drawers open and close by the API, the new events and the first names', async () => {
    await mount(html.drawers);
    const { events, stop } = collect('acun:drawer:opened', 'acun:drawer:closed', 'drawer-state-changed');

    AcunUI.drawer.open('sag');
    await settle();
    assert.equal(drawerOf('sag').isOpen, true);
    assert.equal(drawerOf('sol').isOpen, false, 'Only the named one.');

    AcunUI.drawer.toggle('sag');
    await settle();
    assert.equal(drawerOf('sag').isOpen, false);

    dispatch('acun:drawer:open', { name: 'sol' });
    await settle();
    assert.equal(drawerOf('sol').isOpen, true);
    dispatch('close-drawer', { name: 'sol' });
    await settle();
    assert.equal(drawerOf('sol').isOpen, false);

    dispatch('open-drawer', { name: 'sag' });
    dispatch('acun:drawer:toggle', { name: 'sol' });
    await settle();
    AcunUI.drawer.close();
    await settle();
    stop();

    assert.equal(drawerOf('sag').isOpen, false, 'close() without a name closes every drawer.');
    assert.equal(drawerOf('sol').isOpen, false);
    assert.deepEqual(events.slice(0, 4), [
        ['acun:drawer:opened', { name: 'sag' }],
        ['drawer-state-changed', { name: 'sag', open: true }],
        ['acun:drawer:closed', { name: 'sag' }],
        ['drawer-state-changed', { name: 'sag', open: false }],
    ]);
});

// --- modal events --------------------------------------------------------------------------

test('modals open and close by the API, the new events and the first names; one at a time', async () => {
    await mount(html.modals);
    const { events, stop } = collect('acun:modal:opened', 'acun:modal:closed', 'modal-closed');

    AcunUI.modal.open('bir');
    await settle();
    assert.equal(modalOf('bir').isOpen, true);

    // Opened from the page (a shortcut, a server event while nothing in the modal has the focus).
    document.activeElement.blur();
    dispatch('open-modal', { name: 'iki' });
    await settle();
    assert.equal(modalOf('iki').isOpen, true);
    assert.equal(modalOf('bir').isOpen, false, 'Opening a modal closes the other one.');
    assert.equal(AcunUI.scrollLock.count, 1);

    dispatch('acun:modal:close', { name: 'iki' });
    await settle();
    assert.equal(modalOf('iki').isOpen, false);

    dispatch('acun:modal:open', { name: 'bir' });
    await settle();
    dispatch('close-modal');
    await settle();
    stop();

    assert.equal(modalOf('bir').isOpen, false, 'close-modal without a name closes every modal.');
    assert.equal(AcunUI.scrollLock.count, 0);
    assert.deepEqual(events.slice(0, 5), [
        ['acun:modal:opened', { name: 'bir' }],
        // Replaced by another modal: as when the user closes it (its close-action runs).
        ['acun:modal:closed', { name: 'bir', reason: 'user' }],
        ['modal-closed', { name: 'bir', reason: 'user' }],
        ['acun:modal:opened', { name: 'iki' }],
        // Closed by a command (the page or the server asked).
        ['acun:modal:closed', { name: 'iki', reason: 'command' }],
    ]);
});

test('a modal opened from inside another goes on top of it; a replaced one tells the server', async () => {
    // The $wire of the Livewire component around the modals (no server here): records the close-action calls.
    const calls = [];
    const find = window.Livewire.find;
    window.Livewire.find = (id) => (id === 'sayfa' ? { kapat: () => calls.push('kapat') } : find(id));
    await mount(`<div wire:id="sayfa">${render({ stacked: `
        <acun:modal name="duzenle" title="Düzenle" close-action="kapat"><input id="duzenle-alan"><button id="sil">Sil</button></acun:modal>
        <acun:modal name="onay" title="Silinsin mi?"><button id="evet">Evet</button></acun:modal>
        <acun:modal name="baska" title="Başka"><input id="baska-alan"></acun:modal>` }).stacked}</div>`);

    AcunUI.modal.open('duzenle');
    await settle();
    byId('sil').focus();
    AcunUI.modal.open('onay');
    await settle();
    assert.deepEqual([modalOf('duzenle').isOpen, modalOf('onay').isOpen], [true, true], 'The confirmation stacks on the edit form.');
    assert.equal(AcunUI.overlays.count, 2);

    press('Escape');
    await settle();
    assert.deepEqual([modalOf('duzenle').isOpen, modalOf('onay').isOpen], [true, false], 'Escape closes only the top one.');

    // Opened from outside: the edit form is replaced and its close-action runs.
    assert.deepEqual(calls, [], 'Escape on the confirmation does not close the edit form.');
    document.activeElement.blur();
    AcunUI.modal.open('baska');
    await settle();
    assert.deepEqual([modalOf('duzenle').isOpen, modalOf('baska').isOpen], [false, true]);
    assert.deepEqual(calls, ['kapat']);
    AcunUI.modal.close();
    await settle();

    // Closed by a command (the page or the server asked): no close-action.
    AcunUI.modal.open('duzenle');
    await settle();
    AcunUI.modal.close('duzenle');
    await settle();
    assert.deepEqual(calls, ['kapat'], 'A close the page asked for does not call the server back.');
    window.Livewire.find = find;
});

test('request-close-event: Escape and the backdrop ask instead of closing', async () => {
    await mount(html.modals);
    const { events, stop } = collect('sorulu-kapat');

    AcunUI.modal.open('sorulu');
    await settle();
    press('Escape');
    document.querySelector('[data-acun-modal="sorulu"] .ui-backdrop').click();
    await settle();
    stop();

    assert.equal(modalOf('sorulu').isOpen, true);
    assert.equal(events.length, 2);
});

// --- focus -----------------------------------------------------------------------------------

test('a modal takes the focus and gives it back to the element that opened it', async () => {
    await mount(html.modals);
    byId('opener').focus();

    AcunUI.modal.open('bir');
    await settle();
    assert.ok(document.querySelector('[data-acun-modal="bir"]').contains(document.activeElement));

    press('Escape');
    await settle();
    assert.equal(document.activeElement, byId('opener'));
});

test('AcunUI.focus: first takes [autofocus], trap keeps Tab inside, remember gives it back', async () => {
    await mount('<button id="before">Önce</button><div id="box"><input id="a"><input id="b" autofocus></div>');
    byId('before').focus();
    const back = AcunUI.focus.remember();

    assert.equal(AcunUI.focus.first(byId('box')), byId('b'));
    byId('b').focus();
    const event = new window.KeyboardEvent('keydown', { key: 'Tab', cancelable: true });
    AcunUI.focus.trap(event, byId('box'));
    assert.ok(event.defaultPrevented);
    assert.equal(document.activeElement, byId('a'));

    back();
    assert.equal(document.activeElement, byId('before'));
});

test('Tab: a radio group is one stop, inert and disabled-fieldset controls are skipped', async () => {
    await mount(`<div id="box">
        <button id="first">İlk</button>
        <fieldset disabled><input id="off"></fieldset>
        <div inert><button id="asleep">Uykuda</button></div>
        <label><input type="radio" name="plan" id="p1" value="1"> Bir</label>
        <label><input type="radio" name="plan" id="p2" value="2" checked> İki</label>
        <label><input type="radio" name="plan" id="p3" value="3"> Üç</label>
    </div>`);

    assert.deepEqual(AcunUI.focus.focusable(byId('box')).map((element) => element.id), ['first', 'p2']);

    // From the checked radio (the last stop) Tab goes round to the first element.
    byId('p2').focus();
    const event = new window.KeyboardEvent('keydown', { key: 'Tab', cancelable: true });
    AcunUI.focus.trap(event, byId('box'));
    assert.ok(event.defaultPrevented);
    assert.equal(document.activeElement, byId('first'));
});

test('Tab: a focus that got out to the page behind the dialog comes back in', async () => {
    await mount('<a href="#" id="behind">Arkada</a><div id="box"><button id="a">A</button><button id="b">B</button></div><div role="listbox" id="list" tabindex="0"></div>');

    byId('behind').focus();
    const event = new window.KeyboardEvent('keydown', { key: 'Tab', cancelable: true });
    AcunUI.focus.trap(event, byId('box'));
    assert.ok(event.defaultPrevented);
    assert.equal(document.activeElement, byId('a'));

    // A list opened over the dialog keeps its own focus.
    byId('list').focus();
    const own = new window.KeyboardEvent('keydown', { key: 'Tab', cancelable: true });
    AcunUI.focus.trap(own, byId('box'));
    assert.equal(own.defaultPrevented, false);
});

// --- keep-in-view (Livewire hooks) --------------------------------------------------------

function list(top, bottom) {
    const root = document.createElement('section');
    root.setAttribute('data-keep-in-view', '');
    let rect = { top, bottom };
    root.getBoundingClientRect = () => rect;
    root.scrolled = 0;
    root.scrollIntoView = () => root.scrolled++;
    document.body.append(root);

    return { root, setRect: (next) => { rect = next; } };
}

const commit = (root) => window.Livewire.trigger('commit', { component: { el: root }, commit: {}, respond: (callback) => callback(), succeed() {}, fail() {} });
const morphed = (root) => window.Livewire.trigger('morphed', { el: root, component: { el: root } });

test('a list that got much shorter while its top was above the screen is brought back into view', async () => {
    await mount('');
    const { root, setRect } = list(-900, 400);

    commit(root);
    setRect({ top: -300, bottom: 120 });
    morphed(root);

    assert.equal(root.scrolled, 1);
});

test('a list in view, or one that is still long, is left where it is', async () => {
    await mount('');
    const visible = list(40, 900);
    commit(visible.root);
    morphed(visible.root);

    const long = list(-900, 1200);
    commit(long.root);
    long.setRect({ top: -800, bottom: 1300 });
    morphed(long.root);

    const unmarked = list(-900, 400);
    unmarked.root.removeAttribute('data-keep-in-view');
    commit(unmarked.root);
    unmarked.setRect({ top: -300, bottom: 120 });
    morphed(unmarked.root);

    assert.deepEqual([visible.root.scrolled, long.root.scrolled, unmarked.root.scrolled], [0, 0, 0]);
});

// --- several instances, re-renders ----------------------------------------------------------

test('several drawers and modals on one page keep their own state', async () => {
    await mount(html.drawers + html.modals);

    AcunUI.drawer.open('sol');
    AcunUI.modal.open('iki');
    await settle();

    assert.deepEqual([drawerOf('sag').isOpen, drawerOf('sol').isOpen, modalOf('bir').isOpen, modalOf('iki').isOpen], [false, true, false, true]);
    assert.equal(AcunUI.overlays.count, 2);
});

test('a Livewire re-render: new content is used, a removed open drawer releases the lock, a new copy works', async () => {
    const main = await mount(html.drawers);
    AcunUI.drawer.open('sag');
    await settle();

    // Morphed content inside the open panel: Tab uses the new elements.
    const panel = drawerOf('sag').panel;
    panel.querySelector('#sag-alan').insertAdjacentHTML('afterend', '<button id="yeni">Yeni</button>');
    await settle();
    byId('sag-modal').focus();
    assert.ok(press('Tab').defaultPrevented);
    byId('yeni').focus();
    assert.equal(press('Tab').defaultPrevented, false, 'The new button is in the order.');

    // The component re-rendered without the drawer: the observer stops it (Alpine destroys its scope).
    const root = document.querySelector('[data-acun-drawer="sag"]');
    root.remove();
    await settle();
    assert.equal(AcunUI.plugins.isMounted(root), false);
    assert.equal(AcunUI.overlays.count, 0);
    assert.equal(locked(), false);

    // And with it again.
    main.insertAdjacentHTML('beforeend', render({ again: '<acun:drawer name="sag" title="Sağ panel"><input id="tekrar"></acun:drawer>' }).again);
    await settle();
    AcunUI.drawer.open('sag');
    await settle();
    assert.equal(drawerOf('sag').isOpen, true);
    assert.equal(AcunUI.scrollLock.count, 1);
});

// --- forced layouts --------------------------------------------------------------------------

test('a page that forces its layout is left with a full page load, unless something stopped the leave', async () => {
    await mount('');
    const island = document.createElement('script');
    island.type = 'application/json';
    island.setAttribute('data-acun-ui-config', '');
    island.textContent = JSON.stringify({ forced: true });
    document.head.append(island);

    try {
        const leave = new window.CustomEvent('livewire:navigate', { detail: { url: 'http://localhost/#sonraki' }, bubbles: true, cancelable: true });
        document.dispatchEvent(leave);
        assert.ok(leave.defaultPrevented, 'wire:navigate is replaced by a full load.');
        assert.equal(window.location.hash, '#sonraki');

        const stop = (event) => event.preventDefault();
        document.addEventListener('livewire:navigate', stop);
        const guarded = new window.CustomEvent('livewire:navigate', { detail: { url: 'http://localhost/#baska' }, bubbles: true, cancelable: true });
        document.dispatchEvent(guarded);
        document.removeEventListener('livewire:navigate', stop);
        assert.equal(window.location.hash, '#sonraki', 'An unsaved-changes guard keeps the page.');

        island.textContent = JSON.stringify({ forced: false });
        const normal = new window.CustomEvent('livewire:navigate', { detail: { url: 'http://localhost/#normal' }, bubbles: true, cancelable: true });
        document.dispatchEvent(normal);
        assert.equal(normal.defaultPrevented, false, 'Other pages keep wire:navigate.');
    } finally {
        island.remove();
    }
});

test('a page that forces its layout, reached with wire:navigate, is loaded again in full', async () => {
    await mount('');
    const { page } = await import('../../js/behavior/forced-layout.js');
    const reload = page.reload;
    let reloads = 0;
    page.reload = () => reloads++;

    // Livewire adds the new page's head (its settings) before the onSwap callbacks run.
    const visit = (forced) => {
        const callbacks = [];
        document.dispatchEvent(new window.CustomEvent('livewire:navigating', { detail: { onSwap: (callback) => callbacks.push(callback) } }));
        const island = document.createElement('script');
        island.type = 'application/json';
        island.setAttribute('data-acun-ui-config', '');
        island.textContent = JSON.stringify({ forced });
        document.head.append(island);
        callbacks.forEach((callback) => callback());

        return island;
    };

    const islands = [];
    try {
        islands.push(visit(false));
        assert.equal(reloads, 0, 'Other pages stay with wire:navigate.');

        islands.push(visit(true));
        assert.equal(reloads, 1);
    } finally {
        page.reload = reload;
        islands.forEach((island) => island.remove());
    }
});

test('no listener runs for keys when no dialog is open', async () => {
    await mount('<input id="alan">');
    byId('alan').focus();

    assert.equal(press('Tab').defaultPrevented, false);
    assert.equal(press('Escape').defaultPrevented, false);
    await sleep(0);
});

test('a modal bound with x-model opens with its property and sets it back when closed', async () => {
    const bound = render({
        modal: '<div id="page" x-data="{ showForm: false }"><acun:modal name="bagli" title="Müşteri" x-model="showForm"><input id="bagli-alan"></acun:modal></div>',
    });
    await mount(bound.modal);
    const page = Alpine.$data(document.getElementById('page'));
    const modalData = modalOf('bagli');

    page.showForm = true;
    await settle();
    assert.equal(modalData.isOpen, true);
    assert.equal(locked(), true);

    // The user closes it (Escape): the property follows.
    press('Escape');
    await settle();
    assert.equal(modalData.isOpen, false);
    assert.equal(page.showForm, false);
    assert.equal(locked(), false);
});

test('deprecated: markup of its own written for x-data="acunModal(…)" still opens and closes, with one warning', async () => {
    const warnings = [];
    const { warn } = console;
    console.warn = (message) => warnings.push(String(message));

    try {
        await mount(`
            <button id="eski-ac" x-data x-on:click="$dispatch('open-modal', { name: 'eski' })">Aç</button>
            <div x-data="acunModal({ name: 'eski' })" class="fixed inset-0">
                <div x-show="open" x-ref="panel" role="dialog" aria-label="Eski">
                    <input id="eski-alan"><button id="eski-kapat" x-on:click="close()">Kapat</button>
                </div>
            </div>`);
        const root = document.querySelector('[x-data^="acunModal"]');

        byId('eski-ac').focus();
        byId('eski-ac').click();
        await settle();
        const modal = AcunUI.plugins.get(root, 'modal');
        assert.equal(modal.isOpen, true);
        assert.equal(Alpine.$data(root).open, true);
        assert.notEqual(root.querySelector('[role="dialog"]').style.display, 'none', 'Its own x-show shows the panel.');
        assert.equal(document.activeElement, byId('eski-alan'));
        assert.equal(root.hidden, false, 'The 2.0 markup shows and hides itself: the root is not hidden.');

        byId('eski-kapat').click();
        await settle();
        assert.equal(modal.isOpen, false);
        assert.equal(document.activeElement, byId('eski-ac'));
        assert.equal(locked(), false);
    } finally {
        console.warn = warn;
    }

    assert.equal(warnings.filter((message) => message.includes('acunModal')).length, 1);
});
