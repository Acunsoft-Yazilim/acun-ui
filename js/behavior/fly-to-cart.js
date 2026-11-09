// "Fly to cart": a copy of a picture flies to a cart button, shrinking into a small round thumbnail and fading on
// arrival, then the cart gives a little bounce. Only the look: it adds nothing to a cart and knows no cart; the page
// calls it once its own add has succeeded (Acun Pro's storefront does, on its add-to-cart's cart-updated event):
//
//     await AcunUI.flyToCart(card.querySelector('img'), document.querySelector('[data-acun-cart-target]'));
//
// source and target: elements or selectors. The copy is an <img> of the picture's current source (an <img>, or the
// first one inside the source), else a deep copy of the source with its ids and controls taken out (a drawing), at
// the source's place and size, fixed above the page (inside the source's dialog when the browser's top layer holds
// it); it follows a short arc (Web Animations, no library) and is
// removed at the end, also when the animation is cancelled. Several adds in a row fly side by side; the cart's bounce
// starts again with each arrival. Nothing moves under prefers-reduced-motion (the promise then resolves false at
// once), when the source or the target is not on screen, or without element.animate. The target gets
// acun:fly-to-cart:arrived (bubbling) when a copy reaches it.

/**
 * @typedef {object} FlyToCartOptions
 * @property {number} [duration] The flight in milliseconds (default 700).
 * @property {number} [size] The copy's size on arrival in pixels (default 28).
 * @property {boolean} [bounce] The cart's little bounce on arrival (default true).
 */

/** @type {WeakMap<Element, Animation>} */
const bounces = new WeakMap();

/** @param {Element | string | null | undefined} value */
const elementOf = (value) => (typeof value === 'string' ? document.querySelector(value) : value instanceof Element ? value : null);

/** On screen: a box with a size that overlaps the viewport. @param {DOMRect} rect */
const visible = (rect) => rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.right > 0 && rect.top < window.innerHeight && rect.left < window.innerWidth;

/** The flying copy: the picture's image, else the source itself (a drawing), inert and out of the accessibility tree. @param {Element} source */
function copyOf(source) {
    const picture = source instanceof HTMLImageElement ? source : source.querySelector('img');
    if (picture instanceof HTMLImageElement && (picture.currentSrc || picture.src)) {
        const image = document.createElement('img');
        image.src = picture.currentSrc || picture.src;
        image.alt = '';
        image.style.objectFit = 'cover';
        return image;
    }

    const copy = /** @type {HTMLElement} */ (source.cloneNode(true));
    copy.querySelectorAll('[id]').forEach((element) => element.removeAttribute('id'));
    copy.removeAttribute('id');
    copy.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach((element) => element.setAttribute('tabindex', '-1'));
    return copy;
}

/**
 * Flies a copy of source to target. Resolves true once the copy has arrived (and been removed), false when nothing
 * moved (reduced motion, nothing on screen, no Web Animations).
 *
 * @param {Element | string | null | undefined} source
 * @param {Element | string | null | undefined} target
 * @param {FlyToCartOptions} [options]
 * @returns {Promise<boolean>}
 */
export async function flyToCart(source, target, options = {}) {
    const from = elementOf(source);
    const to = elementOf(target);
    if (!from || !to || typeof window === 'undefined' || typeof from.animate !== 'function') return false;
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return false;

    const start = from.getBoundingClientRect();
    const end = to.getBoundingClientRect();
    if (!visible(start) || !visible(end)) return false;

    const duration = Math.max(200, options.duration ?? 700);
    const size = Math.max(12, options.size ?? 28);
    const copy = copyOf(from);
    copy.setAttribute('aria-hidden', 'true');
    copy.setAttribute('inert', '');
    copy.setAttribute('data-acun-fly-to-cart', '');
    Object.assign(copy.style, {
        position: 'fixed',
        left: `${start.left}px`,
        top: `${start.top}px`,
        width: `${start.width}px`,
        height: `${start.height}px`,
        margin: '0',
        zIndex: '2147483000',
        pointerEvents: 'none',
        overflow: 'hidden',
        borderRadius: getComputedStyle(from).borderRadius,
        boxShadow: '0 18px 36px -12px rgb(0 0 0 / 0.35)',
        transformOrigin: 'center',
        willChange: 'transform, opacity, border-radius',
    });
    // Above the page; inside a dialog the browser's top layer holds (a raised modal: behavior/top-layer.js), so the
    // copy is not drawn under it.
    (from.closest('[data-top-layer], dialog[open]') ?? document.body).append(copy);

    // From the source's centre to the target's, rising over the way (an arc) and shrinking into a round thumbnail.
    const dx = end.left + end.width / 2 - (start.left + start.width / 2);
    const dy = end.top + end.height / 2 - (start.top + start.height / 2);
    const scale = size / Math.max(start.width, start.height);
    const lift = Math.min(140, Math.hypot(dx, dy) * 0.25);
    const middle = Math.max(scale, 0.45);
    const flight = copy.animate(
        [
            { transform: 'translate(0, 0) scale(1)', opacity: 1, borderRadius: getComputedStyle(from).borderRadius, offset: 0 },
            { transform: 'translate(0, -6px) scale(0.9)', borderRadius: '50%', offset: 0.18 },
            { transform: `translate(${dx * 0.55}px, ${dy * 0.55 - lift}px) scale(${middle})`, opacity: 0.95, borderRadius: '50%', offset: 0.6 },
            { transform: `translate(${dx}px, ${dy}px) scale(${scale})`, opacity: 0.2, borderRadius: '50%', offset: 1 },
        ],
        { duration, easing: 'cubic-bezier(0.45, 0, 0.25, 1)', fill: 'forwards' },
    );

    try {
        await flight.finished;
    } catch {
        // Cancelled (the page left): the copy goes all the same.
    } finally {
        copy.remove();
    }

    if (!to.isConnected) return true;
    to.dispatchEvent(new CustomEvent('acun:fly-to-cart:arrived', { bubbles: true }));
    if (options.bounce !== false) {
        bounces.get(to)?.cancel();
        bounces.set(to, to.animate([{ scale: '1' }, { scale: '1.22' }, { scale: '0.92' }, { scale: '1.06' }, { scale: '1' }], { duration: 480, easing: 'ease-out' }));
    }

    return true;
}
