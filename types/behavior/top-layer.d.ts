/**
 * Whether an ancestor of element (below <body>) would hold a fixed layer in its stacking context or containing block.
 *
 * @param {Element} element
 */
export declare function held(element: Element): boolean;
/**
 * Raises layer into the top layer when an ancestor would hold it; returns the function that lowers it again.
 *
 * @param {HTMLElement} layer
 * @returns {() => void}
 */
export declare function raise(layer: HTMLElement): () => void;
