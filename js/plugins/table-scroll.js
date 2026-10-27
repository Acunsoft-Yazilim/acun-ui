// A table box that scrolls sideways (acun:table, .ui-table-box), reachable with the keyboard: while its table is
// wider than the box it becomes a focusable, named region (tabindex="0", role="region", aria-label: the table's
// caption, else data-scroll-label), so arrow keys scroll it (WCAG 2.1.1); a box that fits stays out of the Tab order.
// Measured again when the box or the table changes size.
//
// A box with data-table-stack (acun:table stack: on a narrow box each row is drawn as a card, its cells as blocks)
// gives the table its roles explicitly (table, rowgroup, row, columnheader, rowheader, cell): the browser drops a
// table's meaning once its parts are no longer drawn as one, and screen readers then read a stacked list of text.
// They are put back on rows a Livewire update adds or redraws.

import { plugins } from '../core/plugins.js';

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @param {HTMLElement} root
 */
function createTableScroll(root) {
    plugins.own(root, 'tabindex', 'role', 'aria-label');
    const own = { tabindex: root.getAttribute('tabindex'), role: root.getAttribute('role'), label: root.getAttribute('aria-label') };

    const measure = () => {
        const scrolls = root.scrollWidth > root.clientWidth + 1;
        if (scrolls) {
            const caption = root.querySelector('caption')?.textContent?.trim();
            if (own.tabindex === null) root.setAttribute('tabindex', '0');
            if (own.role === null) root.setAttribute('role', 'region');
            if (own.label === null && !root.hasAttribute('aria-labelledby')) root.setAttribute('aria-label', caption || root.dataset.scrollLabel || '');
        } else {
            if (own.tabindex === null) root.removeAttribute('tabindex');
            if (own.role === null) root.removeAttribute('role');
            if (own.label === null) root.removeAttribute('aria-label');
        }
    };

    const resizes = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(measure);
    resizes?.observe(root);
    const table = root.querySelector('table');
    if (table) resizes?.observe(table);
    measure();

    /** @param {Element} element @param {string} role */
    const give = (element, role) => {
        if (element.getAttribute('role') !== role) element.setAttribute('role', role);
    };
    const roles = () => {
        if (!table) return;
        give(table, 'table');
        for (const group of table.querySelectorAll(':scope > thead, :scope > tbody, :scope > tfoot')) {
            give(group, 'rowgroup');
            for (const row of group.querySelectorAll(':scope > tr')) {
                give(row, 'row');
                for (const cell of row.children) {
                    if (cell.tagName === 'TH') give(cell, group.tagName === 'THEAD' || cell.getAttribute('scope') === 'col' ? 'columnheader' : 'rowheader');
                    else if (cell.tagName === 'TD') give(cell, 'cell');
                }
            }
        }
    };
    const stacked = root.hasAttribute('data-table-stack') && table !== null && typeof MutationObserver !== 'undefined';
    const redraws = stacked ? new MutationObserver(roles) : null;
    if (stacked) {
        roles();
        redraws?.observe(/** @type {HTMLTableElement} */ (table), { childList: true, subtree: true, attributes: true, attributeFilter: ['role'] });
    }

    return {
        element: root,
        destroy() {
            resizes?.disconnect();
            redraws?.disconnect();
        },
    };
}

/** @type {PluginDefinition} */
export const tableScrollPlugin = {
    attribute: 'data-acun-table-scroll',
    mount: createTableScroll,
};
