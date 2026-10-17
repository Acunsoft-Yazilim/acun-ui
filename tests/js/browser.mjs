// A page for the behaviour tests: jsdom running the components' real markup
// (rendered by render.php) with the Alpine that Livewire ships, the one the
// application runs. Each test file runs in its own process, so one page per file.
//
// A test file can shape the page before importing this module (dynamically):
//     globalThis.acunTestPage = { head: '<script type="application/json" data-acun-ui-config>…', boot: true, runtime: false };
// head: the <head> markup · boot: run the boot script (js/boot.js) · runtime: load the runtime (default true).
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const root = fileURLToPath(new URL('../../../../', import.meta.url));

// Renders { key: template } with Blade; returns { key: html }.
export function render(templates) {
    const output = execFileSync('php', [fileURLToPath(new URL('./render.php', import.meta.url))], {
        cwd: root,
        input: JSON.stringify(templates),
    });

    return JSON.parse(output);
}

const options = globalThis.acunTestPage ?? {};
const dom = new JSDOM(`<!doctype html><html><head>${options.head ?? ''}</head><body></body></html>`, {
    url: 'http://localhost/',
    pretendToBeVisual: true,
});
const { window } = dom;

// The viewport width for media queries (jsdom has no matchMedia): a phone unless a test says otherwise.
// dark: the operating system's colour scheme (prefers-color-scheme); setColorScheme() switches it.
// reducedMotion: prefers-reduced-motion.
export const viewport = { width: 390, dark: false, reducedMotion: false };
const widthLists = new Set();
const widthMatches = (query) => {
    const min = query.match(/min-width:\s*(\d+)px/);
    const max = query.match(/max-width:\s*(\d+)px/);

    return (!min || viewport.width >= Number(min[1])) && (!max || viewport.width <= Number(max[1]));
};
/** Resizes the window: the width media queries with listeners hear the change. */
export function setViewportWidth(width) {
    viewport.width = width;
    widthLists.forEach((list) => list.notify());
}
const schemeListeners = new Set();
export function setColorScheme(dark) {
    viewport.dark = dark;
    schemeListeners.forEach((listener) => listener({ matches: dark }));
}
window.matchMedia = (query) => {
    if (query.includes('prefers-reduced-motion')) {
        return { matches: viewport.reducedMotion, media: query, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {} };
    }

    if (query.includes('prefers-color-scheme')) {
        const dark = query.includes('dark');

        return {
            get matches() { return viewport.dark === dark; },
            media: query,
            addEventListener: (type, listener) => schemeListeners.add(listener),
            removeEventListener: (type, listener) => schemeListeners.delete(listener),
            addListener() {},
            removeListener() {},
        };
    }

    const listeners = new Set();
    const list = {
        get matches() { return widthMatches(query); },
        media: query,
        addEventListener: (type, listener) => { listeners.add(listener); widthLists.add(list); },
        removeEventListener: (type, listener) => listeners.delete(listener),
        addListener() {},
        removeListener() {},
        notify: () => listeners.forEach((listener) => listener({ matches: widthMatches(query), media: query })),
    };

    return list;
};

// Alpine evaluates expressions in the global scope: the page's globals go there,
// and the DOM classes replace Node's own (Event, CustomEvent…), which jsdom would not accept.
const own = new Set(['Event', 'EventTarget', 'CustomEvent', 'DOMException', 'navigator']);
for (const key of Object.getOwnPropertyNames(window)) {
    if (key in globalThis && !own.has(key)) continue;
    Object.defineProperty(globalThis, key, { configurable: true, writable: true, value: window[key] });
}

// jsdom has no layout: offsetParent is always null. Here an element is laid out
// unless it or an ancestor is hidden (x-show leaves display: none).
Object.defineProperty(window.HTMLElement.prototype, 'offsetParent', {
    configurable: true,
    get() {
        for (let element = this; element; element = element.parentElement) {
            if (element.hidden || element.style.display === 'none') return null;
        }

        return this.parentElement;
    },
});
// Alpine's click.outside skips an element of no size: a laid-out element gets one.
for (const property of ['offsetWidth', 'offsetHeight']) {
    Object.defineProperty(window.HTMLElement.prototype, property, {
        configurable: true,
        get() { return this.offsetParent === null ? 0 : 1; },
    });
}

// Livewire would start itself on DOMContentLoaded; the tests start Alpine alone, after the
// Acun UI runtime has registered its components (as on a page: the runtime runs first).
window.livewireScriptConfig = {};
const { Alpine } = await import(`${root}vendor/livewire/livewire/dist/livewire.esm.js`);
if (options.boot) await import(`${root}packages/acun-ui/js/boot.js`);
if (options.runtime !== false) await import(`${root}packages/acun-ui/js/index.js`);
Alpine.start();

export const AcunUI = window.AcunUI;
export { Alpine, window };
export const document = window.document;

export const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Lets Alpine react: mutation observers, effects, $nextTick and two animation frames.
export async function settle() {
    for (let i = 0; i < 3; i++) await sleep(20);
}

// Puts html on the page (in place of the previous test's) and lets Alpine start it.
export async function mount(html) {
    for (const element of [...document.body.children]) {
        Alpine.destroyTree(element);
        element.remove();
    }

    window.AcunUI?.scrollLock.reset();
    document.body.insertAdjacentHTML('beforeend', `<main>${html}</main>`);
    await settle();

    return document.body.lastElementChild;
}

export function press(key, options = {}, target = document.activeElement ?? document.body) {
    const event = new window.KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...options });
    target.dispatchEvent(event);

    return event;
}

export function dispatch(name, detail) {
    window.dispatchEvent(new window.CustomEvent(name, { detail }));
}
