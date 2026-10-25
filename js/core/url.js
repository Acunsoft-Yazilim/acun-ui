// Addresses the runtime sends a request to: the CSRF token goes only to this origin.
// (The safe-link rule for sending the browser somewhere is in components/passkey.js,
// so it stays out of the entry chunk.)

/**
 * On this page's origin (a relative address is). An address that cannot be read is not.
 *
 * @param {unknown} url
 */
export function isSameOrigin(url) {
    if (typeof url !== 'string') return false;

    try {
        return new URL(url, window.location.href).origin === window.location.origin;
    } catch {
        return false;
    }
}

/**
 * The CSRF token header (<meta name="csrf-token">) for a request to url: none for another origin.
 *
 * @param {unknown} url
 * @returns {Record<string, string>}
 */
export function csrfHeader(url) {
    if (!isSameOrigin(url)) return {};

    return { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' };
}
