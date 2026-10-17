import test from 'node:test';
import assert from 'node:assert/strict';
import { document } from './browser.mjs';

// The address rules of the runtime (core/url.js; the redirect rule in components/passkey.js), on a page at
// http://localhost/: the same as Acun\Ui\Support\Url (UrlTest) and acun:toast-container's links.

const { csrfHeader, isSameOrigin } = await import('../../js/core/url.js');
const { safeUrl } = await import('../../js/components/passkey.js');

test('safe addresses: http(s), mailto, tel and relative ones; //host only on this site', () => {
    for (const url of ['https://destek.ornek.com/a?b=c', 'http://ornek.com', '/raporlar', 'raporlar', '?sayfa=2', '#bolum', 'mailto:destek@ornek.com', 'tel:+905551112233', '//localhost/raporlar']) {
        assert.equal(safeUrl(url), url, url);
    }

    for (const url of ['javascript:alert(1)', ' JaVa\tScRiPt:alert(1)', 'data:text/html,x', 'vbscript:x', '//evil.example/giris', '/\\evil.example', '\\\\evil.example', '///evil.example', '\t//evil.example', '//localhost@evil.example', '//localhost:8080/x', '', null, 42]) {
        assert.equal(safeUrl(url), null, String(url));
    }
});

test('the same origin: relative addresses and this site\'s own absolute ones', () => {
    for (const url of ['/tercihler', 'tercihler', '?q=1', '', 'http://localhost/tercihler', '//localhost/tercihler']) {
        assert.equal(isSameOrigin(url), true, url);
    }

    for (const url of ['https://localhost/tercihler', 'http://localhost:8000/x', 'https://evil.example', '//evil.example', '/\\evil.example', 'http://[', null]) {
        assert.equal(isSameOrigin(url), false, String(url));
    }
});

test('the CSRF token header goes only with a request to this site', () => {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = 't0ken';
    document.head.append(meta);

    assert.deepEqual(csrfHeader('/passkeys/options'), { 'X-CSRF-TOKEN': 't0ken' });
    assert.deepEqual(csrfHeader('https://evil.example/passkeys/options'), {});
    assert.deepEqual(csrfHeader('//evil.example/passkeys/options'), {});

    meta.remove();
    assert.deepEqual(csrfHeader('/passkeys/options'), { 'X-CSRF-TOKEN': '' }, 'No token on the page: an empty one, as before.');
});
