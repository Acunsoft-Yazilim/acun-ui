import test from 'node:test';
import assert from 'node:assert/strict';

// The appearance choices on a page with acun:layout.head's settings but no Alpine (plain HTML, React, Vue):
// AcunUI.appearance applies, keeps and announces them by itself (appearance/preferences.js).

const PAGE = { scope: 'panel', theme: { key: 'default', light: 'light', dark: 'dark' }, targets: { light: '#fafafa', dark: '#1f1f1f' }, colors: { green: '#008933' }, mode: null, brandColor: null, sidebarMode: null, navbarMode: null, skin: null, semiDark: null, preferenceUrl: null, defaults: {}, forced: false };
globalThis.acunTestPage = { alpine: false, head: `<script type="application/json" data-acun-ui-config>${JSON.stringify(PAGE)}</script>` };
const { AcunUI, document, window } = await import('./browser.mjs');

const root = document.documentElement;

test('without Alpine, AcunUI.appearance changes the mode, the colour and the menu and tells its listeners', () => {
    window.localStorage.clear();
    assert.equal(window.Alpine, undefined);
    assert.equal(AcunUI.appearance.ready, true);
    assert.equal(AcunUI.appearance.scope, 'panel');

    const heard = [];
    const stop = AcunUI.appearance.subscribe((state, changed) => heard.push([changed, state[changed]]));

    AcunUI.appearance.setMode('dark');
    assert.equal(root.dataset.theme, 'dark');
    assert.ok(root.classList.contains('dark'));

    AcunUI.appearance.setBrandColor('green');
    assert.notEqual(root.style.getPropertyValue('--ac-user-fill'), '');

    AcunUI.appearance.setSidebarCollapsed(true);
    assert.equal(root.dataset.sidebarCollapsed, 'true');

    AcunUI.appearance.toggleMode();
    assert.equal(root.dataset.theme, 'light');

    stop();
    AcunUI.appearance.setSkin('bordered');

    assert.deepEqual(heard, [['mode', 'dark'], ['brandColor', 'green'], ['sidebarCollapsed', true], ['mode', 'light']], 'one call per change, none after stop()');
    assert.deepEqual(AcunUI.appearance.get(), { mode: 'light', brandColor: 'green', skin: 'bordered', contentWidth: 'full', navbarMode: 'fixed', sidebarCollapsed: true, semiDark: false });
    assert.deepEqual(JSON.parse(window.localStorage.getItem('acun-ui:panel')), { mode: 'light', brandColor: 'green', sidebar: 'collapsed', skin: 'bordered' });

    AcunUI.appearance.reset();
    assert.equal(window.localStorage.getItem('acun-ui:panel'), null);
    assert.equal(AcunUI.appearance.get().mode, 'system');
});
