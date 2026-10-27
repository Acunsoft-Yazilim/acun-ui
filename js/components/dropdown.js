// The Alpine scope adapters/alpine.js gives every dropdown root (plugins/dropdown.js has the behaviour,
// with no Alpine): content and code written for the 2.x dropdown, an Alpine component with x-data on its
// root, keep reading and writing open (true opens, false closes) and calling openMenu(to), closeMenu(refocus),
// focusItem(to) and items().

import { plugins } from '../core/plugins.js';
import { followState } from '../adapters/alpine.js';

/** @import { AlpineLike } from '../core/types.js' */
/** @import { DropdownInstance } from '../plugins/dropdown.js' */

/**
 * @param {Element} root
 * @returns {DropdownInstance | null}
 */
const instanceOf = (root) => /** @type {DropdownInstance | null} */ (/** @type {unknown} */ (plugins.get(root, 'dropdown') ?? plugins.mount(root, 'dropdown')));

/**
 * The scope of a dropdown root (adapters/alpine.js).
 *
 * @param {HTMLElement} root
 * @param {AlpineLike} Alpine
 */
export function dropdownScope(root, Alpine) {
    const scope = Alpine.reactive({
        open: instanceOf(root)?.isOpen ?? false,
        /** @param {1 | -1 | 'first' | 'last'} [to] */
        openMenu(to) {
            instanceOf(root)?.open(to === undefined ? {} : { focus: to });
        },
        /** @param {boolean} [refocus] */
        closeMenu(refocus = false) {
            instanceOf(root)?.close({ refocus });
        },
        /** @param {1 | -1 | 'first' | 'last'} to */
        focusItem(to) {
            instanceOf(root)?.focusItem(to);
        },
        /** @returns {HTMLElement[]} */
        items() {
            return instanceOf(root)?.items() ?? [];
        },
    });
    const stopFollowing = followState(root, 'dropdown', (value) => {
        scope.open = value;
    });
    // Written by the content (open = false): the menu follows.
    const stopWatching = Alpine.watch(() => scope.open, (value) => (value ? instanceOf(root)?.open() : instanceOf(root)?.close()));

    return {
        scope,
        stop() {
            stopFollowing();
            stopWatching();
        },
    };
}
