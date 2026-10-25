/**
 * The settings (null on a page without acun:layout.head). wire:navigate may add a newer copy: the last one wins.
 *
 * @param {Document} [doc]
 * @returns {Record<string, any> | null}
 */
export declare function readConfig(doc?: Document): Record<string, any> | null;
