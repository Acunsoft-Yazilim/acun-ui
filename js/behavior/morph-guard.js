// Livewire fix: no morph into a page that wire:navigate has already left. Installed once by the runtime (core/livewire.js).

/**
 * Leaving with wire:navigate while a request is still pending morphed the
 * response into the old page's DETACHED component, and unscoped Alpine
 * expressions produced dozens of console errors. Morph is skipped for detached trees.
 */
export function skipDetachedMorphs(Livewire) {
    Livewire.hook('morph.updating', ({ component, skip }) => {
        if (!component.el.isConnected) {
            skip();
        }
    });
}
