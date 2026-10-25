import type { LivewireLike } from './types.js';
/**
 * Runs callback(Livewire) once Livewire is on the page.
 *
 * @param {(livewire: LivewireLike) => void} callback
 */
export declare function onLivewire(callback: (livewire: LivewireLike) => void): void;
/**
 * Livewire.hook(name, callback), registered once.
 *
 * @param {string} name
 * @param {(...args: any[]) => void} callback
 */
export declare function hook(name: string, callback: (...args: any[]) => void): void;
/**
 * After a wire:navigate page change.
 *
 * @param {() => void} callback
 */
export declare function onNavigated(callback: () => void): void;
/** Called once by the runtime. */
export declare function connectLivewire(): void;
