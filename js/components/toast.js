// The Alpine scope adapters/alpine.js gives the toast container (plugins/toast.js has the behaviour, with no
// Alpine): code written for the 2.x container, an Alpine component, keeps reading toasts and timers and calling
// push(toast) and dismiss(id).

import { plugins } from '../core/plugins.js';

/** @import { ToastsInstance } from '../plugins/toast.js' */

/**
 * The scope of the toast container (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 */
export function toastsScope(root) {
    const instance = /** @type {ToastsInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'toasts') ?? plugins.mount(root, 'toasts')));

    return {
        scope: {
            get toasts() {
                return instance?.toasts ?? [];
            },
            timers: instance?.timers ?? new Map(),
            /** @param {Record<string, unknown>} toast */
            push: (toast) => instance?.push(toast),
            /** @param {number} id */
            dismiss: (id) => instance?.dismiss(id),
            /** @param {number} id */
            pause: (id) => instance?.pause(id),
            /** @param {number} id */
            resume: (id) => instance?.resume(id),
        },
        stop() {},
    };
}
