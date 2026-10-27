// Before a component hides itself with the focus inside (a dismissed alert, a closed card), the focus moves to a
// neighbour, so it is not lost to <body> (WCAG 2.4.3): the next element the Tab key reaches after it, else the one
// before it.

import { focusable } from '../core/dom.js';

/** @param {HTMLElement} element */
export function moveFocusOut(element) {
    if (!element.contains(document.activeElement)) return;

    const reachable = focusable(document.body).filter((candidate) => !element.contains(candidate));
    const after = reachable.find((candidate) => element.compareDocumentPosition(candidate) & Node.DOCUMENT_POSITION_FOLLOWING);
    const before = [...reachable].reverse().find((candidate) => element.compareDocumentPosition(candidate) & Node.DOCUMENT_POSITION_PRECEDING);
    (after ?? before)?.focus();
}
