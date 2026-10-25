// A page in headless Chrome driven over the DevTools protocol, with real input: trusted clicks and
// key presses (Escape, Tab) as a user's, for the tests that need the browser's own focus and event
// handling (plain-html-chrome.test.mjs). Skipped where no Chrome is installed (chrome.mjs finds one).
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { chrome } from './chrome.mjs';

export { chrome };

const KEYS = {
    Escape: { code: 'Escape', windowsVirtualKeyCode: 27 },
    Tab: { code: 'Tab', windowsVirtualKeyCode: 9 },
    Enter: { code: 'Enter', windowsVirtualKeyCode: 13 },
};

/**
 * Opens url (file:// or http) in a fresh headless Chrome; returns the page's helpers. Call close() at the end.
 * args: more Chrome switches (--ignore-certificate-errors for a local https site).
 */
export async function openPage(url, { width = 1280, height = 900, args = [] } = {}) {
    const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-cdp-'));
    const child = spawn(chrome, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--allow-file-access-from-files',
        '--remote-debugging-port=0', `--window-size=${width},${height}`, `--user-data-dir=${profile}`, ...args, 'about:blank',
    ], { stdio: ['ignore', 'ignore', 'pipe'] });

    const endpoint = await new Promise((resolve, reject) => {
        let output = '';
        const timer = setTimeout(() => reject(new Error('Chrome did not start in 30 s.')), 30000);
        child.stderr.on('data', (chunk) => {
            output += chunk;
            const found = output.match(/DevTools listening on (ws:\/\/\S+)/);
            if (found) {
                clearTimeout(timer);
                resolve(found[1]);
            }
        });
        child.on('exit', (code) => reject(new Error(`Chrome exited (${code}).`)));
    });

    const socket = new WebSocket(endpoint);
    await new Promise((resolve, reject) => {
        socket.onopen = resolve;
        socket.onerror = reject;
    });

    let id = 0;
    const pending = new Map();
    const events = [];
    socket.onmessage = (event) => {
        const message = JSON.parse(event.data);
        if (message.id && pending.has(message.id)) {
            const { resolve, reject, method } = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) reject(new Error(`${method}: ${message.error.message}`));
            else resolve(message.result);
        } else {
            events.push(message);
        }
    };
    const send = (method, params = {}, sessionId = undefined) => {
        const messageId = ++id;
        socket.send(JSON.stringify({ id: messageId, method, params, sessionId }));

        return new Promise((resolve, reject) => pending.set(messageId, { resolve, reject, method }));
    };

    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const page = (method, params) => send(method, params, sessionId);
    await page('Page.enable');
    await page('Runtime.enable');
    await page('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });

    const loaded = new Promise((resolve) => {
        const check = setInterval(() => {
            if (events.some((message) => message.sessionId === sessionId && message.method === 'Page.loadEventFired')) {
                clearInterval(check);
                resolve();
            }
        }, 20);
    });
    await page('Page.navigate', { url });
    await loaded;

    /** Runs expression in the page (awaits a promise) and returns its value. */
    const evaluate = async (expression) => {
        const { result, exceptionDetails } = await page('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (exceptionDetails) throw new Error(exceptionDetails.exception?.description ?? exceptionDetails.text);

        return result.value;
    };

    return {
        evaluate,
        /** A real mouse click in the middle of the element the selector finds (scrolled into view first). */
        async click(selector) {
            const box = await evaluate(`(() => { const element = document.querySelector(${JSON.stringify(selector)}); if (!element) throw new Error('No ${selector.replace(/'/g, '')}'); element.scrollIntoView({ block: 'center' }); const rect = element.getBoundingClientRect(); return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 }; })()`);
            await this.clickAt(box.x, box.y);
        },
        /** A real mouse click at a point of the viewport. */
        async clickAt(x, y) {
            for (const type of ['mouseMoved', 'mousePressed', 'mouseReleased']) {
                await page('Input.dispatchMouseEvent', { type, x, y, button: 'left', clickCount: 1 });
            }
        },
        /** A real key press: Escape, Tab (shift: Shift+Tab), Enter. */
        async press(key, { shift = false } = {}) {
            const { code, windowsVirtualKeyCode } = KEYS[key];
            const modifiers = shift ? 8 : 0;
            await page('Input.dispatchKeyEvent', { type: 'rawKeyDown', key, code, windowsVirtualKeyCode, modifiers });
            await page('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode, modifiers });
        },
        /** Console messages and uncaught errors so far. */
        get problems() {
            return events
                .filter((message) => message.sessionId === sessionId && (message.method === 'Runtime.exceptionThrown' || (message.method === 'Runtime.consoleAPICalled' && ['error', 'warning'].includes(message.params.type))))
                .map((message) => message.params.exceptionDetails?.exception?.description ?? message.params.args?.map((arg) => arg.value ?? arg.description).join(' '));
        },
        close() {
            socket.close();
            child.kill();
            setTimeout(() => fs.rmSync(profile, { recursive: true, force: true }), 500);
        },
    };
}

export const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
