import test from 'node:test';
import assert from 'node:assert/strict';
import { AcunUI, Alpine, dispatch, document, mount, press, render, settle, window } from './browser.mjs';

// Smaller components in the browser.
//  - acun:toast-container: a toast dispatched straight from a component (not through
//    Toast::payload) gets the same link check as on the server.
//  - acun:accordion: the ids that tie headings to their content are unique on the page.
//  - acun:command-palette: filtering (Turkish case), group headings, the active option, Escape.

const html = render({
    container: '<acun:toast-container />',
    accordions: `
        <acun:accordion open="genel"><acun:accordion.item name="genel" title="Genel">Bir</acun:accordion.item></acun:accordion>
        <acun:accordion><acun:accordion.item name="genel" title="Genel">İki</acun:accordion.item></acun:accordion>`,
    carousel: `<acun:carousel :interval="5000" id="vitrin"><div>Bir</div><div>İki</div><button id="slayt-dugme">Üç</button></acun:carousel>`,
    palette: `<acun:command-palette :items="[
        ['label' => 'İadeler', 'href' => '/iadeler', 'group' => 'Satış'],
        ['label' => 'Siparişler', 'href' => '/siparisler', 'group' => 'Satış'],
        ['label' => 'Kargolar', 'href' => '/kargolar', 'group' => 'Lojistik', 'hint' => 'Gönderi takibi'],
        ['label' => 'Kötü', 'href' => 'javascript:alert(1)', 'group' => 'Lojistik'],
    ]" />`,
});

test('a toast link with an unsafe scheme is dropped, a safe one is shown', async () => {
    await mount(html.container);

    dispatch('toast', { message: 'Hazır', link: 'javascript:alert(1)', linkLabel: 'Aç' });
    dispatch('toast', { message: 'Hazır', link: ' JaVa\tScRiPt:alert(1)', linkLabel: 'Aç' });
    dispatch('toast', { message: 'Kayıt eklendi', link: '/kayitlar/5', linkLabel: 'Göster' });
    await settle();

    const links = [...document.querySelectorAll('a')].map((link) => link.getAttribute('href'));
    assert.deepEqual(links, ['/kayitlar/5']);
    assert.equal(document.body.textContent.match(/Hazır/g)?.length, 2, 'The message is still shown, without the link.');
});

test('two accordions with an item of the same name keep their headings and contents apart', async () => {
    await mount(html.accordions);

    const buttons = [...document.querySelectorAll('h3 button')];
    const regions = [...document.querySelectorAll('[role="region"]')];
    assert.equal(new Set(buttons.map((button) => button.id)).size, 2);
    buttons.forEach((button, index) => {
        assert.equal(button.getAttribute('aria-controls'), regions[index].id);
        assert.equal(regions[index].getAttribute('aria-labelledby'), button.id);
    });
});
test('the command palette filters once per keystroke and keeps its list readable to screen readers', async () => {
    await mount(html.palette);
    window.dispatchEvent(new window.CustomEvent('command-palette-open'));
    await settle();

    const input = document.querySelector('input[role="combobox"]');
    const visible = () => [...document.querySelectorAll('[role="option"]')].map((option) => option.querySelector('.font-medium').textContent.trim().split(/\s+/)[0]);
    // The first option of a group says the group's name to screen readers; the titles themselves are hidden from them.
    const groupNames = () => [...document.querySelectorAll('[role="option"] .sr-only')].filter((span) => span.style.display !== 'none').map((span) => span.textContent);
    const headings = () => [...document.querySelectorAll('[role="listbox"] p')].filter((p) => p.style.display !== 'none').map((p) => p.textContent);

    assert.deepEqual(visible(), ['İadeler', 'Siparişler', 'Kargolar', 'Kötü']);
    assert.deepEqual(headings(), ['Satış', 'Lojistik']);
    assert.deepEqual(groupNames(), ['Satış: ', 'Lojistik: ']);
    assert.ok([...document.querySelectorAll('[role="listbox"] > li')].every((li) => li.getAttribute('role') === 'none'), 'Only options in the listbox.');
    assert.equal(document.querySelector('a[href^="javascript"]'), null, 'An unsafe link becomes #.');

    input.value = 'İADE';
    input.dispatchEvent(new window.Event('input'));
    await settle();
    assert.deepEqual(visible(), ['İadeler'], 'Turkish case: İ and i match.');

    input.value = 'gönderi tak';
    input.dispatchEvent(new window.Event('input'));
    await settle();
    assert.deepEqual(visible(), ['Kargolar'], 'The hint is searched too.');
    assert.deepEqual(headings(), ['Lojistik']);
    assert.equal(input.getAttribute('aria-activedescendant'), document.querySelector('[role="option"]').id);
    assert.equal(input.getAttribute('aria-expanded'), 'true');

    press('Escape', {}, input);
    await settle();
    assert.equal(AcunUI.overlays.count, 0, 'Escape closes it.');
});
test('a toast stays while the pointer or the focus is on it; an error is announced as an alert', async () => {
    await mount(html.container);
    const container = document.querySelector('[aria-live="polite"]');
    const data = Alpine.$data(container);

    dispatch('toast', { type: 'error', message: 'Silinemedi' });
    dispatch('toast', { type: 'success', message: 'Kaydedildi' });
    await settle();

    const [error, success] = container.querySelectorAll('[role]');
    assert.deepEqual([error.getAttribute('role'), success.getAttribute('role')], ['alert', 'status']);

    const id = data.toasts[0].id;
    error.dispatchEvent(new window.MouseEvent('mouseenter'));
    assert.equal(data.timers.get(id).handle, null, 'Paused under the pointer.');
    error.dispatchEvent(new window.MouseEvent('mouseleave'));
    assert.ok(data.timers.get(id).handle, 'Running again.');

    data.toasts.map((toast) => toast.id).forEach((toastId) => data.dismiss(toastId));
    assert.equal(data.timers.size, 0);
});

test('an auto-advancing carousel stops under the keyboard focus, can be paused and counts added slides', async () => {
    await mount(html.carousel);
    const carousel = document.getElementById('vitrin');
    const data = Alpine.$data(carousel);
    assert.ok(data.timer, 'It advances by itself.');

    document.getElementById('slayt-dugme').dispatchEvent(new window.FocusEvent('focusin', { bubbles: true }));
    assert.equal(data.timer, null, 'Stopped while the focus is inside.');
    carousel.dispatchEvent(new window.FocusEvent('focusout', { relatedTarget: document.body }));
    assert.ok(data.timer);

    const pause = carousel.querySelector('[aria-pressed]');
    pause.click();
    await settle();
    assert.deepEqual([data.timer, pause.getAttribute('aria-pressed'), pause.getAttribute('aria-label')], [null, 'true', 'Oynat']);
    carousel.dispatchEvent(new window.MouseEvent('mouseleave'));
    assert.equal(data.timer, null, 'Paused stays paused.');

    carousel.querySelector('[x-ref="track"]').insertAdjacentHTML('beforeend', '<div>Dört</div>');
    await settle();
    assert.equal(data.count, 4);
    assert.equal(carousel.querySelectorAll('[aria-current]').length, 4);
    data.destroy();
});
test('a danger toast is an error toast; an unknown type is shown as info', async () => {
    await mount(html.container);
    const container = document.querySelector('[aria-live="polite"]');
    const data = Alpine.$data(container);

    // danger is the palette's name (acun:button, alert): it used to get no colour and no alert role.
    dispatch('toast', { type: 'danger', message: 'Silinemedi' });
    dispatch('toast', { type: 'duyuru', message: 'Bilgi' });
    dispatch('toast', { message: 'Türsüz' });
    await settle();

    assert.deepEqual(data.toasts.map((toast) => toast.type), ['error', 'info', 'info']);
    const [danger, unknown] = container.querySelectorAll('[role]');
    assert.equal(danger.getAttribute('role'), 'alert');
    assert.ok(danger.classList.contains('bg-red-600'));
    assert.equal(unknown.getAttribute('role'), 'status');
    assert.ok(unknown.classList.contains('bg-gray-900'));

    data.toasts.map((toast) => toast.id).forEach((toastId) => data.dismiss(toastId));
});

test('a toast type is read without regard to case, as on the server', async () => {
    await mount(html.container);
    const container = document.querySelector('[aria-live="polite"]');
    const data = Alpine.$data(container);

    // Toast::type('Danger') is error on the server; the container showed it as info.
    dispatch('toast', { type: 'Danger', message: 'Silinemedi' });
    dispatch('toast', { type: ' SUCCESS ', message: 'Kaydedildi' });
    dispatch('toast', { type: 'Amber', message: 'Dikkat' });
    await settle();

    assert.deepEqual(data.toasts.map((toast) => toast.type), ['error', 'success', 'warning']);
    assert.equal(container.querySelector('[role]').getAttribute('role'), 'alert');

    data.toasts.map((toast) => toast.id).forEach((toastId) => data.dismiss(toastId));
});

test('a download toast link downloads the file (download attribute), a plain one does not', async () => {
    await mount(html.container);
    const data = Alpine.$data(document.querySelector('[aria-live="polite"]'));

    dispatch('toast', { message: 'Rapor hazır', link: '/raporlar/5.xlsx', linkLabel: 'İndir', download: true });
    dispatch('toast', { message: 'Fatura hazır', link: '/faturalar/7.pdf', linkLabel: 'İndir', download: 'fatura-7.pdf' });
    dispatch('toast', { message: 'Kayıt eklendi', link: '/kayitlar/5', linkLabel: 'Göster' });
    await settle();

    const links = [...document.querySelectorAll('a')];
    assert.deepEqual(links.map((link) => [link.getAttribute('href'), link.getAttribute('download')]), [
        ['/raporlar/5.xlsx', ''],
        ['/faturalar/7.pdf', 'fatura-7.pdf'],
        ['/kayitlar/5', null],
    ]);

    data.toasts.map((toast) => toast.id).forEach((toastId) => data.dismiss(toastId));
});

test('a protocol-relative toast link is kept only when it stays on this site', async () => {
    await mount(html.container);
    const data = Alpine.$data(document.querySelector('[aria-live="polite"]'));

    dispatch('toast', { message: 'Bir', link: '//evil.example/giris', linkLabel: 'Aç' });
    dispatch('toast', { message: 'İki', link: '/\\evil.example', linkLabel: 'Aç' });
    dispatch('toast', { message: 'Üç', link: ' \t//evil.example', linkLabel: 'Aç' });
    dispatch('toast', { message: 'Dört', link: '//localhost/kayitlar', linkLabel: 'Göster' });
    await settle();

    assert.deepEqual([...document.querySelectorAll('a')].map((link) => link.getAttribute('href')), ['//localhost/kayitlar']);
    data.toasts.map((toast) => toast.id).forEach((toastId) => data.dismiss(toastId));
});
