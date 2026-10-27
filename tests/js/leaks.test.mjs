import test from 'node:test';
import assert from 'node:assert/strict';

// No leaks from the runtime's plugins (plain HTML, no Alpine): every component of the free kit, as its Blade
// view renders it, is put on the page, used (opened, typed in, a toast shown…) and taken off again, often
// while still open, three times over. After each round the page must be as it was before: no listener left
// on window, document, <html> or <body>, no timer or animation frame still pending, no observer still
// watching, nothing left in <body>, no scroll lock or overlay held, and no instance on the removed elements.
// A plugin that forgot a cleanup in destroy() keeps its listeners (and through them its elements) alive.

globalThis.acunTestPage = { alpine: false };
const { AcunUI, document, press, render, settle, sleep, window } = await import('./browser.mjs');

// ---- What is still attached ------------------------------------------------------------------------------

/**
 * Live listeners per target: "type|capture" for each listener function. Only the page's code counts: jsdom
 * itself adds window listeners for each document it makes (a <template>'s content), which a browser does not.
 */
const listeners = new Map();
const captureOf = (options) => (typeof options === 'boolean' ? options : Boolean(options?.capture));
for (const [name, target] of [['window', window], ['document', document], ['html', document.documentElement], ['body', document.body]]) {
    const live = new Map();
    listeners.set(name, live);
    const add = target.addEventListener;
    const remove = target.removeEventListener;
    target.addEventListener = function (type, listener, options) {
        if (listener && !options?.once && !new Error().stack.split('\n')[2]?.includes('node_modules')) {
            const keys = live.get(listener) ?? new Set();
            keys.add(`${type}|${captureOf(options)}`);
            live.set(listener, keys);
        }
        return add.call(this, type, listener, options);
    };
    target.removeEventListener = function (type, listener, options) {
        const keys = live.get(listener);
        keys?.delete(`${type}|${captureOf(options)}`);
        if (keys && !keys.size) live.delete(listener);
        return remove.call(this, type, listener, options);
    };
}
const listenerList = () => [...listeners].flatMap(([name, live]) => [...live.values()].flatMap((keys) => [...keys].map((key) => `${name} ${key.split('|')[0]}`))).sort();

/** Pending timers and frames: the handle and where it was made. */
const pending = new Map();
const wrapTimer = (set, clear, kind) => {
    globalThis[set] = window[set] = ((original) => (callback, ...rest) => {
        const handle = original((...args) => {
            if (kind !== 'interval') pending.delete(handle);
            callback(...args);
        }, ...rest);
        pending.set(handle, `${kind}: ${new Error().stack.split('\n')[2]?.trim()}`);
        return handle;
    })(globalThis[set].bind(globalThis));
    globalThis[clear] = window[clear] = ((original) => (handle) => {
        pending.delete(handle);
        return original(handle);
    })(globalThis[clear].bind(globalThis));
};
wrapTimer('setTimeout', 'clearTimeout', 'timeout');
wrapTimer('setInterval', 'clearInterval', 'interval');
wrapTimer('requestAnimationFrame', 'cancelAnimationFrame', 'frame');

/** Observers still watching something (observe() called, disconnect() not since). */
const watching = new Set();
const Mutations = globalThis.MutationObserver;
globalThis.MutationObserver = window.MutationObserver = class extends Mutations {
    observe(...args) { watching.add(this); return super.observe(...args); }
    disconnect() { watching.delete(this); return super.disconnect(); }
};
// jsdom has no ResizeObserver: a stand-in, so the plugins that measure take that path too.
globalThis.ResizeObserver = window.ResizeObserver = class {
    constructor(callback) { this.callback = callback; this.targets = new Set(); }
    observe(target) { this.targets.add(target); watching.add(this); }
    unobserve(target) { this.targets.delete(target); if (!this.targets.size) watching.delete(this); }
    disconnect() { this.targets.clear(); watching.delete(this); }
};

const snapshot = () => ({
    listeners: listenerList(),
    observers: watching.size,
    body: [...document.body.children].map((element) => element.tagName + (element.id ? `#${element.id}` : '')),
    locks: AcunUI.scrollLock.count,
    overlays: AcunUI.overlays.count,
});

// ---- The components ----------------------------------------------------------------------------------------

const $ = (root, selector) => {
    const element = root.querySelector(selector);
    assert.ok(element, `No ${selector}`);
    return element;
};
const click = (element) => element.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));

/** name: [template, use(root) — the interaction before the component is taken off]. */
const cases = {
    modal: ['<button type="button" data-acun-open="sizinti-m">Aç</button><acun:modal name="sizinti-m" title="Sipariş"><input id="m-alan"></acun:modal>', async (root) => {
        click($(root, '[data-acun-open]'));
        await settle();
        press('Escape');
        await settle();
        AcunUI.modal.open('sizinti-m');
        await settle();
    }],
    drawer: ['<button type="button" data-acun-toggle="sizinti-d">Filtre</button><acun:drawer name="sizinti-d" title="Filtreler"><input></acun:drawer>', async (root) => {
        click($(root, '[data-acun-toggle]'));
        await settle();
        press('Escape');
        await settle();
        AcunUI.drawer.open('sizinti-d');
        await settle();
    }],
    dropdown: [`<acun:dropdown>
            <acun:dropdown.trigger><acun:button variant="white">İşlemler</acun:button></acun:dropdown.trigger>
            <acun:dropdown.content><acun:dropdown.item>Düzenle</acun:dropdown.item><acun:dropdown.item>Sil</acun:dropdown.item></acun:dropdown.content>
        </acun:dropdown>`, async (root) => {
        click($(root, 'button'));
        await settle();
        press('ArrowDown');
        await settle();
    }],
    popover: [`<acun:popover>
            <acun:popover.trigger><acun:button variant="white">Kural</acun:button></acun:popover.trigger>
            <acun:popover.content title="İade kuralı">14 gün içinde iade.</acun:popover.content>
        </acun:popover>`, async (root) => {
        click($(root, 'button'));
        await settle();
    }],
    tooltip: ['<acun:tooltip text="Sipariş geçmişi"><button type="button">Geçmiş</button></acun:tooltip>', async (root) => {
        const trigger = $(root, 'button');
        trigger.dispatchEvent(new window.MouseEvent('mouseenter', { bubbles: true }));
        trigger.dispatchEvent(new window.FocusEvent('focusin', { bubbles: true }));
        await settle();
    }],
    tabs: [`<acun:tabs :tabs="['ozet' => 'Özet', 'kargo' => 'Kargo']"><acun:tab-panel value="ozet">-</acun:tab-panel><acun:tab-panel value="kargo">-</acun:tab-panel></acun:tabs>`, async (root) => {
        click(root.querySelectorAll('[role="tab"]')[1]);
        await settle();
    }],
    accordion: ['<acun:accordion><acun:accordion.item name="kargo" title="Kargo">Ücretsiz</acun:accordion.item></acun:accordion>', async (root) => {
        click($(root, 'button'));
        await settle();
    }],
    collapse: ['<acun:collapse label="Ayrıntı">Kargo bilgisi</acun:collapse>', async (root) => {
        click($(root, 'button'));
        await settle();
    }],
    toasts: ['<acun:toast-container />', async () => {
        window.dispatchEvent(new window.CustomEvent('toast', { detail: { type: 'success', message: 'Kaydedildi' } }));
        window.dispatchEvent(new window.CustomEvent('toast', { detail: { type: 'info', message: 'Rapor hazır', link: '/raporlar/1', linkLabel: 'Aç' } }));
        await settle();
    }],
    copy: ['<acun:copy-button text="SIP-4821" />', async (root) => {
        click($(root, 'button'));
        await settle();
    }],
    'inline confirm': ['<acun:inline-confirm question="Ürün silinsin mi?"><acun:button>Sil</acun:button></acun:inline-confirm>', async (root) => {
        click($(root, 'button'));
        await settle();
    }],
    'dismissible alert': ['<acun:alert dismissible title="Stok azaldı">Üç ürün tükenmek üzere.</acun:alert><button type="button">Sonraki</button>', async (root) => {
        $(root, '[data-acun-close]').focus();
        click($(root, '[data-acun-close]'));
        await settle();
    }],
    'closable card': ['<acun:card title="Satışlar" collapsible closable>Bu hafta 42 sipariş.</acun:card>', async (root) => {
        click($(root, '[aria-expanded]'));
        await settle();
    }],
    'password field': ['<acun:input type="password" name="sifre" label="Şifre" viewable />', async (root) => {
        click($(root, 'button'));
        await settle();
    }],
    search: ['<acun:search name="q" placeholder="Ara" />', async (root) => {
        const field = $(root, 'input');
        field.value = 'kulaklık';
        field.dispatchEvent(new window.Event('input', { bubbles: true }));
        await settle();
        press('/', {}, document.body);
        await settle();
    }],
    'scroll area': ['<acun:scroll-area shadows max-height="10rem"><p>Uzun içerik</p></acun:scroll-area>', async () => {}],
    'recovery codes': ['<acun:recovery-codes :codes="[\'a1b2-c3d4\', \'e5f6-g7h8\']" />', async () => {}],
    table: ['<acun:table><thead><tr><th>Ürün</th></tr></thead><tbody><tr><td>Kulaklık</td></tr></tbody></acun:table>', async () => {}],
};

const html = render(Object.fromEntries(Object.entries(cases).map(([name, [template]]) => [name, template])));

// The leave transitions end on a timer of their own; give them time before counting.
const quiet = async () => {
    await settle();
    await sleep(400);
    await settle();
};

for (const [name, [, use]] of Object.entries(cases)) {
    test(`${name}: put on the page, used and taken off three times, it leaves nothing behind`, async () => {
        await quiet();
        const before = snapshot();
        pending.clear();

        for (let round = 1; round <= 3; round++) {
            const root = document.createElement('section');
            root.innerHTML = html[name];
            document.body.append(root);
            await settle();

            const mounted = [...root.querySelectorAll('*')].filter((element) => [...AcunUI.plugins.names].some((kind) => AcunUI.plugins.get(element, kind)));
            assert.ok(mounted.length > 0, `${name}: the runtime mounted it`);

            await use(root);
            root.remove();
            await quiet();

            assert.deepEqual(snapshot(), before, `${name}, round ${round}: the page is as it was`);
            assert.deepEqual([...pending.values()], [], `${name}, round ${round}: no timer or frame left`);
            assert.deepEqual(mounted.filter((element) => [...AcunUI.plugins.names].some((kind) => AcunUI.plugins.get(element, kind))), [], `${name}, round ${round}: no instance on the removed elements`);
        }
    });
}
