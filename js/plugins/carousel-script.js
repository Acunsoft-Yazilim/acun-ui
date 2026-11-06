// acun:carousel's sliding in the browser build (scripts/build-js.mjs puts this file in place of carousel-loader.js):
// dist/acun-ui-carousel.js (plugins/carousel-engine.js with Swiper, built from carousel-entry.js) is loaded with a
// <script> tag the first time a carousel is on the page. Its address is dist/acun-ui.js's with the file name
// changed and the query kept (the ?id= of @acunUiScripts is one hash of acun-ui.js and this file, Assets::id(), so
// a new build of either gives both a new address): the package route, the copy published to public/vendor/acun-ui or a CDN folder alike.
// The tag takes the nonce of the acun-ui.js tag (a Content-Security-Policy with nonces).

const FILE = 'acun-ui-carousel.js';

/** @typedef {{ mount: (element: HTMLElement) => { destroy?: () => void } }} CarouselEngine */

// The acun-ui.js tag, while it runs (a deferred script has it too); else the @acunUiScripts tag.
const running = typeof document === 'undefined' ? null : /** @type {HTMLScriptElement | null} */ (document.currentScript);

/** @type {Promise<CarouselEngine> | null} */
let loading = null;

const loaded = () => /** @type {CarouselEngine | undefined} */ (/** @type {any} */ (window).AcunUI?.chunks?.carousel);

// A carousel whose engine cannot load (the file is missing) stays the no-JS one: its slides scroll sideways.
/** @type {CarouselEngine} */
const nothing = { mount: () => ({ destroy() {} }) };

const runtimeTag = (doc = document) => (running?.src ? running : /** @type {HTMLScriptElement | null} */ (doc.querySelector('script[data-acun-ui][src]')));

/** The address of dist/acun-ui-carousel.js, beside the acun-ui.js tag; null without one. */
export function carouselUrl(doc = document) {
    const script = runtimeTag(doc);
    if (!script) return null;
    const url = new URL(script.src, doc.baseURI);
    url.pathname = `${url.pathname.slice(0, url.pathname.lastIndexOf('/') + 1)}${FILE}`;

    return url.href;
}

/** @param {string | null} [address] the file's address (default: beside the acun-ui.js tag) */
export function loadCarousel(address = null) {
    const engine = loaded();
    if (engine) return Promise.resolve(engine);

    return (loading ??= new Promise((resolve) => {
        const src = address ?? carouselUrl();
        const fail = (/** @type {string} */ why) => {
            loading = null;
            console.error(`[Acun UI] acun:carousel: ${why}`);
            resolve(nothing);
        };
        if (!src) {
            fail(`${FILE} has no address: the page has no <script data-acun-ui> tag (@acunUiScripts).`);
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.setAttribute('data-acun-ui-carousel', '');
        const nonce = runtimeTag()?.nonce;
        if (nonce) script.nonce = nonce;
        script.addEventListener('load', () => {
            const ready = loaded();
            if (ready) resolve(ready);
            else fail(`${src} did not set up the carousel.`);
        });
        script.addEventListener('error', () => {
            script.remove();
            fail(`${src} could not be loaded. Publish dist/ again (php artisan vendor:publish --tag=acun-ui-assets --force) or put ${FILE} next to acun-ui.js.`);
        });
        document.head.append(script);
    }));
}
