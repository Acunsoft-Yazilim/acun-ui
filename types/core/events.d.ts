export declare const EVENTS: {
    drawer: {
        open: string;
        close: string;
        toggle: string;
        opened: string;
        closed: string;
    };
    modal: {
        open: string;
        close: string;
        opened: string;
        closed: string;
    };
};
/**
 * Sends a bubbling, cancelable CustomEvent from target (default: window).
 *
 * @param {string} name
 * @param {object} [detail]
 * @param {EventTarget} [target]
 */
export declare function dispatch(name: string, detail?: object, target?: EventTarget): void;
/**
 * Listens to a command under its name and its first name; returns the function that stops listening.
 *
 * @param {string} name
 * @param {(event: CustomEvent<{ name?: string | null } | null | undefined>) => void} handler
 * @param {EventTarget} [target]
 * @returns {() => void}
 */
export declare function listen(name: string, handler: (event: CustomEvent<{
    name?: string | null;
} | null | undefined>) => void, target?: EventTarget): () => void;
/**
 * True when a command is meant for the component with this name (no name: for every one).
 *
 * @param {CustomEvent<{ name?: string | null } | null | undefined>} event
 * @param {string | null} name
 * @param {{ all?: boolean }} [options]
 */
export declare function isFor(event: CustomEvent<{
    name?: string | null;
} | null | undefined>, name: string | null, { all }?: {
    all?: boolean;
}): boolean;
export type Commands = {
    open: (name: string) => void;
    close: (name?: string) => void;
};
export declare function commands(kind: 'drawer'): Commands & {
    toggle: (name: string) => void;
};
export declare function commands(kind: 'modal'): Commands;
