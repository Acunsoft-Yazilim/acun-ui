import test from 'node:test';
import assert from 'node:assert/strict';

// A page without acun:layout.head's settings (a Blade page with its own <head>): AcunUI.appearance has no choices
// until a setter is called, which starts them from the defaults (it did nothing before), and then works as on a
// page with settings.

globalThis.acunTestPage = { alpine: false };
const { AcunUI, document, window } = await import('./browser.mjs');

const root = document.documentElement;

test('without the settings island the first setter starts the choices from the defaults', () => {
    window.localStorage.clear();
    assert.equal(AcunUI.appearance.ready, false);
    assert.equal(AcunUI.appearance.get(), null);

    AcunUI.appearance.setBrandColor('#e11d48');
    assert.equal(AcunUI.appearance.ready, true);
    assert.equal(AcunUI.appearance.get().brandColor, '#e11d48');
    assert.notEqual(root.style.getPropertyValue('--ac-user-fill'), '', 'The colour is measured and written (--ac-user-*).');
    assert.notEqual(root.style.getPropertyValue('--ac-user-on-fill'), '');

    AcunUI.appearance.setMode('dark');
    assert.equal(root.dataset.theme, 'dark');
});
