import test from 'node:test';
import assert from 'node:assert/strict';

// The theme on <html> (js/appearance/theme.js): the order of precedence of every
// setting, the primary colour, and the stores on a page with acun:layout.head.

globalThis.acunTestPage = {
    head: '<script type="application/json" data-acun-ui-config>{"theme":null,"brandColor":null,"sidebarMode":null,"navbarMode":"static","skin":null,"semiDark":false,"preferenceUrl":null,"defaults":{"theme":"dark","skin":"bordered","sidebar_mode":"collapsed"},"brandPresets":["default","green"],"forced":false}</script>',
};

const { Alpine, document, setColorScheme, window } = await import('./browser.mjs');
const { applyBrand, applyContentWidth, brandShades, initialPreferences } = await import('../../js/appearance/theme.js');

const root = document.documentElement;

test('the page or account wins, then the browser, then the application defaults', () => {
    window.localStorage.clear();
    const config = { theme: null, navbarMode: 'hidden', semiDark: null, defaults: { theme: 'dark', navbar_mode: 'static', semi_dark: true, sidebar_mode: 'collapsed' } };

    assert.deepEqual(initialPreferences(config), { theme: 'dark', brandColor: 'default', skin: 'default', contentWidth: 'full', navbarMode: 'hidden', sidebarCollapsed: true, semiDark: true });

    window.localStorage.setItem('appearance', 'light');
    window.localStorage.setItem('sidebarCollapsed', 'false');
    window.localStorage.setItem('semiDark', 'false');
    assert.equal(initialPreferences(config).theme, 'light', 'The browser beats the default.');
    assert.equal(initialPreferences(config).sidebarCollapsed, false);
    assert.equal(initialPreferences(config).semiDark, false);
    assert.equal(initialPreferences({ ...config, theme: 'system' }).theme, 'system', 'The page beats the browser.');
    window.localStorage.clear();
});

test('content width: boxed sets the container width on <html>, wide clears it', () => {
    window.localStorage.clear();
    assert.equal(initialPreferences({ defaults: { content_width: 'boxed' } }).contentWidth, 'boxed', 'The application default.');
    window.localStorage.setItem('contentWidth', 'full');
    assert.equal(initialPreferences({ defaults: { content_width: 'boxed' } }).contentWidth, 'full', 'The browser beats the default.');
    assert.equal(initialPreferences({ contentWidth: 'boxed', defaults: {} }).contentWidth, 'boxed', 'The page beats the browser.');
    window.localStorage.clear();

    applyContentWidth('boxed');
    assert.equal(root.dataset.contentWidth, 'boxed');
    applyContentWidth('full');
    assert.equal(root.dataset.contentWidth, undefined);
});

test('a custom primary colour sets the button colours; an unknown name falls back to the default', () => {
    applyBrand('#f9c74f', ['default', 'green']);
    assert.equal(root.dataset.brandColor, 'custom');
    assert.equal(root.style.getPropertyValue('--btn-primary-bg'), '#f9c74f');
    // The measured shades (packages/acun-ui/tests/brand-contrast.test.mjs checks them over many colours).
    const light = brandShades('#f9c74f');
    assert.equal(root.style.getPropertyValue('--brand-custom-600'), light.brand600, 'A light colour gets a darker tone for text.');
    assert.notEqual(light.brand600, '#f9c74f');
    assert.equal(root.style.getPropertyValue('--brand-custom-600-dark'), light.brand600Dark);
    assert.equal(root.style.getPropertyValue('--btn-primary-hover'), light.hover);
    assert.equal(root.style.getPropertyValue('--btn-primary-color'), light.text, 'White text would not be readable on it.');
    assert.notEqual(light.text, '#ffffff');
    assert.equal(root.style.getPropertyValue('--btn-primary-hover-color'), light.hoverText);
    assert.equal(root.style.getPropertyValue('--color-brand-600'), '', 'panel.css takes it from --brand-custom-600 (light or dark theme).');

    applyBrand('#1e3a8a', ['default', 'green']);
    assert.equal(root.style.getPropertyValue('--btn-primary-color'), '#ffffff', 'A dark colour keeps white text.');
    assert.equal(root.style.getPropertyValue('--brand-custom-600'), '#1e3a8a', 'It reads on white as it is.');
    assert.notEqual(root.style.getPropertyValue('--brand-custom-600-dark'), '#1e3a8a', 'The dark theme gets a lighter tone of it.');

    applyBrand('green', ['default', 'green']);
    assert.equal(root.dataset.brandColor, 'green');
    assert.equal(root.style.getPropertyValue('--btn-primary-bg'), '', 'A preset clears the custom values.');
    assert.equal(root.style.getPropertyValue('--btn-primary-color'), '');
    assert.equal(root.style.getPropertyValue('--btn-primary-hover-color'), '');
    assert.equal(root.style.getPropertyValue('--brand-custom-600'), '');

    applyBrand('purple', ['default', 'green']);
    assert.equal(root.dataset.brandColor, 'default');
});

test('with acun:layout.head the runtime registers the theme and menu stores from its settings', () => {
    assert.equal(Alpine.store('navbar').mode, 'static');
    assert.equal(Alpine.store('customizer').semiDark, false);
    assert.equal(Alpine.store('customizer').skin, 'bordered', 'The default, as nothing else was given.');
    assert.equal(Alpine.store('sidebar').collapsed, true);

    Alpine.store('sidebar').setCollapsed(false);
    assert.equal(root.dataset.sidebarCollapsed, 'false');
    assert.equal(window.localStorage.getItem('sidebarCollapsed'), 'false');

    Alpine.store('appearance').set('dark');
    assert.ok(root.classList.contains('dark'));
    Alpine.store('appearance').toggle();
    assert.equal(Alpine.store('appearance').value, 'light');
});

test('after a wire:navigate page change the stores put their values back on <html>', () => {
    Alpine.store('sidebar').setCollapsed(true);
    // Livewire writes the new page's raw <html> attributes.
    delete root.dataset.sidebarCollapsed;
    root.classList.add('dark');

    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

    assert.equal(root.dataset.sidebarCollapsed, 'true');
    assert.equal(root.classList.contains('dark'), false);
});

test('the system theme follows the operating system while the page is open', () => {
    Alpine.store('appearance').set('system');
    assert.equal(root.classList.contains('dark'), false);

    setColorScheme(true);
    assert.ok(root.classList.contains('dark'));

    setColorScheme(false);
    assert.equal(root.classList.contains('dark'), false);

    // A chosen theme stays as it is.
    Alpine.store('appearance').set('light');
    setColorScheme(true);
    assert.equal(root.classList.contains('dark'), false);
    setColorScheme(false);
});
test('the menu choice of the customizer is saved to the account, with the address of the current page', () => {
    const posts = [];
    globalThis.fetch = window.fetch = async (url, init) => { posts.push([url, JSON.parse(init.body)]); return { ok: true }; };

    // The first page had no preference address (its island says null): nothing is sent.
    Alpine.store('sidebar').set(true);
    assert.deepEqual(posts, []);

    // wire:navigate brings a page whose island has the address: from then on the choices are saved.
    const island = document.createElement('script');
    island.type = 'application/json';
    island.dataset.acunUiConfig = '';
    island.textContent = JSON.stringify({ preferenceUrl: '/tercihler', defaults: { sidebar_mode: 'expanded' }, brandPresets: ['default', 'green'], forced: false });
    document.head.append(island);
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

    Alpine.store('sidebar').set(false);
    Alpine.store('sidebar').toggle();
    assert.deepEqual(posts, [['/tercihler', { sidebar: 'expanded' }], ['/tercihler', { sidebar: 'collapsed' }]]);

    // Reset uses the new page's defaults and saves the menu too.
    posts.length = 0;
    Alpine.store('customizer').reset();
    assert.equal(Alpine.store('sidebar').collapsed, false);
    assert.deepEqual(posts.find(([, body]) => 'sidebar' in body), ['/tercihler', { sidebar: 'expanded' }]);

    island.remove();
    delete globalThis.fetch;
});

test('the CSRF token goes only to a preference address on this site', () => {
    const posts = [];
    globalThis.fetch = window.fetch = async (url, init) => { posts.push([url, init.headers['X-CSRF-TOKEN'] ?? null]); return { ok: true }; };
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = 't0ken';
    document.head.append(meta);

    const navigateTo = (preferenceUrl) => {
        const island = document.createElement('script');
        island.type = 'application/json';
        island.dataset.acunUiConfig = '';
        island.textContent = JSON.stringify({ preferenceUrl, defaults: {}, brandPresets: ['default', 'green'], forced: false });
        document.head.append(island);
        document.dispatchEvent(new window.CustomEvent('livewire:navigated'));

        return island;
    };

    const islands = ['/tercihler', 'http://localhost/tercihler', 'https://evil.example/tercihler', '//evil.example/tercihler'].map((url) => {
        const island = navigateTo(url);
        Alpine.store('navbar').set('static');

        return island;
    });

    assert.deepEqual(posts, [
        ['/tercihler', 't0ken'],
        ['http://localhost/tercihler', 't0ken'],
        ['https://evil.example/tercihler', null],
        ['//evil.example/tercihler', null],
    ]);

    islands.forEach((island) => island.remove());
    meta.remove();
    document.dispatchEvent(new window.CustomEvent('livewire:navigated'));
    delete globalThis.fetch;
});

/**
 * T3-D14: the "Default" swatch of acun:brand-color-picker showed the kit's purple in a theme of
 * another colour. It shows the page's own primary colour (the theme's --color-brand-600), and the
 * custom colour starts from it while the default is chosen.
 */
test('the picker\'s default swatch shows the theme\'s own primary colour', async () => {
    const { mount, render, settle } = await import('./browser.mjs');
    const style = document.createElement('style');
    // A theme's @theme value (jsdom reads no @layer, so a plain :root rule here) and a preset after it.
    style.textContent = ":root { --color-brand-600: #0f766e; } :root[data-brand-color='green'] { --color-brand-600: #008933; }";
    document.head.append(style);
    Alpine.store('brand').set('green');

    await mount(render({ picker: '<acun:brand-color-picker />' }).picker);
    const swatch = document.querySelector('[role="radio"][title="Varsayılan"]');
    assert.match(swatch.getAttribute('style'), /background: (#0f766e|rgb\(15, 118, 110\))/);
    assert.equal(root.dataset.brandColor, 'green', 'The chosen preset stays on <html>.');

    Alpine.store('brand').set('default');
    await settle();
    assert.equal(document.querySelector('input[type="color"]').value, '#0f766e');

    style.remove();
    Alpine.store('brand').set('default');
});
