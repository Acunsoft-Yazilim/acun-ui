// The settings acun:layout.head prints for the runtime: <script type="application/json" data-acun-ui-config="…">.
//
// Every response's island carries an id of its own: wire:navigate adds a head <script> only when the
// same markup is not there yet and never removes one, so going panel → shop → panel would otherwise
// keep the shop's island last. With a fresh id the island of the page shown is always the newest.

/**
 * The settings (null on a page without acun:layout.head): the newest island. The older ones (pages
 * left with wire:navigate) are removed, so the head does not grow and no stale copy is read.
 *
 * @param {Document} [doc]
 * @returns {Record<string, any> | null}
 */
export function readConfig(doc = document) {
    const islands = [...doc.querySelectorAll('script[data-acun-ui-config]')];
    const island = islands.pop();

    if (!island) return null;
    islands.forEach((old) => old.remove());

    try {
        return JSON.parse(island.textContent ?? '');
    } catch {
        return null;
    }
}
