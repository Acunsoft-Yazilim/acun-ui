export declare const VERSION = "2.2.0";
/** @import { AcunWindow } from './types.js' */
/**
 * Sets up window.AcunUI with the public API and runs setup() once per page.
 * Keeps what is already on the namespace (AcunUI.pro, when the PRO JS ran first).
 *
 * @template {object} T
 * @param {T} api
 * @param {(runtime: T & Record<string, any>) => void} setup
 * @returns {{ runtime: T & Record<string, any>, created: boolean }}
 */
export declare function createRuntime<T extends object>(api: T, setup: (runtime: T & Record<string, any>) => void): {
    runtime: T & Record<string, any>;
    created: boolean;
};
