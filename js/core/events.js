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
const LEGACY_COMMANDS = {
    [EVENTS.drawer.open]: 'open-drawer',
    [EVENTS.drawer.close]: 'close-drawer',
    [EVENTS.drawer.toggle]: 'toggle-drawer',
    [EVENTS.modal.open]: 'open-modal',
    [EVENTS.modal.close]: 'close-modal',
};

export function dispatch(name, detail = {}, target = window) {
    target.dispatchEvent(new CustomEvent(name, { detail, bubbles: true, composed: true, cancelable: true }));
}

/** Listens to a command under its name and its first name; returns the function that stops listening. */
export function listen(name, handler, target = window) {
    const names = [name, LEGACY_COMMANDS[name]].filter(Boolean);
    names.forEach((eventName) => target.addEventListener(eventName, handler));

    return () => names.forEach((eventName) => target.removeEventListener(eventName, handler));
}

/** True when a command is meant for the component with this name (no name: for every one). */
export function isFor(event, name, { all = false } = {}) {
    const target = event.detail?.name;

    return target === name || (all && (target === undefined || target === null));
}

/** The programmatic API of a component kind: AcunUI.drawer.open('order-4821'). */
export function commands(kind) {
    const names = EVENTS[kind];
    const api = {
        open: (name) => dispatch(names.open, { name }),
        close: (name) => dispatch(names.close, name === undefined ? {} : { name }),
    };

    if (names.toggle) api.toggle = (name) => dispatch(names.toggle, { name });

    return api;
}
