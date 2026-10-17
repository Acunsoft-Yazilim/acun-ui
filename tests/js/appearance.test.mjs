import test from 'node:test';
import assert from 'node:assert/strict';

// The look of the page on <html> (js/appearance/theme.js and stores.js): data-theme from the mode
// and the theme pair, the user's colour as --ac-user-*, the order of precedence of every setting,
// the browser storage per scope, and the stores on a page with acun:layout.head.

const island = (values) => `<script type="application/json" data-acun-ui-config>${JSON.stringify(values)}</script>`;
const COLORS = { green: '#008933', dark: { light: '#3f404d', dark: '#474747' } };
const THEME = { key: 'default', light: 'light', dark: 'dark' };
const TARGETS = { light: '#ffffff', dark: '#1f1f1f' };
const PAGE = { scope: 'panel', theme: THEME, targets: TARGETS, colors: COLORS, mode: null, brandColor: null, sidebarMode: null, navbarMode: 'static', skin: null, semiDark: false, preferenceUrl: null, defaults: { mode: 'dark', skin: 'bordered', sidebar_mode: 'collapsed' }, forced: false };

globalThis.acunTestPage = { head: island(PAGE) };

const { Alpine, AcunUI, document, setColorScheme, window } = await import('./browser.mjs');
const { USER_PROPERTIES, applyColor, applyContentWidth, brandShades, initialPreferences, isDark, readStored, storageKey, tokenColor } = await import('../../js/appearance/theme.js');

const root = document.documentElement;
const user = () => Object.fromEntries(USER_PROPERTIES.map((name) => [name, root.style.getPropertyValue(name)]).filter(([, value]) => value !== ''));
const saved = (scope) => JSON.parse(window.localStorage.getItem(storageKey(scope)) ?? 'null');

/** Puts a newer island on the page (as wire:navigate does) and lets the stores read it. */
function navigateTo(values) {
    const script = document.createElement('script');
    script.type = 'application/json';
    script.dataset.acunUiConfig = '';
    script.textContent = JSON.stringify({ ...PAGE, ...values });
    document.head.append(script);
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

    return script;
}

test('the page or account wins, then the browser (the scope\'s key), then the application defaults', () => {
    window.localStorage.clear();
    const config = { scope: 'panel', mode: null, navbarMode: 'hidden', semiDark: null, defaults: { mode: 'dark', navbar_mode: 'static', semi_dark: true, sidebar_mode: 'collapsed' } };

    assert.deepEqual(initialPreferences(config), { mode: 'dark', brandColor: 'default', skin: 'default', contentWidth: 'full', navbarMode: 'hidden', sidebarCollapsed: true, semiDark: true });

    window.localStorage.setItem('acun-ui:panel', JSON.stringify({ mode: 'light', sidebar: 'expanded', semiDark: false, brandColor: '#e11d48' }));
    assert.equal(initialPreferences(config).mode, 'light', 'The browser beats the default.');
    assert.equal(initialPreferences(config).sidebarCollapsed, false);
    assert.equal(initialPreferences(config).semiDark, false);
    assert.equal(initialPreferences(config).brandColor, '#e11d48');
    assert.equal(initialPreferences({ ...config, mode: 'system' }).mode, 'system', 'The page beats the browser.');
    window.localStorage.clear();
});

test('the 1.x keys are not read: saved choices start over once', () => {
    window.localStorage.clear();
    Object.entries({ appearance: 'dark', brandColor: 'green', skin: 'bordered', contentWidth: 'boxed', sidebarCollapsed: 'true', navbarMode: 'hidden', semiDark: 'true' })
        .forEach(([key, value]) => window.localStorage.setItem(key, value));

    assert.deepEqual(initialPreferences({ scope: 'panel', defaults: {} }), { mode: 'system', brandColor: 'default', skin: 'default', contentWidth: 'full', navbarMode: 'fixed', sidebarCollapsed: false, semiDark: false });
    window.localStorage.clear();
});

test('two scopes on one origin do not share the colour or the mode', () => {
    window.localStorage.clear();
    Alpine.store('appearance').set('dark');
    Alpine.store('brand').set('#e11d48');

    assert.deepEqual(saved('panel'), { mode: 'dark', brandColor: '#e11d48' });
    assert.equal(saved('shop'), null);
    assert.deepEqual(initialPreferences({ scope: 'shop', defaults: {} }), { mode: 'system', brandColor: 'default', skin: 'default', contentWidth: 'full', navbarMode: 'fixed', sidebarCollapsed: false, semiDark: false });
    // A page without a scope keeps its choices under "app".
    assert.equal(storageKey(undefined), 'acun-ui:app');
    assert.deepEqual(readStored(undefined), {});

    // A page of the storefront reached with wire:navigate starts from the storefront's choices…
    const shop = navigateTo({ scope: 'shop', defaults: {} });
    assert.equal(Alpine.store('appearance').value, 'system');
    assert.equal(root.dataset.theme, 'light');
    assert.deepEqual(user(), {}, 'The panel\'s colour stays in the panel.');
    // …and what it chooses stays there.
    Alpine.store('brand').set('green');
    assert.deepEqual(saved('shop'), { brandColor: 'green' });
    assert.deepEqual(saved('panel'), { mode: 'dark', brandColor: '#e11d48' });

    // Back in the panel: its own choices again.
    shop.remove();
    const panel = navigateTo({});
    assert.equal(Alpine.store('appearance').value, 'dark');
    assert.equal(root.dataset.theme, 'dark');
    assert.equal(user()['--ac-user-fill'], '#e11d48');
    panel.remove();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

    Alpine.store('brand').set('default');
    Alpine.store('appearance').set('light');
    window.localStorage.clear();
});

test('content width: boxed sets the container width on <html>, wide clears it', () => {
    window.localStorage.clear();
    assert.equal(initialPreferences({ defaults: { content_width: 'boxed' } }).contentWidth, 'boxed', 'The application default.');
    window.localStorage.setItem('acun-ui:app', JSON.stringify({ contentWidth: 'full' }));
    assert.equal(initialPreferences({ defaults: { content_width: 'boxed' } }).contentWidth, 'full', 'The browser beats the default.');
    assert.equal(initialPreferences({ contentWidth: 'boxed', defaults: {} }).contentWidth, 'boxed', 'The page beats the browser.');
    window.localStorage.clear();

    applyContentWidth('boxed');
    assert.equal(root.dataset.contentWidth, 'boxed');
    applyContentWidth('full');
    assert.equal(root.dataset.contentWidth, undefined);
});

test('the user\'s colour is measured on the island\'s targets and written once on <html>', () => {
    const config = { colors: COLORS, targets: TARGETS };

    applyColor('#f9c74f', config);
    const yellow = brandShades('#f9c74f', TARGETS);
    assert.deepEqual(user(), {
        '--ac-user-fill': '#f9c74f',
        '--ac-user-fill-hover': yellow.hover,
        '--ac-user-on-fill': yellow.onFill,
        '--ac-user-tone': yellow.tone,
        '--ac-user-tone-dark': yellow.toneDark,
    });
    assert.notEqual(yellow.onFill, '#ffffff', 'White text would not be readable on it.');
    assert.notEqual(yellow.tone, '#f9c74f', 'A light colour gets a darker tone for text.');
    assert.equal(root.dataset.brandColor, undefined, 'No data-brand-color any more.');

    applyColor('#1e3a8a', config);
    assert.equal(user()['--ac-user-on-fill'], '#ffffff', 'A dark colour keeps white text.');
    assert.equal(user()['--ac-user-tone'], '#1e3a8a', 'It reads on white as it is.');
    assert.notEqual(user()['--ac-user-tone-dark'], '#1e3a8a', 'The dark theme gets a lighter tone of it.');

    // Darker light targets (a theme with a cream surface) make a darker tone.
    assert.notEqual(brandShades('#2563eb', { light: '#d4d4d8', dark: '#1f1f1f' }).tone, brandShades('#2563eb', TARGETS).tone);

    // A preset is a colour of the island; the dark one has its own fill in a dark theme.
    applyColor('green', config);
    assert.equal(user()['--ac-user-fill'], '#008933');
    assert.equal(user()['--ac-user-fill-dark'], undefined);
    applyColor('dark', config);
    assert.deepEqual([user()['--ac-user-fill'], user()['--ac-user-fill-dark'], user()['--ac-user-on-fill-dark']], ['#3f404d', '#474747', '#ffffff']);
    assert.equal(user()['--ac-user-fill-hover-dark'], brandShades('#474747', TARGETS).hover);

    // The theme's own colour: no user variables. A name that is no longer a preset is the same.
    applyColor('default', config);
    assert.deepEqual(user(), {});
    applyColor('#123456', config);
    applyColor('purple', config);
    assert.deepEqual(user(), {});
});

test('with acun:layout.head the runtime registers the stores from its settings and writes data-theme', () => {
    window.localStorage.clear();
    assert.equal(Alpine.store('navbar').mode, 'static');
    assert.equal(Alpine.store('customizer').semiDark, false);
    assert.equal(Alpine.store('customizer').skin, 'bordered', 'The default, as nothing else was given.');
    assert.equal(Alpine.store('sidebar').collapsed, true);

    Alpine.store('sidebar').setCollapsed(false);
    assert.equal(root.dataset.sidebarCollapsed, 'false');
    assert.deepEqual(saved('panel'), { sidebar: 'expanded' });

    Alpine.store('appearance').set('dark');
    assert.equal(root.dataset.theme, 'dark');
    assert.ok(root.classList.contains('dark'), 'class="dark" goes with it for application code.');
    Alpine.store('appearance').toggle();
    assert.equal(Alpine.store('appearance').value, 'light');
    assert.equal(root.dataset.theme, 'light');
    assert.equal(root.classList.contains('dark'), false);
    assert.equal(saved('panel').mode, 'light');
    window.localStorage.clear();
});

test('a theme pair of its own: data-theme names its members; without a dark member the page stays light', () => {
    const named = navigateTo({ theme: { key: 'atelier', light: 'atelier', dark: 'atelier-dark' } });
    Alpine.store('appearance').set('dark');
    assert.equal(root.dataset.theme, 'atelier-dark');
    Alpine.store('appearance').set('light');
    assert.equal(root.dataset.theme, 'atelier');
    named.remove();

    const single = navigateTo({ theme: { key: 'paper', light: 'paper', dark: null } });
    Alpine.store('appearance').set('dark');
    assert.equal(root.dataset.theme, 'paper');
    assert.equal(root.classList.contains('dark'), false);
    single.remove();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));
    Alpine.store('appearance').set('light');
    window.localStorage.clear();
});

test('semi-dark: the theme\'s dark member on the menu, and the dark class with it', async () => {
    const { mount } = await import('./browser.mjs');
    await mount('<aside data-acun-semi-dark class="panel-sidebar"><i id="item"></i></aside>');
    const menu = document.querySelector('aside');

    Alpine.store('customizer').setSemiDark(true);
    assert.equal(menu.dataset.theme, 'dark');
    assert.ok(menu.classList.contains('dark'));
    assert.equal(isDark(document.getElementById('item')), true, 'AcunUI.isDark reads the nearest data-theme.');
    assert.equal(isDark(document.body), false);
    assert.equal(AcunUI.isDark, isDark);

    Alpine.store('customizer').setSemiDark(false);
    assert.equal(menu.dataset.theme, undefined);
    assert.equal(menu.classList.contains('dark'), false);
    window.localStorage.clear();
});

test('after a wire:navigate page change the stores put data-theme, the colour and the menu back on <html>', () => {
    Alpine.store('sidebar').setCollapsed(true);
    Alpine.store('appearance').set('dark');
    Alpine.store('brand').set('#1e3a8a');
    const before = user();

    // Livewire writes the new page's raw <html> attributes: the server's light member, no style.
    const strip = () => {
        delete root.dataset.sidebarCollapsed;
        root.dataset.theme = 'light';
        root.classList.remove('dark');
        root.removeAttribute('style');
    };

    strip();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));
    assert.equal(root.dataset.sidebarCollapsed, 'true');
    assert.equal(root.dataset.theme, 'dark');
    assert.ok(root.classList.contains('dark'));
    assert.deepEqual(user(), before);

    // onSwap: in the same step the new page is put in, before the browser paints.
    strip();
    let swap = null;
    document.dispatchEvent(new window.CustomEvent('livewire:navigating', { detail: { onSwap: (callback) => (swap = callback) } }));
    swap();
    assert.equal(root.dataset.theme, 'dark');
    assert.deepEqual(user(), before);

    Alpine.store('brand').set('default');
    Alpine.store('appearance').set('light');
    window.localStorage.clear();
});

test('the system mode follows the operating system while the page is open', () => {
    Alpine.store('appearance').set('system');
    assert.equal(root.dataset.theme, 'light');

    setColorScheme(true);
    assert.equal(root.dataset.theme, 'dark');
    assert.ok(root.classList.contains('dark'));

    setColorScheme(false);
    assert.equal(root.dataset.theme, 'light');

    // A chosen mode stays as it is.
    Alpine.store('appearance').set('light');
    setColorScheme(true);
    assert.equal(root.dataset.theme, 'light');
    setColorScheme(false);
    window.localStorage.clear();
});

test('the choices are saved to the account with the address of the current page; reset forgets the scope', () => {
    const posts = [];
    globalThis.fetch = window.fetch = async (url, init) => { posts.push([url, JSON.parse(init.body)]); return { ok: true }; };

    // The first page had no preference address (its island says null): nothing is sent.
    Alpine.store('sidebar').set(true);
    assert.deepEqual(posts, []);

    // wire:navigate brings a page whose island has the address: from then on the choices are saved.
    const page = navigateTo({ preferenceUrl: '/tercihler', defaults: { sidebar_mode: 'expanded' } });

    Alpine.store('sidebar').set(false);
    Alpine.store('sidebar').toggle();
    Alpine.store('appearance').set('dark');
    assert.deepEqual(posts, [['/tercihler', { sidebar: 'expanded' }], ['/tercihler', { sidebar: 'collapsed' }], ['/tercihler', { mode: 'dark' }]]);

    // Reset uses the new page's defaults, saves them to the account and forgets the scope's key.
    posts.length = 0;
    Alpine.store('customizer').reset();
    assert.equal(Alpine.store('sidebar').collapsed, false);
    assert.equal(Alpine.store('appearance').value, 'system');
    assert.deepEqual(posts.find(([, body]) => 'sidebar' in body), ['/tercihler', { sidebar: 'expanded' }]);
    assert.equal(window.localStorage.getItem('acun-ui:panel'), null);

    page.remove();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));
    delete globalThis.fetch;
    window.localStorage.clear();
});

test('the CSRF token goes only to a preference address on this site', () => {
    const posts = [];
    globalThis.fetch = window.fetch = async (url, init) => { posts.push([url, init.headers['X-CSRF-TOKEN'] ?? null]); return { ok: true }; };
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = 't0ken';
    document.head.append(meta);

    const islands = ['/tercihler', 'http://localhost/tercihler', 'https://evil.example/tercihler', '//evil.example/tercihler'].map((preferenceUrl) => {
        const page = navigateTo({ preferenceUrl });
        Alpine.store('navbar').set('static');

        return page;
    });

    assert.deepEqual(posts, [
        ['/tercihler', 't0ken'],
        ['http://localhost/tercihler', 't0ken'],
        ['https://evil.example/tercihler', null],
        ['//evil.example/tercihler', null],
    ]);

    islands.forEach((page) => page.remove());
    meta.remove();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));
    delete globalThis.fetch;
    window.localStorage.clear();
});

test('the storefront\'s reset forgets only the saved colour', () => {
    window.localStorage.clear();
    Alpine.store('appearance').set('dark');
    Alpine.store('brand').set('green');
    Alpine.store('brand').forget();
    assert.deepEqual(saved('panel'), { mode: 'dark' });
    Alpine.store('appearance').set('light');
    window.localStorage.clear();
});

test('tokenColor resolves a token on the element through a hidden probe', () => {
    // jsdom does not resolve var(): the browser's getComputedStyle is stood in for here (the Chrome
    // contrast tests read tokens the same way).
    const real = globalThis.getComputedStyle;
    const seen = [];
    globalThis.getComputedStyle = (element) => {
        seen.push([element.parentElement, element.style.color, element.style.display]);
        return { color: 'rgb(1, 2, 3)' };
    };
    const section = document.createElement('section');
    document.body.append(section);

    assert.equal(tokenColor(section, '--ac-line'), 'rgb(1, 2, 3)');
    assert.deepEqual(seen, [[section, 'var(--ac-line)', 'none']]);
    assert.equal(section.children.length, 0, 'The probe is gone.');
    assert.equal(AcunUI.tokenColor, tokenColor);

    globalThis.getComputedStyle = real;
    section.remove();
});

/**
 * T3-D14: the "Default" swatch of acun:brand-color-picker showed the kit's purple in a theme of
 * another colour. It shows the theme's own primary colour (--ac-tone-primary, which the user's
 * colour never changes), and the custom colour starts from it while the default is chosen.
 */
test('the picker\'s default swatch shows the theme\'s own primary colour', async () => {
    const { mount, render, settle } = await import('./browser.mjs');
    const style = document.createElement('style');
    // A theme's tone (jsdom reads no @layer, so a plain :root rule here).
    style.textContent = ':root { --ac-tone-primary: #0f766e; }';
    document.head.append(style);
    Alpine.store('brand').set('green');

    await mount(render({ picker: '<acun:brand-color-picker />' }).picker);
    const swatch = document.querySelector('[role="radio"][title="Varsayılan"]');
    assert.match(swatch.getAttribute('style'), /background: (#0f766e|rgb\(15, 118, 110\))/);
    assert.equal(user()['--ac-user-fill'], '#008933', 'The chosen preset stays on <html>.');

    Alpine.store('brand').set('default');
    await settle();
    assert.equal(document.querySelector('input[type="color"]').value, '#0f766e');

    style.remove();
    window.localStorage.clear();
});
