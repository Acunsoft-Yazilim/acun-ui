/**
 * Opens or closes region (and the trigger's aria-expanded); returns the function that cancels a closing still
 * running (the region opened again meanwhile).
 *
 * @param {HTMLElement} region
 * @param {HTMLElement | null} trigger
 * @param {boolean} open
 * @returns {() => void}
 */
export declare function setRegion(region: HTMLElement, trigger: HTMLElement | null, open: boolean): () => void;
