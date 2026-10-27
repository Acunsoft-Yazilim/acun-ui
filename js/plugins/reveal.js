// The show / hide button of a password field (acun:input viewable), in plain JavaScript (no Alpine):
//
//     <div class="relative" data-acun-reveal>
//         <input data-acun-control type="password" …>
//         <button type="button" data-slot="reveal-toggle" aria-pressed="false" aria-label="Show password" class="group">…two icons, group-aria-pressed:…</button>
//     </div>
//
// The button switches the field between password and text (aria-pressed shows which); both are kept through a
// Livewire morph, so a re-render does not hide a password the user chose to see.

import { plugins } from '../core/plugins.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @param {HTMLElement} root
 */
function createReveal(root) {
    const input = /** @type {HTMLInputElement | null} */ (root.querySelector('input'));
    const button = /** @type {HTMLElement | null} */ (root.querySelector('[data-slot="reveal-toggle"]'));
    if (input) plugins.own(input, 'type');
    if (button) plugins.own(button, 'aria-pressed');

    const onClick = () => {
        if (!input || !button) return;
        const shown = input.type === 'password';
        input.type = shown ? 'text' : 'password';
        button.setAttribute('aria-pressed', shown ? 'true' : 'false');
    };
    button?.addEventListener('click', onClick);

    return {
        element: root,
        destroy() {
            button?.removeEventListener('click', onClick);
        },
    };
}

/** @type {PluginDefinition} */
export const revealPlugin = {
    attribute: 'data-acun-reveal',
    mount: createReveal,
};
