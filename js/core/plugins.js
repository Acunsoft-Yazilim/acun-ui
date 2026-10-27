// The plain-JavaScript components (plugins): one behaviour per component, bound to its markup
// through data-* attributes, the same in plain HTML, React, Vue, Blade and Livewire. No Alpine.
//
// A plugin is defined once, by this package or by another one (Acun Pro registers its own):
//
//     AcunUI.plugins.define('modal', {
//         attribute: 'data-acun-modal',          // names the element: data-acun-open="orders" finds it
//         mount(element, context) {              // once per element; returns its instance or a cleanup
//             …
//             return { open() {}, close() {}, toggle() {}, get isOpen() {}, destroy() {} };
//         },
//         setup(context) {},                     // optional: once, when defined (global listeners)
//     });
//
// A heavy one can come in a chunk of its own: the package imports the chunk when it needs it and
// defines the plugin then; a plugin defined after the page started mounts on the elements already there.
//
// Mounting is idempotent (one instance per element and plugin). The runtime mounts what the page
// has when it starts (before or after DOMContentLoaded), and a MutationObserver mounts elements
// added later and unmounts removed ones (an element moved within the page, as a Livewire morph
// or @persist does, keeps its instance). AcunUI.init(root) and AcunUI.destroy(root) do the same
// by hand: React and Vue call them on mount / unmount when the observer is off
// (AcunUI.observe(false), or window.AcunUI = { observe: false } before the script).
//
// Triggers need no mounting: one click listener on the document handles
//     data-acun-open="name" · data-acun-toggle="name"       open / toggle the component of that name
//     data-acun-close                                        the user closes the component it is in
//                                                            (a modal with a close guard asks first)
//     data-acun-close="name"                                 … or the one of that name
//     data-acun-close-now[="name"]                           closes without the guard (the modal's close-action still runs)
//     data-acun-dispatch="event-name"                        with one of the above: also sends that event from the
//                                                            trigger (bubbling to window) once it acted
// A trigger's aria-expanded (on itself, or on its direct child when it is a display: contents
// wrapper) follows its component.

import { isVisible } from './dom.js';

/**
 * What open() and toggle() take: opener is where the focus goes back when the component closes
 * (default: the element focused when it opened).
 *
 * @typedef {{ opener?: Element | null }} OpenOptions
 */

/**
 * What a plugin's mount() returns for an element: an object the page can drive (open, close…;
 * its destroy() runs on unmount). The members below are the ones the triggers and the commands use.
 *
 * @typedef {object} PluginInstance
 * @property {boolean} [isOpen]
 * @property {(options?: OpenOptions) => void} [open]
 * @property {(options?: { notify?: boolean }) => void} [close]
 * @property {(options?: OpenOptions) => void} [toggle]
 * @property {() => void} [requestClose]
 * @property {() => void} [destroy]
 */

/**
 * @typedef {object} PluginContext
 * @property {string} kind      the plugin's name
 * @property {unknown} options  what plugins.mount(element, kind, options) passed
 * @property {Plugins} plugins  the registry
 */

/**
 * The plugin registry (AcunUI.plugins).
 *
 * @typedef {object} Plugins
 * @property {(kind: string, definition: PluginDefinition) => Plugins} define  defines a plugin (the first definition of a name wins)
 * @property {(element: Element, kind: string, options?: unknown) => PluginInstance | null} mount  mounts one element by hand (idempotent)
 * @property {(element: Element, kind?: string) => void} unmount  unmounts one element (every plugin on it, or only kind)
 * @property {(element: Element | null | undefined, kind?: string) => PluginInstance | null} get  the instance of kind on element
 * @property {(element: Element, kind?: string) => boolean} isMounted
 * @property {(name: string | null | undefined, kind?: string) => PluginInstance[]} find  the instances named name on the page
 * @property {(kind: string) => PluginInstance[]} all  the mounted instances of kind on the page
 * @property {(kind: string) => boolean} has
 * @property {readonly string[]} names  the defined plugins' names
 * @property {string} selector  the selector of every defined plugin's elements
 * @property {(element: Element, ...names: string[]) => void} own  marks attributes a plugin manages on element
 * @property {(from: Element, to: Element) => void} keepOwned  copies the managed attributes onto a morph's new markup
 * @property {(name: string | null, expanded: boolean) => void} expand  sets aria-expanded on the triggers of name
 */

/** @typedef {(element: HTMLElement, context: PluginContext) => PluginInstance | (() => void) | null | undefined | void} MountFunction */

/**
 * @typedef {object} PluginDefinition
 * @property {string} [selector]   the elements it mounts on (default: [attribute])
 * @property {string} [attribute]  the attribute whose value names an element (data-acun-open="name" finds it)
 * @property {MountFunction} mount
 * @property {(context: { plugins: Plugins }) => void} [setup]  runs once, when the plugin is defined
 */

/** @typedef {{ selector: string, attribute?: string, mount: MountFunction }} Definition */
/** @typedef {{ instance: PluginInstance | null, cleanup: (() => void) | null }} MountRecord */

/** @type {Map<string, Definition>} */
const definitions = new Map();
/** @type {WeakMap<Element, Map<string, MountRecord>>} element → kind → its instance and cleanup */
const records = new WeakMap();
/** @type {WeakMap<Element, Set<string>>} element → the attributes a plugin manages on it (kept through Livewire morphs, adapters/livewire.js) */
const owned = new WeakMap();

let started = false;
let ready = false;
/** @type {MutationObserver | null} */
let observer = null;
let observing = false;

const TRIGGERS = '[data-acun-open], [data-acun-toggle], [data-acun-close], [data-acun-close-now]';

/**
 * @param {unknown} node
 * @returns {node is HTMLElement}
 */
const isElement = (node) => /** @type {Node | null | undefined} */ (node)?.nodeType === 1;
// A value inside an attribute selector's quotes ([data-acun-modal="…"]).
/** @param {string} value */
const quoted = (value) => String(value).replace(/["\\]/g, '\\$&');

/**
 * The elements of root (a document or an element, itself included) that match selector.
 *
 * @param {ParentNode | null} root
 * @param {string} selector
 * @returns {HTMLElement[]}
 */
function matching(root, selector) {
    if (!root || !selector) return [];
    const inside = /** @type {HTMLElement[]} */ ([...root.querySelectorAll(selector)]);

    return isElement(root) && root.matches(selector) ? [root, ...inside] : inside;
}

/**
 * @param {string} kind
 * @param {unknown} error
 */
function report(kind, error) {
    console.error(`[Acun UI] The "${kind}" component could not start.`, error);
}

/**
 * @param {HTMLElement} element
 * @param {string} kind
 * @param {MountFunction} mountFunction
 * @param {unknown} options
 * @returns {PluginInstance | null}
 */
function mountNow(element, kind, mountFunction, options) {
    let map = records.get(element);
    const existing = map?.get(kind);
    if (existing) return existing.instance;

    let result;
    try {
        result = mountFunction(element, { kind, options, plugins });
    } catch (error) {
        report(kind, error);
        return null;
    }

    /** @type {MountRecord} */
    const record = typeof result === 'function'
        ? { instance: null, cleanup: result }
        : { instance: result ?? null, cleanup: result?.destroy ? () => result.destroy?.() : null };

    // The mount itself may have mounted it already (a plugin that looks itself up).
    map = records.get(element);
    const mounted = map?.get(kind);
    if (mounted) {
        record.cleanup?.();
        return mounted.instance;
    }
    if (!map) records.set(element, (map = new Map()));
    map.set(kind, record);

    return record.instance;
}

/**
 * @param {unknown} element
 * @param {string} kind
 * @param {unknown} [options]
 * @returns {PluginInstance | null}
 */
function mount(element, kind, options) {
    const definition = definitions.get(kind);
    if (!definition || !isElement(element)) return null;

    return mountNow(element, kind, definition.mount, options);
}

/**
 * @param {Element} element
 * @param {string} [kind]
 */
function unmount(element, kind) {
    const map = records.get(element);
    if (map) {
        for (const name of kind ? [kind] : [...map.keys()]) {
            const record = map.get(name);
            if (!record) continue;
            map.delete(name);
            try {
                record.cleanup?.();
            } catch (error) {
                console.error(`[Acun UI] The "${name}" component could not stop cleanly.`, error);
            }
        }

        if (!map.size) records.delete(element);
    }
}

const allSelectors = () => [...definitions.values()].map((definition) => definition.selector).join(', ');

const page = () => (typeof document === 'undefined' ? null : document);

/**
 * Mounts every plugin's elements in root (a document or an element, itself included); nothing without a DOM.
 *
 * @template {ParentNode | null} T
 * @param {T} [root]
 * @returns {T}
 */
export function init(root = /** @type {T} */ (page())) {
    if (!root) return root;
    for (const [kind, definition] of definitions) {
        for (const element of matching(root, definition.selector)) mount(element, kind);
    }

    return root;
}

/**
 * Unmounts every plugin instance in root (itself included): listeners, timers, the scroll lock it held.
 *
 * @template {ParentNode | null} T
 * @param {T} [root]
 * @returns {T}
 */
export function destroy(root = /** @type {T} */ (page())) {
    const selector = allSelectors();
    if (!selector || !root) return root;

    for (const element of matching(root, selector)) unmount(element);
    if (isElement(root)) unmount(root);

    return root;
}

/**
 * Whether a node added or removed has plugin elements (itself or inside): most nodes a morph adds
 * (table rows, text) have none, so the observer does not query them once per plugin.
 *
 * @param {HTMLElement} node
 * @param {string} selector
 */
const holds = (node, selector) => node.matches(selector) || node.querySelector(selector) !== null;

/** @param {MutationRecord[]} mutations */
function onMutations(mutations) {
    const selector = allSelectors();
    if (!selector) return;

    /** @type {Set<HTMLElement>} */
    const removed = new Set();
    /** @type {Set<HTMLElement>} */
    const added = new Set();

    for (const mutation of mutations) {
        mutation.removedNodes.forEach((node) => isElement(node) && removed.add(node));
        mutation.addedNodes.forEach((node) => isElement(node) && added.add(node));
    }

    // Moved (removed and added again in the same batch) is still connected: it keeps its instance.
    removed.forEach((node) => !node.isConnected && holds(node, selector) && destroy(node));
    added.forEach((node) => node.isConnected && holds(node, selector) && init(node));
}

/**
 * Turns the observer on (default) or off; returns whether it is on. Off, the page's own code
 * calls AcunUI.init(element) and AcunUI.destroy(element).
 */
export function observe(enabled = true) {
    if (typeof MutationObserver === 'undefined') return false;

    if (enabled && !observing) {
        observer ??= new MutationObserver(onMutations);
        observer.observe(document.documentElement, { childList: true, subtree: true });
        observing = true;
    } else if (!enabled && observer && observing) {
        onMutations(observer.takeRecords());
        observer.disconnect();
        observing = false;
    }

    return observing;
}

// --- triggers -----------------------------------------------------------------------------

/**
 * The focusable element a trigger was used from (the button inside a display: contents wrapper).
 *
 * @param {HTMLElement} trigger
 * @returns {Element | null}
 */
function openerOf(trigger) {
    const active = document.activeElement;
    if (active && active !== document.body && trigger.contains(active)) return active;
    // A trigger hidden by its own click (a menu item: the menu closed and focused its button) cannot
    // take the focus back: the element focused now does.
    if (trigger.matches('button, a[href], [tabindex]') && (isVisible(trigger) || !active || active === document.body)) return trigger;

    return trigger.querySelector('button, a[href], [tabindex]') ?? active;
}

/**
 * The nearest mounted component around element that can be closed.
 *
 * @param {Element} element
 * @returns {PluginInstance | null}
 */
function closestClosable(element) {
    for (let node = /** @type {Element | null} */ (element); node; node = node.parentElement) {
        const map = records.get(node);
        if (!map) continue;
        for (const { instance } of map.values()) {
            if (typeof instance?.close === 'function') return instance;
        }
    }

    return null;
}

/** @param {MouseEvent} event */
function onClick(event) {
    const trigger = isElement(event.target) ? /** @type {HTMLElement | null} */ (event.target.closest(TRIGGERS)) : null;
    if (!trigger) return;

    act(trigger);
    // As Alpine's $dispatch(name) sends it (no detail): a confirmation's "{name}-confirmed".
    const notice = trigger.getAttribute('data-acun-dispatch');
    if (notice) trigger.dispatchEvent(new CustomEvent(notice, { bubbles: true, composed: true, cancelable: true }));
}

/** @param {HTMLElement} trigger */
function act(trigger) {
    const open = trigger.getAttribute('data-acun-open');
    const toggle = open === null ? trigger.getAttribute('data-acun-toggle') : null;
    const name = open ?? toggle;
    if (name !== null) {
        const targets = plugins.find(name).filter((instance) => typeof instance.open === 'function');
        if (!targets.length) console.warn(`[Acun UI] Nothing named "${name}" to open on this page.`, trigger);

        const opener = openerOf(trigger);
        targets.forEach((instance) => (toggle !== null && instance.isOpen ? instance.close?.() : instance.open?.({ opener })));
        return;
    }

    const now = trigger.hasAttribute('data-acun-close-now');
    const attribute = now ? 'data-acun-close-now' : 'data-acun-close';
    // A boolean attribute may repeat its name (data-acun-close="data-acun-close", as Blade writes one): no name.
    const value = trigger.getAttribute(attribute);
    const target = value && value !== attribute ? value : null;
    const targets = target ? plugins.find(target) : [closestClosable(trigger)].filter((instance) => instance !== null);

    if (!targets.length) {
        console.error(`[Acun UI] ${attribute} is not inside a component it can close${target ? ` and nothing is named "${target}"` : ''}.`, trigger);
        return;
    }

    targets.forEach((instance) => (!now && instance.requestClose ? instance.requestClose() : instance.close?.()));
}

/**
 * Runs once, from the runtime: mounts what the page has now (or at DOMContentLoaded while the page
 * is still parsing), starts the observer unless it is turned off, and handles the triggers.
 *
 * @param {{ observe?: boolean }} [options]
 */
export function start({ observe: watch = true } = {}) {
    if (started) return;
    started = true;
    document.addEventListener('click', onClick);

    const go = () => {
        ready = true;
        init(document);
        if (watch) observe(true);
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', go, { once: true });
    else go();
}

// --- the registry ----------------------------------------------------------------------------

/** @type {Plugins} */
export const plugins = {
    /**
     * Defines a plugin (the first definition of a name wins); see PluginDefinition. Returns the
     * registry, so definitions chain.
     *
     * @param {string} kind
     * @param {PluginDefinition} definition
     * @returns {Plugins}
     */
    define(kind, definition) {
        if (definitions.has(kind)) return plugins;

        const selector = definition?.selector ?? (definition?.attribute ? `[${definition.attribute}]` : null);
        if (!selector || typeof definition.mount !== 'function') {
            throw new Error(`[Acun UI] plugins.define('${kind}') needs a selector (or attribute) and mount().`);
        }

        /** @type {Definition} */
        const entry = { selector, mount: definition.mount };
        if (definition.attribute) entry.attribute = definition.attribute;
        definitions.set(kind, entry);


        try {
            definition.setup?.({ plugins });
        } catch (error) {
            report(kind, error);
        }

        if (ready) for (const element of matching(document, selector)) mount(element, kind);

        return plugins;
    },

    /**
     * Mounts one element by hand (idempotent); returns its instance. options go to mount().
     *
     * @param {Element} element
     * @param {string} kind
     * @param {unknown} [options]
     * @returns {PluginInstance | null}
     */
    mount(element, kind, options) {
        return mount(element, kind, options);
    },

    /**
     * Unmounts one element (every plugin on it, or only kind).
     *
     * @param {Element} element
     * @param {string} [kind]
     */
    unmount(element, kind) {
        unmount(element, kind);
    },

    /**
     * The instance of kind on element (the first one when kind is left out), or null.
     *
     * @param {Element | null | undefined} element
     * @param {string} [kind]
     * @returns {PluginInstance | null}
     */
    get(element, kind) {
        const map = element ? records.get(element) : undefined;
        if (!map) return null;
        if (kind) return map.get(kind)?.instance ?? null;

        return [...map.values()].find((record) => record.instance)?.instance ?? null;
    },

    /**
     * Whether kind (or any plugin) is mounted on element.
     *
     * @param {Element} element
     * @param {string} [kind]
     */
    isMounted(element, kind) {
        const map = records.get(element);

        return Boolean(map && (kind ? map.has(kind) : map.size));
    },

    /**
     * The instances named name (data-acun-modal="name"…) on the page, of kind or of every named
     * plugin; an element not mounted yet is mounted now.
     *
     * @param {string | null | undefined} name
     * @param {string} [kind]
     * @returns {PluginInstance[]}
     */
    find(name, kind) {
        if (name === undefined || name === null) return [];
        /** @type {PluginInstance[]} */
        const found = [];

        for (const [key, definition] of definitions) {
            if ((kind && key !== kind) || !definition.attribute) continue;
            for (const element of document.querySelectorAll(`[${definition.attribute}="${quoted(name)}"]`)) {
                const instance = mount(element, key);
                if (instance) found.push(instance);
            }
        }

        return found;
    },

    /**
     * The mounted instances of kind on the page.
     *
     * @param {string} kind
     * @returns {PluginInstance[]}
     */
    all(kind) {
        const definition = definitions.get(kind);
        if (!definition) return [];

        return [...document.querySelectorAll(definition.selector)]
            .map((element) => records.get(element)?.get(kind)?.instance)
            .filter((instance) => instance !== null && instance !== undefined);
    },


    /** @param {string} kind */
    has(kind) {
        return definitions.has(kind);
    },

    /** The defined plugins' names. */
    get names() {
        return [...definitions.keys()];
    },

    /** The selector of every defined plugin's elements (adapters/alpine.js makes them Alpine roots). */
    get selector() {
        return allSelectors();
    },

    /**
     * Marks attributes of element as managed by a plugin (hidden, data-open, aria-expanded…):
     * a Livewire morph keeps their current values instead of the server's (adapters/livewire.js).
     *
     * @param {Element} element
     * @param {...string} names
     */
    own(element, ...names) {
        if (!isElement(element)) return;
        let set = owned.get(element);
        if (!set) owned.set(element, (set = new Set()));
        names.forEach((name) => set.add(name));
    },

    /**
     * Copies the managed attributes of from onto to (the new markup of a morph), before it is applied.
     *
     * @param {Element} from
     * @param {Element} to
     */
    keepOwned(from, to) {
        const names = owned.get(from);
        if (!names || !isElement(to)) return;

        for (const name of names) {
            const value = from.getAttribute(name);
            if (value === null) to.removeAttribute(name);
            else to.setAttribute(name, value);
        }
    },

    /**
     * Sets aria-expanded on the triggers of name (data-acun-open / data-acun-toggle) that have
     * one: on the trigger, or on its direct child for a wrapper. One bound by Alpine is left alone.
     *
     * @param {string | null} name
     * @param {boolean} expanded
     */
    expand(name, expanded) {
        if (name === null) return;
        const value = quoted(name);

        for (const trigger of document.querySelectorAll(`[data-acun-open="${value}"], [data-acun-toggle="${value}"]`)) {
            const target = trigger.hasAttribute('aria-expanded') ? trigger : trigger.querySelector(':scope > [aria-expanded]');
            if (!target || target.hasAttribute(':aria-expanded') || target.hasAttribute('x-bind:aria-expanded')) continue;

            target.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            plugins.own(target, 'aria-expanded');
        }
    },
};

