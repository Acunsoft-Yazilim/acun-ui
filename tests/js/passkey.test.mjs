import test from 'node:test';
import assert from 'node:assert/strict';
import { document, mount, render, settle, sleep, window } from './browser.mjs';

// acun:passkey-button in the browser: the WebAuthn JSON in both directions, the application's
// two steps (URLs here; Livewire methods take the same path through $wire.call), the events and
// the messages. navigator.credentials is a stand-in: jsdom has no authenticator.

const { creationOptions, credentialJson, requestOptions } = await import('../../js/components/passkey.js');

const html = render({
    login: '<acun:passkey-button mode="login" options-url="/passkeys/options" action-url="/passkeys/login" id="login" />',
    register: '<acun:passkey-button mode="register" options-url="/passkeys/new" action-url="/passkeys" name-field="#passkey-name" id="register" />',
    otherSite: '<acun:passkey-button mode="login" options-url="https://auth.example.com/passkeys/options" action-url="https://auth.example.com/passkeys/login" id="other-site" />',
});

const bytes = (...values) => Uint8Array.from(values).buffer;
const decoded = (buffer) => Array.from(new Uint8Array(buffer));

// A credential as the browser returns it, without toJSON() (older browsers).
const credential = {
    id: 'AQID',
    rawId: bytes(1, 2, 3),
    type: 'public-key',
    authenticatorAttachment: 'platform',
    getClientExtensionResults: () => ({}),
    response: { clientDataJSON: bytes(123, 125), authenticatorData: bytes(9), signature: bytes(8, 7), userHandle: bytes(5) },
};

function fakeBrowser({ answer = credential, fail = null, redirect = null } = {}) {
    const requests = [];
    window.PublicKeyCredential = function PublicKeyCredential() {};
    // The module reads the globals (navigator, fetch), as a browser page does; here they are the page window's.
    const navigators = new Set([window.navigator, globalThis.navigator].filter(Boolean));
    const credentials = {
        get: async (options) => { requests.push(['get', options]); if (fail) throw fail; return answer; },
        create: async (options) => { requests.push(['create', options]); if (fail) throw fail; return { ...answer, response: { clientDataJSON: bytes(1), attestationObject: bytes(2), getTransports: () => ['internal'] } }; },
    };
    navigators.forEach((navigator) => Object.defineProperty(navigator, 'credentials', { configurable: true, value: credentials }));
    globalThis.PublicKeyCredential = window.PublicKeyCredential;
    globalThis.fetch = window.fetch = async (url, init) => {
        requests.push(['fetch', url, init.method, init.body ? JSON.parse(init.body) : null, init.headers]);
        if (url === '/passkeys/login' && fail?.status) {
            return { ok: false, status: fail.status, json: async () => ({ message: 'Bu geçiş anahtarı tanınmadı.' }), headers: { get: () => 'application/json' } };
        }
        const body = String(url).includes('options') || url === '/passkeys/new'
            ? { publicKey: { challenge: 'AQID', rpId: 'localhost', user: { id: 'BQ', name: 'ayse@example.com', displayName: 'Ayşe' }, allowCredentials: [{ id: 'AQID', type: 'public-key' }] } }
            : { redirect };
        return { ok: true, status: 200, json: async () => body, headers: { get: () => 'application/json' } };
    };
    return requests;
}

test('the server\'s base64url JSON becomes the buffers the browser needs, and back', () => {
    window.PublicKeyCredential = function PublicKeyCredential() {};

    const request = requestOptions({ publicKey: { challenge: 'AQID', allowCredentials: [{ id: 'BQY', type: 'public-key' }] } });
    assert.deepEqual(decoded(request.challenge), [1, 2, 3]);
    assert.deepEqual(decoded(request.allowCredentials[0].id), [5, 6]);

    const creation = creationOptions({ challenge: 'AQID', user: { id: 'BQ', name: 'ayse' }, excludeCredentials: [] });
    assert.deepEqual(decoded(creation.user.id), [5]);

    assert.deepEqual(credentialJson(credential), {
        id: 'AQID',
        rawId: 'AQID',
        type: 'public-key',
        authenticatorAttachment: 'platform',
        clientExtensionResults: {},
        response: { clientDataJSON: 'e30', authenticatorData: 'CQ', signature: 'CAc', userHandle: 'BQ' },
    });
});

test('signing in asks the options, the browser, then sends the answer and announces it', async () => {
    const requests = fakeBrowser();
    await mount(html.login);
    const done = [];
    document.addEventListener('acun:passkey:done', (event) => done.push(event.detail), { once: true });

    document.querySelector('#login').click();
    await sleep(20);
    await settle();

    assert.deepEqual(requests.map(([kind, ...rest]) => (kind === 'fetch' ? `fetch ${rest[1]} ${rest[0]}` : kind)), ['fetch POST /passkeys/options', 'get', 'fetch POST /passkeys/login']);
    assert.deepEqual(decoded(requests[1][1].publicKey.challenge), [1, 2, 3]);
    assert.equal(requests[2][3].response.signature, 'CAc');
    assert.deepEqual(done, [{ mode: 'login', result: { redirect: null } }]);
});

test('adding a passkey creates it and sends its name with it', async () => {
    const requests = fakeBrowser();
    await mount(`<input id="passkey-name" value="İş bilgisayarı">${html.register}`);

    document.querySelector('#register').click();
    await sleep(20);

    assert.equal(requests[1][0], 'create');
    assert.equal(requests[2][3].name, 'İş bilgisayarı');
    assert.equal(requests[2][3].response.attestationObject, 'Ag');
});

test('closing the browser\'s window shows nothing; a refused answer shows the server\'s message', async () => {
    const cancelled = Object.assign(new Error('closed'), { name: 'NotAllowedError' });
    fakeBrowser({ fail: cancelled });
    await mount(html.login);
    const failed = [];
    document.addEventListener('acun:passkey:failed', (event) => failed.push(event.detail.error));

    document.querySelector('#login').click();
    await sleep(20);
    await settle();
    assert.equal(document.querySelector('[role="alert"]').textContent.trim(), '');

    fakeBrowser({ fail: { status: 422 } });
    [window.navigator, globalThis.navigator].filter(Boolean).forEach((navigator) => { navigator.credentials.get = async () => credential; });
    document.querySelector('#login').click();
    await sleep(20);
    await settle();
    assert.equal(document.querySelector('[role="alert"]').textContent.trim(), 'Bu geçiş anahtarı tanınmadı.');
    assert.deepEqual(failed, ['NotAllowedError', 'HttpError']);
});

test('the CSRF token goes only to this site; only a safe redirect address is followed', async () => {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = 't0ken';
    document.head.append(meta);
    const signIn = async (markup, id, options) => {
        const requests = fakeBrowser(options);
        await mount(markup);
        document.querySelector(id).click();
        await sleep(20);
        await settle();

        return requests.filter(([kind]) => kind === 'fetch').map((request) => request[4]['X-CSRF-TOKEN'] ?? null);
    };

    assert.deepEqual(await signIn(html.login, '#login', { redirect: '#oturum-acildi' }), ['t0ken', 't0ken']);
    assert.equal(window.location.hash, '#oturum-acildi', 'A safe redirect address is followed.');

    window.location.hash = '';
    assert.deepEqual(await signIn(html.otherSite, '#other-site', { redirect: 'javascript:location.hash="#calisti"' }), [null, null]);
    assert.equal(window.location.hash, '');

    meta.remove();
});

test('a browser without passkeys disables the button and says why', async () => {
    delete window.PublicKeyCredential;
    delete globalThis.PublicKeyCredential;
    await mount(html.login);
    await settle();

    assert.equal(document.querySelector('#login').disabled, true);
    assert.equal(document.querySelector('#login').closest('[x-data]').querySelector('p[x-show]').style.display, '');
});
