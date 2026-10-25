// The event names of the Acun UI components, on window.
//
// Commands: acun:{component}:{open|close|toggle} with { name }; close without a
// name closes every open one. Notifications: acun:{component}:{opened|closed}
// with { name }. The names of the first API (open-drawer, close-modal…) are still
// understood and still sent, so existing $dispatch / Livewire dispatch calls work.

export const EVENTS = {
    drawer: {
        open: 'acun:drawer:open',
        close: 'acun:drawer:close',
        toggle: 'acun:drawer:toggle',
        opened: 'acun:drawer:opened',
        closed: 'acun:drawer:closed',
    },
    modal: {
        open: 'acun:modal:open',
        close: 'acun:modal:close',
        opened: 'acun:modal:opened',
        closed: 'acun:modal:closed',
    },
};

// Commands under their first names.
/** @type {Record<string, string>} */
const LEGACY_COMMANDS = {
    [EVENTS.drawer.open]: 'open-drawer',
    [EVENTS.drawer.close]: 'close-drawer',
    [EVENTS.drawer.toggle]: 'toggle-drawer',
    [EVENTS.modal.open]: 'open-modal',
    [EVENTS.modal.close]: 'close-modal',
};

/**
 * Sends a bubbling, cancelable CustomEvent from target (default: window).
 *
 * @param {string} name
 * @param {object} [detail]
 * @param {EventTarget} [target]
 */
export function dispatch(name, detail = {}, target = window) {
    target.dispatchEvent(new CustomEvent(name, { detail, bubbles: true, composed: true, cancelable: true }));
}

/**
 * Listens to a command under its name and its first name; returns the function that stops listening.
 *
 * @param {string} name
 * @param {(event: CustomEvent<{ name?: string | null } | null | undefined>) => void} handler
 * @param {EventTarget} [target]
 * @returns {() => void}
 */
export function listen(name, handler, target = window) {
    const listener = /** @type {EventListener} */ (handler);
    const names = [name, LEGACY_COMMANDS[name]].filter(Boolean);
    names.forEach((eventName) => target.addEventListener(eventName, listener));

    return () => names.forEach((eventName) => target.removeEventListener(eventName, listener));
}

/**
 * True when a command is meant for the component with this name (no name: for every one).
 *
 * @param {CustomEvent<{ name?: string | null } | null | undefined>} event
 * @param {string | null} name
 * @param {{ all?: boolean }} [options]
 */
export function isFor(event, name, { all = false } = {}) {
    const target = event.detail?.name;

    return target === name || (all && (target === undefined || target === null));
}

/**
 * The programmatic API of a component kind: AcunUI.modal.open('order-4821'), AcunUI.modal.close()
 * (without a name: every open one).
 *
 * @typedef {object} Commands
 * @property {(name: string) => void} open
 * @property {(name?: string) => void} close
 */

/**
 * @overload
 * @param {'drawer'} kind
 * @returns {Commands & { toggle: (name: string) => void }}
 */
/**
 * @overload
 * @param {'modal'} kind
 * @returns {Commands}
 */
/**
 * @param {'drawer' | 'modal'} kind
 * @returns {Commands & { toggle?: (name: string) => void }}
 */
export function commands(kind) {
    /** @type {{ open: string, close: string, toggle?: string }} */
    const names = EVENTS[kind];
    /** @type {Commands & { toggle?: (name: string) => void }} */
    const api = {
        open: (name) => dispatch(names.open, { name }),
        close: (name) => dispatch(names.close, name === undefined ? {} : { name }),
    };

    const toggle = names.toggle;
    if (toggle) api.toggle = (name) => dispatch(toggle, { name });

    return api;
}
