// A page in headless Chrome driven over the DevTools protocol, with real input: trusted clicks and
// key presses (Escape, Tab) as a user's, for the tests that need the browser's own focus and event
// handling (plain-html-chrome.test.mjs). Skipped where no Chrome is installed (chrome.mjs finds one; on
// CI a missing Chrome fails instead). Every step has a time limit, so a stuck browser fails the test
// with a message instead of hanging the run, and close() waits for Chrome to exit.
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { chrome } from './chrome.mjs';

export { chrome };

// The limits: Chrome's start, a page load and one protocol call (an evaluate waits for its promise).
const START_TIMEOUT = 30000;
const LOAD_TIMEOUT = 30000;
const CALL_TIMEOUT = 15000;

/** promise, or a rejection with message after ms. */
function within(promise, ms, message) {
    let timer;
    return Promise.race([
        promise,
        new Promise((resolve, reject) => { timer = setTimeout(() => reject(new Error(message)), ms); }),
    ]).finally(() => clearTimeout(timer));
}

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

    const exited = new Promise((resolve) => child.once('exit', resolve));
    // Chrome's helper processes (crashpad…) may still write into the profile for a moment after the browser
    // exits (Linux): the removal is retried, and a temporary folder left behind does not fail the test.
    const removeProfile = () => {
        try {
            fs.rmSync(profile, { recursive: true, force: true, maxRetries: 10, retryDelay: 100 });
        } catch (error) {
            console.warn(`cdp.mjs: the Chrome profile ${profile} was not removed (${error.code ?? error.message}).`);
        }
    };
    /** Ends Chrome and waits for it (killed outright after 5 s), then removes its profile. */
    const stop = async () => {
        if (child.exitCode === null && child.signalCode === null) {
            child.kill();
            const killer = setTimeout(() => child.kill('SIGKILL'), 5000);
            await exited;
            clearTimeout(killer);
        }
        removeProfile();
    };

    let socket;
    try {
        const endpoint = await within(new Promise((resolve, reject) => {
            let output = '';
            child.stderr.on('data', (chunk) => {
                output += chunk;
                const found = output.match(/DevTools listening on (ws:\/\/\S+)/);
                if (found) resolve(found[1]);
            });
            child.once('exit', (code) => reject(new Error(`Chrome exited (${code}) before it started: ${output.split('\n').filter(Boolean).slice(-3).join(' | ')}`)));
            child.once('error', reject);
        }), START_TIMEOUT, `Chrome did not start in ${START_TIMEOUT / 1000} s.`);

        socket = new WebSocket(endpoint);
        await within(new Promise((resolve, reject) => {
            socket.onopen = resolve;
            socket.onerror = () => reject(new Error('The DevTools connection failed.'));
        }), START_TIMEOUT, 'The DevTools connection did not open.');
    } catch (error) {
        await stop();
        throw error;
    }

    let id = 0;
    const pending = new Map();
    const events = [];
    // Waiters for protocol events: { test(message), resolve }.
    const waiters = new Set();
    socket.onmessage = (event) => {
        const message = JSON.parse(event.data);
        if (message.id && pending.has(message.id)) {
            const { resolve, reject, method } = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) reject(new Error(`${method}: ${message.error.message}`));
            else resolve(message.result);
        } else {
            events.push(message);
            waiters.forEach((waiter) => {
                if (!waiter.test(message)) return;
                waiters.delete(waiter);
                waiter.resolve();
            });
        }
    };
    // Chrome gone (a crash) or the connection closed: every call still waiting fails at once.
    const failPending = (reason) => {
        pending.forEach(({ reject, method }) => reject(new Error(`${method}: ${reason}`)));
        pending.clear();
    };
    socket.onclose = () => failPending('the DevTools connection closed');
    child.once('exit', (code) => failPending(`Chrome exited (${code})`));

    const send = (method, params = {}, sessionId = undefined) => {
        const messageId = ++id;
        const answer = new Promise((resolve, reject) => pending.set(messageId, { resolve, reject, method }));
        socket.send(JSON.stringify({ id: messageId, method, params, sessionId }));

        return within(answer, CALL_TIMEOUT, `${method} got no answer in ${CALL_TIMEOUT / 1000} s.`).finally(() => pending.delete(messageId));
    };

    let sessionId;
    try {
        const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
        ({ sessionId } = await send('Target.attachToTarget', { targetId, flatten: true }));
        await send('Page.enable', {}, sessionId);
        await send('Runtime.enable', {}, sessionId);
        await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false }, sessionId);

        const loaded = new Promise((resolve) => waiters.add({ test: (message) => message.sessionId === sessionId && message.method === 'Page.loadEventFired', resolve }));
        await send('Page.navigate', { url }, sessionId);
        await within(loaded, LOAD_TIMEOUT, `${url} did not load in ${LOAD_TIMEOUT / 1000} s.`);
    } catch (error) {
        socket.close();
        await stop();
        throw error;
    }
    const page = (method, params) => send(method, params, sessionId);

    /** Runs expression in the page (awaits a promise) and returns its value. */
    const evaluate = async (expression) => {
        const { result, exceptionDetails } = await page('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (exceptionDetails) throw new Error(exceptionDetails.exception?.description ?? exceptionDetails.text);

        return result.value;
    };

    /** Polls expression in the page until it is truthy; fails with message after timeout ms. */
    const waitFor = async (expression, { timeout = 5000, message = expression } = {}) => {
        const end = Date.now() + timeout;
        while (!(await evaluate(expression))) {
            if (Date.now() > end) throw new Error(`Timed out after ${timeout} ms waiting for: ${message}`);
            await wait(10);
        }
    };

    return {
        evaluate,
        waitFor,
        /**
         * Waits until the page's transitions and animations are over: none running in two checks in a row,
         * each after two frames (a transition a click starts on the next frame is counted). Endless ones (a
         * spinner) do not count.
         */
        async settle({ timeout = 5000 } = {}) {
            const idle = `new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(() => resolve(document.getAnimations().every((animation) => animation.playState !== 'running' || animation.effect?.getComputedTiming().endTime === Infinity)))))`;
            await waitFor(`(${idle}).then(async (first) => first && (${idle}))`, { timeout, message: 'the page\'s transitions to end' });
        },
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
        /** Closes the page and Chrome, and waits for Chrome to exit. */
        async close() {
            socket.close();
            await stop();
        },
    };
}

export const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
