/**
 * Opens: layers lose hidden, are laid out, and parts get data-open (layers and parts may overlap).
 *
 * @param {HTMLElement[]} layers
 * @param {Element[]} parts
 */
export declare function enter(layers: HTMLElement[], parts: Element[]): void;
/**
 * Closes: parts lose data-open; once their transitions have ended, layers get hidden and done()
 * runs. Returns a function that cancels the wait (the component opened again meanwhile).
 *
 * @param {HTMLElement[]} layers
 * @param {Element[]} parts
 * @param {() => void} [done]
 * @returns {() => void}
 */
export declare function leave(layers: HTMLElement[], parts: Element[], done?: () => void): () => void;
