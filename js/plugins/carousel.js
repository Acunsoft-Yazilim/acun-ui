// acun:carousel, in plain JavaScript (no Alpine): the slider of a home page, the scrolling product strips and the
// product gallery with thumbnails and zoom, on Swiper. This definition is small and on every page; the sliding itself (plugins/carousel-engine.js, with Swiper)
// is imported the first time a carousel is on the page (a chunk of its own in a bundler build,
// dist/acun-ui-carousel.js beside dist/acun-ui.js in the browser build). Until it has loaded, and without
// JavaScript, the slides scroll sideways with snapping (the view's CSS). See carousel-engine.js for the markup.
//
//     AcunUI.plugins.get(element, 'carousel')   → { next(), previous(), goTo(index), pause(), play(), index, count,
//                                                   paused, update(), showSet(name), openFullscreen(), swiper and
//                                                   thumbs (Swiper, once loaded) }
//
// The acun:carousel:set event ({ name }) on the element shows that set (acun:carousel.set); one sent before the
// sliding has loaded is kept and shown when it has.
//
// AcunUI.destroy(element) takes Swiper off and leaves the server's markup; AcunUI.init(element) starts it again (the
// MutationObserver does both by itself when a carousel leaves or enters the page: a Livewire re-render).

import { loadCarousel } from './carousel-loader.js';

/** @import { PluginDefinition } from '../core/plugins.js' */
/** @typedef {import('./carousel-engine.js').CarouselInstance} CarouselInstance */

/** @type {PluginDefinition} */
export const carouselPlugin = {
    selector: '[data-acun-carousel]',
    mount(element) {
        /** @type {Partial<CarouselInstance> | null} */
        let instance = null;
        let stopped = false;
        /** @type {string | null} a set asked for before the sliding loaded */
        let pendingSet = null;
        const showSet = (/** @type {string} */ name) => {
            if (instance?.showSet) return instance.showSet(name);
            pendingSet = name;

            return false;
        };
        const onSet = (/** @type {Event} */ event) => {
            const name = /** @type {CustomEvent<{ name?: unknown }>} */ (event).detail?.name;
            if (event.target === element && typeof name === 'string') showSet(name);
        };
        element.addEventListener('acun:carousel:set', onSet);
        loadCarousel().then((engine) => {
            if (stopped || !element.isConnected) return;
            instance = /** @type {Partial<CarouselInstance>} */ (engine.mount(element));
            if (pendingSet !== null) instance.showSet?.(pendingSet);
            pendingSet = null;
        });

        return {
            element,
            get index() {
                return instance?.index ?? 0;
            },
            get count() {
                return instance?.count ?? element.querySelector('[data-slot="carousel-track"]')?.children.length ?? 0;
            },
            get paused() {
                return instance?.paused ?? false;
            },
            get swiper() {
                return instance?.swiper ?? null;
            },
            get thumbs() {
                return instance?.thumbs ?? null;
            },
            next: () => instance?.next?.(),
            previous: () => instance?.previous?.(),
            goTo: (/** @type {number} */ index) => instance?.goTo?.(index),
            pause: () => instance?.pause?.(),
            play: () => instance?.play?.(),
            update: () => instance?.update?.(),
            showSet,
            openFullscreen: () => instance?.openFullscreen?.(),
            destroy() {
                stopped = true;
                element.removeEventListener('acun:carousel:set', onSet);
                instance?.destroy?.();
            },
        };
    },
};
