import type { LivewireLike, WireLike } from '../core/types.js';
/** @import { AcunWindow, HostElement, LivewireLike, WireLike } from '../core/types.js' */
/**
 * Called once by the runtime, when Livewire is on the page (core/livewire.js).
 *
 * @param {LivewireLike} Livewire
 */
export declare function keepPluginStateThroughMorphs(Livewire: LivewireLike): void;
/**
 * Called once by the runtime, when Livewire is on the page: a modal the user closed runs its close-action.
 */
export declare function runCloseActions(): void;
/**
 * Keeps the appearance choices through wire:navigate. Livewire writes the new page's raw attributes to
 * <html> (data-theme back to the light member, the menu expanded, the user's colour gone): they go back
 * in the same step the page is swapped in, before the browser paints (onSwap; 'livewire:navigated'
 * comes a frame later, a flash in the dark mode), and again on 'livewire:navigated'. A page of another
 * scope brings its own choices. The first page with settings after pages without any starts them
 * (started() runs then).
 *
 * @param {() => void} started
 */
export declare function followAppearanceThroughNavigation(started: () => void): void;
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
