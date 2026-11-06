// How acun:carousel's sliding (plugins/carousel-engine.js, with Swiper) reaches the page: a chunk of its own in a
// bundler build, imported the first time a carousel is on the page. The browser build puts carousel-script.js in
// this file's place (scripts/build-js.mjs): dist/acun-ui.js carries no Swiper.

export const loadCarousel = () => import('./carousel-engine.js');
