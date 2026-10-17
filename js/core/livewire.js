// The one place that talks to Livewire. Behaviours register their hooks here;
// each is registered once, when Livewire is there: right away when its script has
// already run (it runs before deferred and module scripts), otherwise on livewire:init.

let livewire = null;
const waiting = [];

/** Runs callback(Livewire) once Livewire is on the page. */
export function onLivewire(callback) {
    if (livewire) {
        callback(livewire);
    } else {
        waiting.push(callback);
    }
}

/** Livewire.hook(name, callback), registered once. */
export function hook(name, callback) {
    onLivewire((instance) => instance.hook(name, callback));
}

/** After a wire:navigate page change. */
export function onNavigated(callback) {
    document.addEventListener('livewire:navigated', callback);
}

/** Called once by the runtime. */
export function connectLivewire() {
    const connect = () => {
        if (livewire || typeof window.Livewire?.hook !== 'function') return;

        livewire = window.Livewire;
        waiting.splice(0).forEach((callback) => callback(livewire));
    };

    connect();

    if (!livewire) document.addEventListener('livewire:init', connect, { once: true });
}
