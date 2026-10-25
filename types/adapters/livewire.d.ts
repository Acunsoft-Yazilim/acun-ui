import type { LivewireLike, WireLike } from '../core/types.js';
/** @import { AcunWindow, HostElement, LivewireLike, WireLike } from '../core/types.js' */
/**
 * Called once by the runtime, when Livewire is on the page (core/livewire.js).
 *
 * @param {LivewireLike} Livewire
 */
export declare function keepPluginStateThroughMorphs(Livewire: LivewireLike): void;
/**
 * The $wire of the Livewire component element is in, or null.
 *
 * @param {Element} element
 * @returns {WireLike | null}
 */
export declare function wireOf(element: Element): WireLike | null;
/**
 * Calls method on the Livewire component around element (a modal's close-action).
 *
 * @param {Element} element
 * @param {string} method
 * @returns {unknown}
 */
export declare function callComponent(element: Element, method: string): unknown;
