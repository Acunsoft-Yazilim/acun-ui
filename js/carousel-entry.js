// Entry of dist/acun-ui-carousel.js, acun:carousel's sliding (plugins/carousel-engine.js with Swiper) for the browser
// build: dist/acun-ui.js loads it the first time a carousel is on the page (plugins/carousel-script.js). Rebuilt with
// `npm run build:js`.
import { mount } from './plugins/carousel-engine.js';

const namespace = /** @type {any} */ (window).AcunUI ??= {};
namespace.chunks ??= {};
namespace.chunks.carousel ??= { mount };
