// acun:command-palette, in plain JavaScript (no Alpine). Markup (the component renders it):
//
//     <div data-acun-command-palette data-shortcut="k">
//         <button type="button" data-slot="command-palette-trigger" aria-haspopup="dialog">Search…</button>
//         <div data-slot="command-palette-layer" hidden class="… data-open:pointer-events-auto">
//             <div data-slot="command-palette-backdrop" data-acun-close aria-hidden="true"></div>
//             <div data-slot="command-palette-panel" role="dialog" aria-modal="true" aria-label="Search…">
//                 <input data-slot="command-palette-input" role="combobox" aria-controls="…" aria-expanded="true">
//                 <ul id="…" role="listbox">
//                     <li role="none" data-group="Satış">
//                         <p data-slot="command-palette-group" aria-hidden="true">Satış</p>
//                         <a role="option" id="…" href="/siparisler" data-search="Siparişler Satış">
//                             <span data-slot="command-palette-group-name" class="sr-only">Satış: </span>Siparişler
//                         </a>
//                     </li>
//                     <li role="none" data-slot="command-palette-empty" hidden>No results found</li>
//                 </ul>
//                 <p role="status" data-slot="command-palette-status" data-results=":count results" data-empty="No results found"></p>
//             </div>
//         </div>
//     </div>
//
// The trigger, ⌘K / Ctrl+K (data-shortcut="k") and a command-palette-open event on window open it; the
// field filters the options as you type (Turkish case-insensitive: the page's language decides İ/i), a
// group's heading shows above its first match only, the arrow keys move the highlighted option (the focus
// stays in the field, screen readers follow aria-activedescendant), Enter and a click go to the option
// (wire:navigate within the application when Livewire is on the page). Escape in the field, the backdrop and
// the shortcut close it. The page scroll lock, Tab and the focus restore come from the overlay manager.
// The layer moves to the end of body on mount (a top bar's z-index and position would otherwise hold the
// fixed layer under the page's other layers) and comes back on destroy.

import { overlays } from '../behavior/overlay-manager.js';
import { ownPart } from '../core/dom.js';
import { plugins } from '../core/plugins.js';
import { enter, leave } from '../core/transition.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

const ATTRIBUTE = 'data-acun-command-palette';

/**
 * A command palette on the page (AcunUI.plugins.get(element, 'command-palette')).
 *
 * @typedef {object} CommandPaletteInstance
 * @property {HTMLElement} element
 * @property {boolean} isOpen
 * @property {() => void} open
 * @property {() => void} close
 * @property {() => void} toggle
 * @property {(query: string) => void} filter  shows the options that match (the field's value)
 * @property {() => void} destroy
 */

/** @param {string} text */
const fold = (text) => text.toLocaleLowerCase(document.documentElement.lang || undefined);

/**
 * @param {HTMLElement} root
 * @returns {CommandPaletteInstance}
 */
function createCommandPalette(root) {
    const part = (/** @type {string} */ slot) => ownPart(root, ATTRIBUTE, slot);
    const trigger = part('command-palette-trigger');
    const layer = part('command-palette-layer');
    const panel = part('command-palette-panel');
    const input = /** @type {HTMLInputElement | null} */ (part('command-palette-input'));
    const empty = part('command-palette-empty');
    const status = part('command-palette-status');
    const options = /** @type {HTMLElement[]} */ ([...root.querySelectorAll('[role="option"]')]);
    const search = new Map(options.map((option) => [option, fold(option.dataset.search ?? option.textContent ?? '')]));
    const shortcut = root.dataset.shortcut?.toLowerCase();
    const parts = /** @type {HTMLElement[]} */ ([layer, part('command-palette-backdrop'), panel].filter(Boolean));

    let isOpen = false;
    /** @type {HTMLElement[]} */
    let results = options;
    let highlighted = 0;
    let cancelLeave = () => {};

    if (layer) {
        plugins.own(layer, 'hidden', 'data-open');
        document.body.append(layer);
    }
    parts.forEach((element) => plugins.own(element, 'data-open'));
    options.forEach((option) => plugins.own(option, 'aria-selected', 'data-highlighted'));

    const highlight = (/** @type {number} */ index, { scroll = false } = {}) => {
        highlighted = Math.max(0, Math.min(index, results.length - 1));
        results.forEach((option, i) => {
            option.setAttribute('aria-selected', String(i === highlighted));
            option.toggleAttribute('data-highlighted', i === highlighted);
        });
        const current = results[highlighted];
        if (current) input?.setAttribute('aria-activedescendant', current.id);
        else input?.removeAttribute('aria-activedescendant');
        if (scroll) current?.scrollIntoView?.({ block: 'nearest' });
    };

    // Once per keystroke: every option shows or hides; a group's heading (and its name for screen readers)
    // stays on its first visible option only.
    const filter = (/** @type {string} */ query) => {
        const term = fold(query.trim());
        results = term === '' ? options : options.filter((option) => search.get(option)?.includes(term));
        /** @type {string | null} */
        let previousGroup = null;
        options.forEach((option) => {
            const row = /** @type {HTMLElement | null} */ (option.closest('li'));
            const shown = results.includes(option);
            row?.toggleAttribute('hidden', !shown);
            option.removeAttribute('aria-selected');
            option.removeAttribute('data-highlighted');
            const group = row?.dataset.group ?? '';
            const startsGroup = shown && group !== '' && group !== previousGroup;
            row?.querySelectorAll('[data-slot="command-palette-group"], [data-slot="command-palette-group-name"]').forEach((element) => element.toggleAttribute('hidden', !startsGroup));
            if (shown) previousGroup = group;
        });
        empty?.toggleAttribute('hidden', results.length > 0);
        input?.setAttribute('aria-expanded', String(results.length > 0));
        if (status) status.textContent = term === '' ? '' : results.length ? (status.dataset.results ?? '').replace(':count', String(results.length)) : (status.dataset.empty ?? '');
        highlight(0);
    };

    const open = () => {
        if (isOpen || !layer) return;
        isOpen = true;
        if (input) input.value = '';
        filter('');
        cancelLeave();
        enter([layer], parts);
        overlays.open(layer, { close: () => instance.close(), trap: () => panel ?? layer, opener: document.activeElement });
        input?.focus();
    };

    const close = () => {
        if (!isOpen || !layer) return;
        isOpen = false;
        cancelLeave = leave([layer], parts);
        overlays.close(layer);
    };

    const go = (/** @type {HTMLElement | undefined} */ option) => {
        const href = option?.getAttribute('href');
        if (!href) return;
        close();
        // wire:navigate only within the application; mailto:, tel: and other sites as a normal link.
        const url = new URL(href, location.href);
        const livewire = /** @type {{ Livewire?: { navigate?: (href: string) => void } }} */ (/** @type {unknown} */ (window)).Livewire;
        if (url.origin === location.origin && livewire?.navigate) livewire.navigate(href);
        else window.location.href = url.href;
    };

    const onInput = () => filter(input?.value ?? '');
    const onKeydown = (/** @type {KeyboardEvent} */ event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            highlight(highlighted + (event.key === 'ArrowDown' ? 1 : -1), { scroll: true });
        } else if (event.key === 'Home' || event.key === 'End') {
            event.preventDefault();
            highlight(event.key === 'Home' ? 0 : results.length - 1, { scroll: true });
        } else if (event.key === 'Enter') {
            event.preventDefault();
            go(results[highlighted]);
        } else if (event.key === 'Escape') {
            // Its own Escape: the overlay manager leaves Escape to an expanded combobox.
            event.preventDefault();
            event.stopPropagation();
            close();
        }
    };
    const onClick = (/** @type {MouseEvent} */ event) => {
        const option = /** @type {HTMLElement | null} */ (/** @type {Element} */ (event.target).closest('[role="option"]'));
        if (option) {
            event.preventDefault();
            go(option);
        } else if (/** @type {Element} */ (event.target).closest('[data-slot="command-palette-backdrop"]')) {
            close();
        }
    };
    const onPointer = (/** @type {MouseEvent} */ event) => {
        const option = /** @type {HTMLElement | null} */ (/** @type {Element} */ (event.target).closest('[role="option"]'));
        const index = option ? results.indexOf(option) : -1;
        if (index >= 0 && index !== highlighted) highlight(index);
    };
    const onShortcut = (/** @type {KeyboardEvent} */ event) => {
        if (!shortcut || event.key.toLowerCase() !== shortcut || !(event.metaKey || event.ctrlKey) || event.altKey) return;
        event.preventDefault();
        instance.toggle();
    };
    const onOpenEvent = () => open();

    trigger?.addEventListener('click', open);
    input?.addEventListener('input', onInput);
    input?.addEventListener('keydown', onKeydown);
    layer?.addEventListener('click', onClick);
    layer?.addEventListener('mousemove', onPointer);
    window.addEventListener('keydown', onShortcut);
    window.addEventListener('command-palette-open', onOpenEvent);

    /** @type {CommandPaletteInstance} */
    const instance = {
        element: root,
        get isOpen() {
            return isOpen;
        },
        open,
        close,
        toggle() {
            if (isOpen) close();
            else open();
        },
        filter,
        destroy() {
            trigger?.removeEventListener('click', open);
            input?.removeEventListener('input', onInput);
            input?.removeEventListener('keydown', onKeydown);
            layer?.removeEventListener('click', onClick);
            layer?.removeEventListener('mousemove', onPointer);
            window.removeEventListener('keydown', onShortcut);
            window.removeEventListener('command-palette-open', onOpenEvent);
            cancelLeave();
            if (layer) {
                overlays.close(layer);
                if (root.isConnected) root.append(layer);
                else layer.remove();
            }
            isOpen = false;
        },
    };

    return instance;
}

/** @type {PluginDefinition} */
export const commandPalettePlugin = {
    attribute: ATTRIBUTE,
    mount: createCommandPalette,
};
