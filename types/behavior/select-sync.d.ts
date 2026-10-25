import type { LivewireLike } from '../core/types.js';
/** @import { LivewireLike } from '../core/types.js' */
/**
 * Known browser/morph limitation: while a <select> is already in the DOM,
 * morph updates an <option>'s `selected` ATTRIBUTE but the browser does not
 * recompute the .value PROPERTY, so a box can keep showing the old selection.
 *
 * Off by default: config('acun-ui.select_sync') turns it on for every box
 * (selectSync in the runtime settings), a container with data-acun-select-sync
 * for the boxes inside it. When on, after a commit only the boxes of the
 * component that SENT it are synced (a nested component's boxes keep what the
 * user picked), from that component's current client state (ephemeral, which
 * the response has just updated). Nested paths ("editing.type") are supported;
 * a box whose value the component does not hold is left untouched.
 *
 * @param {LivewireLike} Livewire
 */
export declare function syncSelectValues(Livewire: LivewireLike): void;
