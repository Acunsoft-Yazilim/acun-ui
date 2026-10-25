/**
 * Keeps dialog named by the title of owner (the component root, marked data-acun-{component})
 * as morphs change them. Returns the function that stops.
 *
 * @param {Element | null} dialog
 * @param {Element} owner
 * @param {'modal' | 'drawer'} component
 * @returns {() => void}
 */
export declare function keepHeaderName(dialog: Element | null, owner: Element, component: 'modal' | 'drawer'): () => void;
