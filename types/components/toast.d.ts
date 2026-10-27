/** @import { ToastsInstance } from '../plugins/toast.js' */
/**
 * The scope of the toast container (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 */
export declare function toastsScope(root: HTMLElement): {
    scope: {
        readonly toasts: import("../plugins/toast.js").ToastData[];
        timers: Map<any, any>;
        /** @param {Record<string, unknown>} toast */
        push: (toast: Record<string, unknown>) => void | undefined;
        /** @param {number} id */
        dismiss: (id: number) => void | undefined;
        /** @param {number} id */
        pause: (id: number) => void | undefined;
        /** @param {number} id */
        resume: (id: number) => void | undefined;
    };
    stop(): void;
};
