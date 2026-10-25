import type { LivewireLike } from '../core/types.js';
/**
 * Leaving with wire:navigate while a request is still pending morphed the
 * response into the old page's DETACHED component, and unscoped Alpine
 * expressions produced dozens of console errors. Morph is skipped for detached trees.
 */
/** @import { LivewireLike } from '../core/types.js' */
/**
 * @param {LivewireLike} Livewire
 */
export declare function skipDetachedMorphs(Livewire: LivewireLike): void;
