// The one place that talks to Livewire. Behaviours register their hooks here;
// each is registered once, when Livewire is there: right away when its script has
// already run (it runs before deferred and module scripts), otherwise on livewire:init.

/** @import { AcunWindow, LivewireLike } from './types.js' */

/** @type {LivewireLike | null} */
let livewire = null;
/** @type {((livewire: LivewireLike) => void)[]} */
const waiting = [];

/**
 * Runs callback(Livewire) once Livewire is on the page.
 *
 * @param {(livewire: LivewireLike) => void} callback
 */
export function onLivewire(callback) {
    if (livewire) {
        callback(livewire);
    } else {
        waiting.push(callback);
    }
}

/**
 * Livewire.hook(name, callback), registered once.
 *
 * @param {string} name
 * @param {(...args: any[]) => void} callback
 */
export function hook(name, callback) {
    onLivewire((instance) => instance.hook(name, callback));
}

/**
 * After a wire:navigate page change.
 *
 * @param {() => void} callback
 */
export function onNavigated(callback) {
    document.addEventListener('livewire:navigated', callback);
}

/** Called once by the runtime. */
export function connectLivewire() {
    const connect = () => {
        const instance = /** @type {AcunWindow} */ (window).Livewire;
        if (livewire || typeof instance?.hook !== 'function') return;

        livewire = instance;
        waiting.splice(0).forEach((callback) => callback(instance));
    };

    connect();

    if (!livewire) document.addEventListener('livewire:init', connect, { once: true });
}
