// Showing and hiding a component with CSS transitions, no JavaScript animation: the markup
// styles its closed state, and its open state under data-open (Tailwind: opacity-0
// data-open:opacity-100 transition-opacity). Entering, the layer loses hidden, the browser lays
// it out closed, and data-open starts the transition; leaving, data-open goes and hidden comes
// back once the transitions have ended (transitionend, with a timeout in case one never fires).
// With prefers-reduced-motion (or no transition at all) hidden comes back at once.

const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true;

/** @param {string} value a CSS time: 150ms, 0.2s */
const seconds = (value) => (value.trim().endsWith('ms') ? Number.parseFloat(value) / 1000 : Number.parseFloat(value) || 0);

/**
 * The longest transition of element as it is styled now (duration + delay, in ms).
 *
 * @param {Element} element
 */
function longest(element) {
    const style = getComputedStyle(element);
    const durations = (style.transitionDuration || '0s').split(',').map(seconds);
    const delays = (style.transitionDelay || '0s').split(',').map(seconds);

    return Math.max(0, ...durations.map((duration, index) => (duration + (delays[index % delays.length] ?? 0)) * 1000));
}

/**
 * Opens: layers lose hidden, are laid out, and parts get data-open (layers and parts may overlap).
 *
 * @param {HTMLElement[]} layers
 * @param {Element[]} parts
 */
export function enter(layers, parts) {
    layers.forEach((layer) => layer.removeAttribute('hidden'));
    // Laid out closed first: the transition starts from there (no @starting-style needed).
    layers.forEach((layer) => layer.offsetWidth);
    parts.forEach((part) => part.setAttribute('data-open', ''));
}

/**
 * Closes: parts lose data-open; once their transitions have ended, layers get hidden and done()
 * runs. Returns a function that cancels the wait (the component opened again meanwhile).
 *
 * @param {HTMLElement[]} layers
 * @param {Element[]} parts
 * @param {() => void} [done]
 * @returns {() => void}
 */
export function leave(layers, parts, done = () => {}) {
    parts.forEach((part) => part.removeAttribute('data-open'));

    const finish = () => {
        layers.forEach((layer) => layer.setAttribute('hidden', ''));
        done();
    };

    /** @type {[Element, number][]} */
    const timed = reducedMotion() ? [] : parts.map((part) => /** @type {[Element, number]} */ ([part, longest(part)])).filter(([, time]) => time > 0);
    if (!timed.length) {
        finish();
        return () => {};
    }

    const pending = new Set(timed.map(([part]) => part));
    /** @type {ReturnType<typeof setTimeout> | undefined} */
    let timer;
    const stop = () => {
        clearTimeout(timer);
        timed.forEach(([part]) => part.removeEventListener('transitionend', onEnd));
    };
    const complete = () => {
        stop();
        finish();
    };
    /** @param {Event} event */
    function onEnd(event) {
        if (!event.target || !pending.has(/** @type {Element} */ (event.target))) return;
        pending.delete(/** @type {Element} */ (event.target));
        if (!pending.size) complete();
    }

    timed.forEach(([part]) => part.addEventListener('transitionend', onEnd));
    timer = setTimeout(complete, Math.max(...timed.map(([, time]) => time)) + 50);

    return stop;
}
