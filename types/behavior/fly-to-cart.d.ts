export type FlyToCartOptions = {
    /**
     * The flight in milliseconds (default 700).
     */
    duration?: number;
    /**
     * The copy's size on arrival in pixels (default 28).
     */
    size?: number;
    /**
     * The cart's little bounce on arrival (default true).
     */
    bounce?: boolean;
    /**
     * Where the flight starts when the picture is not on screen or its centre
     * is off screen or under a fixed or sticky header (a card scrolled up: the button pressed): a small thumbnail of the
     * picture rises from its centre.
     */
    origin?: Element | string | null;
};
/**
 * Flies a copy of source to target. Resolves true once the copy has arrived (and been removed), false when nothing
 * moved (reduced motion, nothing on screen, no Web Animations).
 *
 * @param {Element | string | null | undefined} source
 * @param {Element | string | null | undefined} target
 * @param {FlyToCartOptions} [options]
 * @returns {Promise<boolean>}
 */
export declare function flyToCart(source: Element | string | null | undefined, target: Element | string | null | undefined, options?: FlyToCartOptions): Promise<boolean>;
