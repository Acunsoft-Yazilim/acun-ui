/**
 * The settings (null on a page without acun:layout.head): the newest island. The older ones (pages
 * left with wire:navigate) are removed, so the head does not grow and no stale copy is read.
 *
 * @param {Document} [doc]
 * @returns {Record<string, any> | null}
 */
export declare function readConfig(doc?: Document): Record<string, any> | null;
