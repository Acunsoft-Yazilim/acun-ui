// jsdom has no layout: acun:carousel's Swiper would find no width and lock itself. withCarouselLayout(run) gives the
// carousels' viewports and thumbnail strips a 300 px box and their slides and thumbnails 300 px each while run() runs,
// so Swiper has something to slide (carousel-chrome.test.mjs drives the real layout in a browser).
import { window } from './browser.mjs';

const BOXES = '[data-slot="carousel-viewport"], [data-slot="carousel-thumbs"]';

export async function withCarouselLayout(run) {
    const width = Object.getOwnPropertyDescriptor(window.Element.prototype, 'clientWidth');
    const height = Object.getOwnPropertyDescriptor(window.Element.prototype, 'clientHeight');
    const styles = window.getComputedStyle;
    Object.defineProperty(window.Element.prototype, 'clientWidth', { configurable: true, get() { return this.matches(BOXES) ? 300 : 0; } });
    Object.defineProperty(window.Element.prototype, 'clientHeight', { configurable: true, get() { return this.matches(BOXES) ? 300 : 0; } });
    // Swiper reads window.getComputedStyle and the global one.
    globalThis.getComputedStyle = window.getComputedStyle = (element, pseudo) => {
        const style = styles.call(window, element, pseudo);
        if (!element.classList?.contains('acun-carousel-slide') && !element.classList?.contains('acun-carousel-thumb')) return style;

        return new Proxy(style, { get: (target, key) => (key === 'getPropertyValue' ? (name) => (name === 'width' || name === 'height' ? '300px' : target.getPropertyValue(name)) : typeof target[key] === 'function' ? target[key].bind(target) : target[key]) });
    };
    try {
        return await run();
    } finally {
        if (width) Object.defineProperty(window.Element.prototype, 'clientWidth', width);
        if (height) Object.defineProperty(window.Element.prototype, 'clientHeight', height);
        globalThis.getComputedStyle = window.getComputedStyle = styles;
    }
}
