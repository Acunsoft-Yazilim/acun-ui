import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, document, mount, settle } from './browser.mjs';
import { component, morphComponent, renderLivewire } from './livewire.mjs';

// The ids the runtime gives the parts the server draws without one (a dropdown's menu, a popover's bubble and title,
// a tooltip, tabs, an accordion, a collapsible card) stay through a Livewire update: the morph pairs elements by id,
// and a part whose id it does not see in the new markup would be replaced by the server's copy (hidden, without the
// plugin's listeners), so a dropdown would no longer open after the component's first update.

const click = (element) => element.dispatchEvent(new document.defaultView.MouseEvent('click', { bubbles: true }));

test('after a Livewire update the parts keep their nodes, ids and ARIA links, and the dropdown still opens', async () => {
    const { page } = renderLivewire({ page: 'islemler' });
    await mount(page.first);
    await settle();

    const parts = () => ({
        menu: document.querySelector('#islem-menusu [data-slot="dropdown-content"]'),
        bubble: document.querySelector('#iade-bilgisi [data-slot="popover-content"]'),
        title: document.querySelector('#iade-bilgisi [data-slot="popover-title"]'),
        tip: document.querySelector('#kargo-ipucu [data-slot="tooltip-content"]'),
        tab: document.querySelector('#urun-sekmeleri [role="tab"]'),
        panel: document.querySelector('#urun-sekmeleri [role="tabpanel"]'),
        section: document.querySelector('#sss [data-slot="accordion-content"]'),
        body: document.querySelector('#siparis-karti [data-slot="card-body"]'),
    });
    const before = parts();
    Object.entries(before).forEach(([name, element]) => assert.ok(element?.id, `${name} has an id.`));
    const ids = Object.fromEntries(Object.entries(before).map(([name, element]) => [name, element.id]));
    const links = () => ({
        menu: document.querySelector('#islem-menusu [data-slot="dropdown-trigger"] button').getAttribute('aria-controls'),
        bubble: document.querySelector('#iade-bilgisi [data-slot="popover-trigger"] button').getAttribute('aria-controls'),
        labelled: parts().bubble.getAttribute('aria-labelledby'),
        described: document.querySelector('#kargo-ipucu button').getAttribute('aria-describedby'),
    });
    const linked = links();
    assert.deepEqual(linked, { menu: ids.menu, bubble: ids.bubble, labelled: ids.title, described: ids.tip });

    for (const round of [1, 2]) {
        morphComponent(component('islemler'), page.updates.islemler);
        await settle();

        const after = parts();
        Object.entries(after).forEach(([name, element]) => {
            assert.equal(element, before[name], `round ${round}: the morph keeps the ${name} node.`);
            assert.equal(element.id, ids[name], `round ${round}: and its id.`);
        });
        assert.deepEqual(links(), linked, `round ${round}: the ARIA links stay.`);

        const root = document.getElementById('islem-menusu');
        click(root.querySelector('[data-slot="dropdown-trigger"] button'));
        await settle();
        assert.equal(AcunUI.plugins.get(root, 'dropdown').content.isConnected, true);
        assert.equal(after.menu.hidden, false, `round ${round}: the menu opens.`);
        click(document.body);
        await settle();
        assert.equal(after.menu.hasAttribute('data-open'), false);
    }
});

test('a child id the server draws itself is not touched, and one on another kind of element is not copied', () => {
    const from = document.createElement('div');
    from.innerHTML = '<div data-slot="dropdown-content"></div><span></span>';
    const [content, span] = from.children;
    AcunUI.plugins.giveId(content, () => 'yeni-kimlik');
    AcunUI.plugins.giveId(span, () => 'span-kimlik');
    assert.equal(AcunUI.plugins.giveId(content, () => 'baska'), 'yeni-kimlik', 'An id once given stays.');

    const to = document.createElement('div');
    to.innerHTML = '<div data-slot="dropdown-content"></div><b></b>';
    AcunUI.plugins.keepOwned(from, to);
    assert.equal(to.children[0].id, 'yeni-kimlik');
    assert.equal(to.children[1].id, '', 'Not the same element: no id.');

    const server = document.createElement('div');
    server.innerHTML = '<div data-slot="dropdown-content" id="sunucu"></div>';
    AcunUI.plugins.keepOwned(from, server);
    assert.equal(server.children[0].id, 'sunucu', 'The server\'s own id wins.');
});
