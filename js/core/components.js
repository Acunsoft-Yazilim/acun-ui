// Heavy components in chunks of their own (the PRO combobox, file upload, form modal…).
//
// Their element carries data-acun-component="name". The package that owns the component
// defines how to load it (AcunUI.components.define); the module is imported the first time
// such an element is on the page, and only once, however many there are.
//
// No scan of the page: Alpine hands every element it starts to an init interceptor, on the
// first page load, after wire:navigate, for elements a Livewire morph adds and inside nested
// or lazy Livewire components alike. A deferred component's element is left to Alpine until
// its module is here (it registers the component's Alpine data), then Alpine starts it. The
// server's HTML shows in the meantime.
//
// A module that fails to load (the network dropped, a deploy replaced the chunk mid-session) is
// tried again: once by itself a moment later, when the browser is back online, after the next
// wire:navigate and when another such element appears. Its waiting elements start once it loads.

const ATTRIBUTE = 'data-acun-component';

// name → { load, defer }
const definitions = new Map();
// name → the promise of its import (dropped when it fails, so the next load tries again)
const loads = new Map();
const loaded = new Set();
// name → the elements Alpine left until the module is here
const waiting = new Map();
// name → the failed attempts so far
const failures = new Map();
const AUTOMATIC_RETRIES = 1;
const RETRY_DELAY = 1500;
let alpine = null;

export const components = {
    /**
     * load(): imports the module and registers what it needs. defer (default true): Alpine
     * waits for it before starting the element (its x-data is in the module); false for a
     * module that only has to be on the page (event delegation, no Alpine data).
     */
    define(name, load, { defer = true } = {}) {
        if (!definitions.has(name)) definitions.set(name, { load, defer });
    },

    /** Loads a component's module ahead of its element (once); resolves when it is ready. */
    load(name) {
        const definition = definitions.get(name);
        if (!definition) return Promise.reject(new Error(`[Acun UI] Unknown component: ${name}`));

        if (!loads.has(name)) {
            loads.set(name, Promise.resolve().then(definition.load).then(() => {
                loaded.add(name);
                failures.delete(name);
                startWaiting(name);
            }, (error) => {
                loads.delete(name);
                const attempts = (failures.get(name) ?? 0) + 1;
                failures.set(name, attempts);
                if (attempts <= AUTOMATIC_RETRIES) setTimeout(() => retry(name), RETRY_DELAY);
                throw error;
            }));
        }

        return loads.get(name);
    },

    isLoaded(name) {
        return loaded.has(name);
    },
};

const report = (name) => (error) => console.error(`[Acun UI] The JavaScript of the "${name}" component could not be loaded; it is tried again.`, error);

/** Starts the elements that waited for this module. */
function startWaiting(name) {
    const elements = waiting.get(name);
    waiting.delete(name);

    elements?.forEach((element) => {
        delete element._x_ignore;
        if (element.isConnected) alpine?.initTree(element);
    });
}

function retry(name) {
    if (loaded.has(name) || loads.has(name)) return;

    // Only for elements still on the page (a page left since then needs nothing).
    const elements = [...(waiting.get(name) ?? [])].filter((element) => element.isConnected);
    if (elements.length) waiting.set(name, new Set(elements));
    else waiting.delete(name);

    if (elements.length || document.querySelector(`[${ATTRIBUTE}="${CSS.escape(name)}"]`)) {
        components.load(name).catch(report(name));
    }
}

const retryFailed = () => [...failures.keys()].forEach(retry);

/** Called once by the runtime, before Alpine starts. */
export function deferLazyComponents(Alpine) {
    alpine = Alpine;
    window.addEventListener('online', retryFailed);
    document.addEventListener('livewire:navigated', retryFailed);

    Alpine.interceptInit((element) => {
        const name = element.getAttribute?.(ATTRIBUTE);
        if (!name || loaded.has(name)) return;

        const definition = definitions.get(name);
        if (!definition) return;

        // A Livewire component root starts in this same pass (Livewire initialises it here);
        // its lazy part belongs on an element inside it.
        if (!definition.defer || element.hasAttribute('wire:id')) {
            components.load(name).catch(report(name));
            return;
        }

        // Alpine skips an element with _x_ignore, and everything inside it, without marking it started.
        element._x_ignore = true;
        if (!waiting.has(name)) waiting.set(name, new Set());
        waiting.get(name).add(element);

        components.load(name).catch(report(name));
    });
}

/** The components the first page already has start loading before Alpine reaches them. */
export function loadComponentsOnPage(root = document) {
    for (const element of root.querySelectorAll(`[${ATTRIBUTE}]`)) {
        const name = element.getAttribute(ATTRIBUTE);
        if (definitions.has(name) && !loads.has(name)) components.load(name).catch(report(name));
    }
}
