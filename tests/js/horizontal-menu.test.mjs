import test from 'node:test';
import assert from 'node:assert/strict';
import { document, mount, press, render, settle } from './browser.mjs';

// The horizontal (top) menu: a group opens on hover and focus-within (CSS), and with Alpine
// also on a click and Arrow Down; Escape closes it and returns to its button, which says
// whether it is open (aria-expanded).

const html = render({
    menu: `
        <nav>
            <acun:nav.horizontal-group title="Raporlar">
                <acun:nav.sub-item href="/raporlar/satis" title="Satışlar" compact />
                <acun:nav.horizontal-sub-group title="Stok">
                    <acun:nav.sub-item href="/raporlar/stok" title="Depolar" compact />
                </acun:nav.horizontal-sub-group>
            </acun:nav.horizontal-group>
            <a id="after" href="/sonra">Sonra</a>
        </nav>`,
});

const button = () => document.querySelector('[aria-haspopup="true"]');
const panelOf = (trigger) => document.getElementById(trigger.getAttribute('aria-controls'));

test('a click opens the group and says so; the button names its panel', async () => {
    await mount(html.menu);
    assert.equal(button().getAttribute('aria-expanded'), 'false');
    assert.ok(panelOf(button()), 'aria-controls points at the panel.');

    button().click();
    await settle();
    assert.equal(button().getAttribute('aria-expanded'), 'true');
    assert.match(panelOf(button()).className, /!visible/);

    button().click();
    await settle();
    assert.equal(button().getAttribute('aria-expanded'), 'false', 'A second click closes it.');
});

test('Arrow Down opens it at its first link; Escape closes it and returns to the button', async () => {
    await mount(html.menu);
    button().focus();
    await settle();

    press('ArrowDown', {}, button());
    await settle();
    assert.equal(document.activeElement.getAttribute('href'), '/raporlar/satis');

    press('Escape');
    await settle();
    assert.equal(document.activeElement, button());
    assert.equal(button().getAttribute('aria-expanded'), 'false');
    assert.match(panelOf(button()).className, /!invisible/, 'Hidden although the focus is still in the group.');
});

test('Escape in the flyout closes only the flyout', async () => {
    await mount(html.menu);
    const sub = document.querySelectorAll('[aria-haspopup="true"]')[1];
    button().click();
    await settle();
    sub.focus();
    press('ArrowRight', {}, sub);
    await settle();
    assert.equal(document.activeElement.getAttribute('href'), '/raporlar/stok');

    press('Escape');
    await settle();
    assert.equal(document.activeElement, sub);
    assert.equal(sub.getAttribute('aria-expanded'), 'false');
    assert.equal(button().getAttribute('aria-expanded'), 'true', 'The top group stays open.');
});

test('Escape elsewhere on the page closes a group opened with the mouse', async () => {
    await mount(html.menu);
    button().click();
    await settle();
    assert.equal(button().getAttribute('aria-expanded'), 'true');

    press('Escape', {}, document.body);
    await settle();
    assert.equal(button().getAttribute('aria-expanded'), 'false');
});
