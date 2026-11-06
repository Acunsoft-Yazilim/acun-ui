// acun:color-input in plain JavaScript (no Alpine): the browser's colour picker beside the #rrggbb text box.
//
//     <div data-acun-color-input>
//         <input type="color" data-slot="color-swatch" value="#c2410c">
//         <input type="text" data-acun-control data-slot="color-value" name="renk" wire:model="renk" value="#c2410c">
//     </div>
//
// The text box is the form control (wire:model, x-model, name). A colour picked fills it and sends input (the binding)
// and, when the picker closes, change; a value typed (#c2410c, #abc or c2410c) moves the picker, and when the box is
// left it is written out as #rrggbb (before the binding hears the change, so the server gets the full form and the
// box's pattern holds). A server render that writes a new value into the box (a Livewire morph of its value
// attribute) moves the picker too.

/** @import { PluginDefinition } from '../core/plugins.js' */

/** @param {string} value  #rrggbb in lower case, or null for anything else */
export function normalColor(value) {
    let hex = String(value).trim().replace(/^#/, '');
    if (/^[0-9a-f]{3}$/i.test(hex)) hex = hex.replace(/./g, '$&$&');

    return /^[0-9a-f]{6}$/i.test(hex) ? `#${hex.toLowerCase()}` : null;
}

/** @param {HTMLElement} root */
function createColorInput(root) {
    const swatch = /** @type {HTMLInputElement | null} */ (root.querySelector('[data-slot="color-swatch"]'));
    const text = /** @type {HTMLInputElement | null} */ (root.querySelector('[data-slot="color-value"]'));
    if (!swatch || !text) return { element: root, destroy() {} };

    const fromText = () => {
        const color = normalColor(text.value);
        if (color) swatch.value = color;
    };
    const send = (/** @type {string} */ type) => () => {
        text.value = swatch.value;
        text.dispatchEvent(new Event(type, { bubbles: true }));
    };
    const onInput = send('input');
    const onChange = send('change');
    // Captured on the root: it runs before the box's own change listeners (wire:model.change, .blur).
    const onTextChange = (/** @type {Event} */ event) => {
        if (event.target !== text) return;
        const color = normalColor(text.value);
        if (!color || color === text.value) return;
        text.value = color;
        text.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const observer = new MutationObserver(fromText);

    fromText();
    text.addEventListener('input', fromText);
    swatch.addEventListener('input', onInput);
    swatch.addEventListener('change', onChange);
    root.addEventListener('change', onTextChange, true);
    observer.observe(text, { attributes: true, attributeFilter: ['value'] });

    return {
        element: root,
        destroy() {
            observer.disconnect();
            text.removeEventListener('input', fromText);
            swatch.removeEventListener('input', onInput);
            swatch.removeEventListener('change', onChange);
            root.removeEventListener('change', onTextChange, true);
        },
    };
}

/** @type {PluginDefinition} */
export const colorInputPlugin = {
    attribute: 'data-acun-color-input',
    mount: createColorInput,
};
