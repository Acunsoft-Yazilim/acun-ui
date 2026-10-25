export declare const FOCUSABLE: string;
/**
 * Rendered and visible (not inside display: none, not visibility: hidden).
 *
 * @param {HTMLElement} element
 */
export declare function isVisible(element: HTMLElement): boolean;
/**
 * The elements inside container that Tab reaches, in order. Left out: tabindex="-1",
 * controls disabled by their fieldset, anything inside [inert], and the radios of a
 * group but one (the checked radio, else the first): Tab stops on a group once.
 *
 * @param {ParentNode} container
 * @returns {HTMLElement[]}
 */
export declare function focusable(container: ParentNode): HTMLElement[];
/**
 * The element holds a focus that belongs to a window of its own.
 *
 * @param {Element | null | undefined} element
 */
export declare function inOwnFocusLayer(element: Element | null | undefined): boolean;
/**
 * A part of a component (data-slot="{slot}"), not of a component of the same kind nested inside it
 * (a modal in a modal); fallback: a selector for markup without data-slot (2.0 and earlier).
 *
 * @param {HTMLElement} root       the component's root, marked with attribute
 * @param {string} attribute       data-acun-modal, data-acun-drawer…
 * @param {string} slot
 * @param {string} [fallback]
 * @returns {HTMLElement | null}
 */
export declare function ownPart(root: HTMLElement, attribute: string, slot: string, fallback?: string): HTMLElement | null;
