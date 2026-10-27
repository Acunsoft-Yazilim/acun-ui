// The toast container (acun:toast-container), in plain JavaScript (no Alpine). Markup:
//
//     <div data-acun-toasts aria-live="polite" aria-relevant="additions" data-type-classes='{"success":"bg-success text-on-success",…}' data-flashed='[…]' class="fixed …">
//         <template data-slot="toast-template">
//             <div data-slot="toast" class="… opacity-0 data-open:opacity-100"><div><p data-slot="toast-message"></p><a data-slot="toast-link" hidden></a></div><button type="button" data-slot="toast-close">×</button></div>
//         </template>
//     </div>
//
// A 'toast' event on window ({ type, message, link, linkLabel, download }: Livewire's dispatch('toast', …)) shows a
// toast from the template; the toasts the server flashed for this page (data-flashed) show when it mounts. A toast
// goes after 5 seconds and stays while the pointer or the focus is on it; one with a link (an action, a download)
// stays until it is closed, so there is time to reach it with the keyboard or a screen reader (WCAG 2.2.1). An error is
// announced at once (role="alert"), the others by the container (aria-live="polite"; no status region inside it). The type is read as the server reads it
// (danger is error, any case; anything else is info). A link passes the rule of Acun\Ui\Support\Url (javascript:,
// data:… and another site's //host are dropped), opens with wire:navigate when Livewire is on the page and the link
// is on this site, and download: true (or a file name) downloads it instead.

import { enter, leave } from '../core/transition.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @typedef {object} ToastData
 * @property {number} id
 * @property {'success' | 'error' | 'warning' | 'info'} type
 * @property {string} message
 * @property {string | null} link
 * @property {string | null} linkLabel
 * @property {string | null} download  '' downloads with the server's name, a text names the file, null: a plain link
 */

/**
 * The toast container (AcunUI.plugins.get(element, 'toasts')).
 *
 * @typedef {object} ToastsInstance
 * @property {HTMLElement} element
 * @property {ToastData[]} toasts  the toasts on screen
 * @property {Map<number, { left: number, since?: number, handle: ReturnType<typeof setTimeout> | null }>} timers
 * @property {(toast: Record<string, unknown>) => void} push
 * @property {(id: number) => void} dismiss
 * @property {(id: number) => void} pause
 * @property {(id: number) => void} resume
 * @property {() => void} destroy
 */

const ATTRIBUTE = 'data-acun-toasts';
const TYPES = /** @type {const} */ (['success', 'error', 'warning', 'info']);
let counter = 0;

/**
 * The types of Acun\Ui\Support\Toast: the palette's names work too (danger is error), in any case; anything else is info.
 *
 * @param {unknown} type
 * @returns {ToastData['type']}
 */
export function toastType(type) {
    const name = String(type ?? '').trim().toLowerCase();
    const resolved = /** @type {Record<string, string>} */ ({ danger: 'error', red: 'error', green: 'success', emerald: 'success', amber: 'warning', yellow: 'warning', blue: 'info', sky: 'info' })[name] ?? name;

    return /** @type {readonly string[]} */ (TYPES).includes(resolved) ? /** @type {ToastData['type']} */ (resolved) : 'info';
}

/** @param {string} url */
const sameSite = (url) => {
    try {
        return new URL(url, location.href).origin === location.origin;
    } catch {
        return false;
    }
};

/**
 * The rule of Acun\Ui\Support\Url: only http, https, mailto, tel and relative links; a protocol-relative //host
 * link only to this site.
 *
 * @param {unknown} url
 * @returns {string | null}
 */
export function safeLink(url) {
    if (typeof url !== 'string' || url === '') return null;
    const normalized = url.replace(/[\t\n\r]/g, '').replace(/^[\x00-\x20]+/, '');
    // Browsers read a backslash as a slash: /\host and \\host are //host too.
    if (/^[\\/]{2}/.test(normalized)) return sameSite(normalized) ? url : null;
    const scheme = normalized.match(/^([a-z][a-z0-9+.-]*):/i)?.[1]?.toLowerCase();

    return !scheme || ['http', 'https', 'mailto', 'tel'].includes(scheme) ? url : null;
}

/** @param {unknown} download */
const downloadName = (download) => {
    if (download === true) return '';
    return typeof download === 'string' && download.trim() !== '' ? download.trim() : null;
};

/**
 * @param {HTMLElement} root
 * @returns {ToastsInstance}
 */
function createToasts(root) {
    const template = /** @type {HTMLTemplateElement | null} */ (root.querySelector('template[data-slot="toast-template"]'));
    /** @type {Record<string, string>} */
    let typeClasses = {};
    try {
        typeClasses = JSON.parse(root.dataset.typeClasses ?? '{}');
    } catch {
        typeClasses = {};
    }

    /** @type {ToastData[]} */
    let toasts = [];
    /** @type {ToastsInstance['timers']} */
    const timers = new Map();
    /** @type {Map<number, HTMLElement>} */
    const elements = new Map();

    /** @param {ToastData} toast */
    const draw = (toast) => {
        const element = /** @type {HTMLElement | null} */ (template?.content.firstElementChild?.cloneNode(true) ?? null);
        if (!element) return;

        // The container is the polite live region: it says the others; an error is said at once (alert).
        if (toast.type === 'error') element.setAttribute('role', 'alert');
        element.dataset.type = toast.type;
        element.classList.add(...(typeClasses[toast.type] ?? '').split(/\s+/).filter(Boolean));
        const message = element.querySelector('[data-slot="toast-message"]');
        if (message) message.textContent = toast.message;

        const link = /** @type {HTMLAnchorElement | null} */ (element.querySelector('[data-slot="toast-link"]'));
        if (link && toast.link) {
            link.href = toast.link;
            link.setAttribute('href', toast.link);
            link.textContent = toast.linkLabel ?? '';
            if (toast.download !== null) link.setAttribute('download', toast.download);
            link.hidden = false;
            link.addEventListener('click', (event) => {
                const live = /** @type {Window & { Livewire?: { navigate?: (url: string) => void } }} */ (window).Livewire;
                if (toast.download === null && live?.navigate && toast.link && sameSite(toast.link)) {
                    event.preventDefault();
                    live.navigate(toast.link);
                }
                instance.dismiss(toast.id);
            });
        } else {
            link?.remove();
        }

        element.querySelector('[data-slot="toast-close"]')?.addEventListener('click', () => instance.dismiss(toast.id));
        element.addEventListener('mouseenter', () => instance.pause(toast.id));
        element.addEventListener('mouseleave', () => {
            if (!element.contains(document.activeElement)) instance.resume(toast.id);
        });
        element.addEventListener('focusin', () => instance.pause(toast.id));
        element.addEventListener('focusout', (event) => {
            if (!element.contains(/** @type {Node | null} */ (event.relatedTarget))) instance.resume(toast.id);
        });

        root.append(element);
        elements.set(toast.id, element);
        enter([element], [element]);
    };

    /** @type {ToastsInstance} */
    const instance = {
        element: root,

        get toasts() {
            return toasts;
        },

        timers,

        push(input) {
            const link = safeLink(input.link);
            /** @type {ToastData} */
            const toast = {
                id: ++counter,
                type: toastType(input.type),
                message: String(input.message ?? ''),
                link,
                linkLabel: typeof input.linkLabel === 'string' ? input.linkLabel : null,
                download: downloadName(input.download),
            };
            toasts = [...toasts, toast];
            draw(toast);
            // A toast with a link stays until it is closed; the others go after 5 seconds.
            if (!link) {
                timers.set(toast.id, { left: 5000, handle: null });
                instance.resume(toast.id);
            }
        },

        pause(id) {
            const timer = timers.get(id);
            if (!timer?.handle) return;
            clearTimeout(timer.handle);
            timer.handle = null;
            timer.left -= Date.now() - (timer.since ?? Date.now());
        },

        resume(id) {
            const timer = timers.get(id);
            if (!timer || timer.handle) return;
            timer.since = Date.now();
            timer.handle = setTimeout(() => instance.dismiss(id), Math.max(timer.left, 1000));
        },

        dismiss(id) {
            const timer = timers.get(id);
            if (timer?.handle) clearTimeout(timer.handle);
            timers.delete(id);
            toasts = toasts.filter((toast) => toast.id !== id);
            const element = elements.get(id);
            elements.delete(id);
            if (element) leave([], [element], () => element.remove());
        },

        destroy() {
            window.removeEventListener('toast', onToast);
            timers.forEach((timer) => timer.handle && clearTimeout(timer.handle));
            timers.clear();
        },
    };

    const onToast = (/** @type {Event} */ event) => instance.push(/** @type {CustomEvent<Record<string, unknown>>} */ (event).detail ?? {});
    window.addEventListener('toast', onToast);

    // The toasts the server flashed for this page (Toast::flash before a redirect).
    try {
        /** @type {Record<string, unknown>[]} */ (JSON.parse(root.dataset.flashed ?? '[]')).forEach((toast) => instance.push(toast));
    } catch {
        // No flashed toasts.
    }

    return instance;
}

/** @type {PluginDefinition} */
export const toastsPlugin = {
    attribute: ATTRIBUTE,
    mount: createToasts,
};
