// The settings acun:layout.head prints for the runtime: <script type="application/json" data-acun-ui-config>.

/**
 * The settings (null on a page without acun:layout.head). wire:navigate may add a newer copy: the last one wins.
 *
 * @param {Document} [doc]
 * @returns {Record<string, any> | null}
 */
export function readConfig(doc = document) {
    const islands = doc.querySelectorAll('script[data-acun-ui-config]');
    const island = islands[islands.length - 1];

    if (!island) return null;

    try {
        return JSON.parse(island.textContent ?? '');
    } catch {
        return null;
    }
}
