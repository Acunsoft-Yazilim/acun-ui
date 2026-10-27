// The Alpine scope adapters/alpine.js gives every popover root (plugins/popover.js has the behaviour, with no
// Alpine): content and code written for the 2.x popover, an Alpine component with x-data on its root, keep
// reading and writing open (true opens, false closes) and calling closePopover(refocus).

import { plugins } from '../core/plugins.js';
import { followState } from '../adapters/alpine.js';

/** @import { AlpineLike } from '../core/types.js' */
/** @import { PopoverInstance } from '../plugins/popover.js' */

/**
 * @param {Element} root
 * @returns {PopoverInstance | null}
 */
const instanceOf = (root) => /** @type {PopoverInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'popover') ?? plugins.mount(root, 'popover')));

/**
 * The scope of a popover root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function popoverScope(root, Alpine) {
    const scope = Alpine.reactive({
        open: instanceOf(root)?.isOpen ?? false,
        /** @param {boolean} [refocus] */
        closePopover(refocus = false) {
            instanceOf(root)?.close({ refocus });
        },
    });
    const stopFollowing = followState(root, 'popover', (value) => {
        scope.open = value;
    });
    const stopWatching = Alpine.watch(() => scope.open, (value) => (value ? instanceOf(root)?.open() : instanceOf(root)?.close()));

    return {
        scope,
        stop() {
            stopFollowing();
            stopWatching();
        },
    };
}
