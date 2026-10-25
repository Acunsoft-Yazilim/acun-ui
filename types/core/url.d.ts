/**
 * On this page's origin (a relative address is). An address that cannot be read is not.
 *
 * @param {unknown} url
 */
export declare function isSameOrigin(url: unknown): boolean;
/**
 * The CSRF token header (<meta name="csrf-token">) for a request to url: none for another origin.
 *
 * @param {unknown} url
 * @returns {Record<string, string>}
 */
export declare function csrfHeader(url: unknown): Record<string, string>;
