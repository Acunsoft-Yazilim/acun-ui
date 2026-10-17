// Livewire fix (opt-in): a <select> shows the value its component holds after an update. Installed once by the runtime (core/livewire.js).

import { readConfig } from '../core/config.js';

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
 */
export function syncSelectValues(Livewire) {
    Livewire.hook('commit', ({ component, respond }) => {
        respond(() => {
            const everywhere = readConfig(component.el.ownerDocument)?.selectSync === true;

            component.el.querySelectorAll('select').forEach((select) => {
                if (!everywhere && !select.closest('[data-acun-select-sync]')) {
                    return;
                }

                const modelAttribute = [...select.attributes].find(
                    (attribute) => attribute.name === 'wire:model' || attribute.name.startsWith('wire:model.'),
                );

                if (!modelAttribute || select.closest('[wire\\:id]') !== component.el) {
                    return;
                }

                const rawValue = modelAttribute.value
                    .split('.')
                    .reduce((data, key) => (data === null || data === undefined ? undefined : data[key]), component.ephemeral);

                if (rawValue === undefined) {
                    return;
                }

                // A multiple select holds an array (a PHP array with
                // non-sequential keys arrives as an object); assigning it to
                // .value would deselect every option, so each option is set.
                if (select.multiple) {
                    const values = rawValue === null ? [] : (typeof rawValue === 'object' ? Object.values(rawValue) : [rawValue]);
                    const targetValues = values.map(String);

                    [...select.options].forEach((option) => {
                        const shouldSelect = targetValues.includes(option.value);

                        if (option.selected !== shouldSelect) {
                            option.selected = shouldSelect;
                        }
                    });

                    return;
                }

                const targetValue = rawValue === null ? '' : String(rawValue);

                if (select.value !== targetValue) {
                    select.value = targetValue;
                }
            });
        });
    });
}
