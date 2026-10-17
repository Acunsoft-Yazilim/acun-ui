// Livewire's own morph for the behaviour tests: the page runs Livewire's Alpine (browser.mjs)
// but not Livewire.start(), so its updates are applied here as Livewire applies them
// (morph2 and getMorphConfig in vendor/livewire/livewire/dist/livewire.esm.js), with its
// morph plugin taken from the same bundle. renderLivewire() gives real Livewire output
// (render-livewire.php): a page's first render and each component's own update.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import { Alpine, document, window } from './browser.mjs';

const root = fileURLToPath(new URL('../../../../', import.meta.url));

if (typeof Alpine.morph !== 'function') {
    const bundle = fs.readFileSync(`${root}vendor/livewire/livewire/dist/livewire.esm.js`, 'utf8');
    const start = bundle.indexOf('"node_modules/@alpinejs/morph/dist/module.cjs.js"(exports, module) {');
    assert.ok(start > 0, 'The morph plugin is in Livewire\'s bundle.');
    const body = bundle.slice(bundle.indexOf('{', start) + 1, bundle.indexOf('\n  }\n});', start));
    const module = { exports: {} };
    new Function('exports', 'module', body)(module.exports, module);
    Alpine.plugin(module.exports.default ?? module.exports.morph);
}

/** { key: component } → { key: { first, updates: { name: html } } } (render-livewire.php). */
export function renderLivewire(pages) {
    const output = execFileSync('php', [fileURLToPath(new URL('./render-livewire.php', import.meta.url))], {
        cwd: root,
        input: JSON.stringify(pages),
    });

    return JSON.parse(output);
}

// jsdom's selectors do not match attribute names with a colon ([wire\:id]): the elements are
// looked through instead.
const withAttribute = (root, name) => [...root.querySelectorAll('*')].filter((element) => element.hasAttribute(name));

/** The root element of the component named name (wire:name) on the page. */
export function component(name) {
    return withAttribute(document, 'wire:name').find((element) => element.getAttribute('wire:name') === name) ?? null;
}

const isComponentRoot = (node) => node.nodeType === 1 && node.hasAttribute('wire:id');

/**
 * Applies a component's update (the html Livewire sent) to its root element el, as
 * Livewire's morph2 does: nested components keep their DOM (their stub is replaced
 * with it, and the morph leaves their root alone); morph.removing hooks may keep a node.
 */
export function morphComponent(el, html) {
    const id = el.getAttribute('wire:id');
    const wrapper = document.createElement(el.parentElement.tagName.toLowerCase());
    wrapper.innerHTML = html;
    const to = wrapper.firstElementChild;

    for (const name of ['wire:snapshot', 'wire:effects']) {
        if (el.hasAttribute(name)) to.setAttribute(name, el.getAttribute(name));
    }

    const existing = Object.fromEntries(withAttribute(el, 'wire:id').map((child) => [child.getAttribute('wire:id'), child]));
    withAttribute(to, 'wire:id').forEach((child) => {
        const kept = existing[child.getAttribute('wire:id')];
        if (kept && !child.hasAttribute('wire:snapshot')) child.replaceWith(kept.cloneNode(true));
    });

    Alpine.morph(el, to, {
        key: (node) => (node.nodeType !== 1 ? undefined : node.hasAttribute('wire:id') ? node.getAttribute('wire:id') : node.hasAttribute('wire:key') ? node.getAttribute('wire:key') : node.id),
        updating: (from, toEl, childrenOnly, skip) => {
            if (isComponentRoot(from) && from.getAttribute('wire:id') !== id) skip();
        },
        removing: (node, skip) => {
            if (node.nodeType === 1) window.Livewire.trigger('morph.removing', { el: node, component: null, skip });
        },
        lookahead: false,
    });
}
