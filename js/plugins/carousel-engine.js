// acun:carousel's sliding, on Swiper (its core with the Thumbs, Zoom and FreeMode modules; no Swiper CSS: the kit's
// Blade draws the arrows, the dots, the counter, the pause and zoom buttons and the thumbnails with its tokens, and the
// few layout rules Swiper needs are Tailwind classes there). A chunk of its own: plugins/carousel.js imports it the
// first time a carousel is on the page (in the browser build it is dist/acun-ui-carousel.js, loaded from beside
// dist/acun-ui.js).
//
// Markup (acun:carousel renders it):
//
//     <div data-acun-carousel data-interval="5000" data-loop="rewind|infinite|off" data-strip data-centered
//          data-draggable="false" data-snap="false" data-auto-height data-zoom data-thumbs="horizontal|vertical"
//          data-slide-label=":number of :count" data-slide-role="slide" data-label-zoom-in="…" data-label-zoom-out="…"
//          role="region" aria-roledescription="carousel" aria-label="…" class="group/carousel …">
//         <div data-slot="carousel-viewport" class="relative overflow-hidden …">     ← Swiper's element
//             <div data-slot="carousel-track" class="acun-carousel-track flex …" aria-live="off">…slides…</div>
//             <button data-slot="carousel-prev">…</button> <button data-slot="carousel-next">…</button>
//             <button data-slot="carousel-pause" data-label-pause="Pause" data-label-play="Play">…</button>
//             <button data-slot="carousel-zoom">…</button>
//             <p data-slot="carousel-counter" aria-hidden="true"></p>
//             <div data-slot="carousel-dots"><template data-slot="carousel-dot"><button aria-label="Slide :number">…</template></div>
//         </div>
//         <div data-slot="carousel-thumbs"><div class="acun-carousel-thumbs flex">
//             <button data-slot="carousel-thumb" class="acun-carousel-thumb" aria-current="true">…</button> …
//         </div></div>
//     </div>
//
// Every element child of the track is a slide. A slide that names itself (acun:carousel.item label) keeps its name;
// the others are "n of m" (data-slide-label). With one slide in view the slides out of view are inert (Tab skips their
// links); a strip (data-strip: several in view) keeps them reachable and slides the focused one into view.
// The states for the views' variants: data-ready (Swiper runs: the no-JS scroll snapping and the CSS gap are off),
// data-controls (there is something to slide to: the arrows, the dots, the counter and the pause button show),
// data-paused, data-zoomed, aria-disabled on an arrow at an end (data-loop="off") and aria-current on the shown dot and
// thumbnail. Slides added or removed later (a Livewire update) and a morph that rewrote a slide's attributes are taken
// in (MutationObserver). Event from the root: acun:carousel:change ({ index, count }).
//
// Sets (acun:carousel.set: <template data-slot="carousel-set" data-name="red" data-announce="…">): showSet(name) (the
// acun:carousel:set event, plugins/carousel.js) puts a set's slides and thumbnails in place of the shown ones,
// at the first slide, and says data-announce in the root's status line (data-slot="carousel-status"); data-set
// names the shown set. Full screen (data-fullscreen="name of the dialog"): the button data-slot="carousel-fullscreen"
// (and a click or tap on a slide's picture) copies the slides and thumbnails into the carousel of the modal
// data-slot="carousel-lightbox" and opens it at the shown slide; closed, the page's carousel goes to the slide the
// dialog showed last. In the dialog Left / Right move and Escape first zooms out, then closes (the modal plugin).
// data-start: the slide a carousel starts at.

import Swiper from 'swiper';
import { FreeMode, Thumbs, Zoom } from 'swiper/modules';

const TRACK = 'acun-carousel-track';
const SLIDE = 'acun-carousel-slide';
// Elements that keep their own role (an image slide stays an image).
const OWN_ROLE = new Set(['IMG', 'PICTURE', 'VIDEO', 'IFRAME', 'A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA']);

/** Interactive elements: a click on them never opens the full-screen view. */
const INTERACTIVE = 'a[href], button, input, select, textarea, label, summary, [contenteditable], [tabindex]:not([tabindex="-1"])';

/** The runtime (window.AcunUI): its registry, init() and destroy(). */
const runtime = () => /** @type {any} */ (window).AcunUI;

const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true;

/** @param {EventTarget | null} target */
const isField = (target) => target instanceof Element && !!target.closest('input, select, textarea, [contenteditable=""], [contenteditable="true"]');

/** @param {Element | null | undefined} element @param {string[]} names */
const own = (element, ...names) => {
    if (element) /** @type {any} */ (window).AcunUI?.plugins?.own(element, ...names);
};

/** The CSS gap of a flex row or column (the no-JS layout), in px: Swiper's space between slides. @param {Element} element */
const gapOf = (element) => {
    const style = getComputedStyle(element);

    return Number.parseFloat(style.columnGap) || Number.parseFloat(style.rowGap) || 0;
};

/** Adds a class without writing the attribute when it is there (classList.add() writes it: a mutation of its own). */
const mark = (/** @type {Element} */ element, /** @type {string} */ name) => {
    if (!element.classList.contains(name)) element.classList.add(name);
};

/**
 * @typedef {object} CarouselInstance
 * @property {HTMLElement} element
 * @property {Swiper} swiper            the Swiper instance (its API beyond the kit's)
 * @property {Swiper | null} thumbs     the thumbnails' Swiper (data-thumbs)
 * @property {number} index             the shown slide (with several in view: the first)
 * @property {number} count             the number of slides
 * @property {boolean} paused           the auto-advance was paused by the user
 * @property {() => void} next
 * @property {() => void} previous
 * @property {(index: number) => void} goTo
 * @property {() => void} pause
 * @property {() => void} play
 * @property {(name: string) => boolean} showSet  shows a set (acun:carousel.set) in place of the slides
 * @property {() => void} openFullscreen  opens the full-screen view (data-fullscreen)
 * @property {() => void} update        measures again (after a change the observers do not see)
 * @property {() => void} destroy
 */

/**
 * @param {HTMLElement} root
 * @returns {CarouselInstance | { element: HTMLElement, destroy(): void }}
 */
export function mount(root) {
    /** @param {string} name */
    const part = (name) => /** @type {HTMLElement | null} */ ([...root.querySelectorAll(`[data-slot="${name}"]`)].find((element) => element.closest('[data-acun-carousel]') === root) ?? null);
    const viewport = part('carousel-viewport');
    const found = viewport ? /** @type {HTMLElement | undefined} */ ([...viewport.children].find((child) => child.matches('[data-slot="carousel-track"]'))) : undefined;
    if (!viewport || !found) return { element: root, destroy() {} };
    const view = viewport;
    const track = found;
    mark(track, TRACK);

    const prev = part('carousel-prev');
    const next = part('carousel-next');
    const pauseButton = part('carousel-pause');
    const zoomButton = part('carousel-zoom');
    const counter = part('carousel-counter');
    const dots = part('carousel-dots');
    const dotTemplate = /** @type {HTMLTemplateElement | null} */ (dots?.querySelector('template[data-slot="carousel-dot"]') ?? null);
    const thumbsRoot = part('carousel-thumbs');
    const thumbsTrack = /** @type {HTMLElement | null} */ (thumbsRoot?.querySelector('.acun-carousel-thumbs') ?? null);
    const status = part('carousel-status');
    const lightbox = part('carousel-lightbox');
    const lightboxCarousel = /** @type {HTMLElement | null} */ (lightbox?.querySelector('[data-carousel-lightbox]') ?? null);
    const fullscreenButton = part('carousel-fullscreen');
    const thumbButtons = () => /** @type {HTMLElement[]} */ (thumbsTrack ? [...thumbsTrack.children].filter((child) => child instanceof HTMLElement && child.matches('[data-slot="carousel-thumb"]')) : []);

    const data = root.dataset;
    const interval = Number(data.interval) || 0;
    const loop = data.loop ?? 'rewind';
    const strip = root.hasAttribute('data-strip');
    const zoom = root.hasAttribute('data-zoom');
    const draggable = data.draggable !== 'false';
    const slideLabel = data.slideLabel || ':number of :count';
    const slideRole = data.slideRole || 'slide';

    own(root, 'data-ready', 'data-controls', 'data-paused', 'data-zoomed', 'data-set');
    own(view, 'style', 'class');
    own(track, 'style', 'aria-live');
    [prev, next].forEach((button) => own(button, 'aria-disabled'));
    own(pauseButton, 'aria-label');
    own(zoomButton, 'aria-label');
    own(thumbsRoot, 'style', 'class');
    own(thumbsTrack, 'style');

    /** The slides: the track's element children. */
    const slides = () => /** @type {HTMLElement[]} */ ([...track.children].filter((child) => child instanceof HTMLElement && child.tagName !== 'TEMPLATE' && child.tagName !== 'SCRIPT'));
    /** @type {WeakSet<HTMLElement>} slides this plugin names (the others named themselves) */
    const named = new WeakSet();
    /** @type {WeakSet<HTMLElement>} slides this plugin gave the slide role */
    const roled = new WeakSet();

    // The CSS gaps (the no-JS layout) become Swiper's space between slides; data-ready turns the CSS gaps off.
    const gap = gapOf(track);
    slides().forEach((slide) => mark(slide, SLIDE));
    thumbButtons().forEach((thumb) => mark(thumb, 'acun-carousel-thumb'));

    let paused = false;
    let hovered = false;
    let focused = false;
    /** @type {ReturnType<typeof setInterval> | null} */
    let timer = null;
    let lastIndex = -1;
    let lastCount = -1;
    const speed = reducedMotion() ? 0 : 500;

    // The thumbnails' strip: hidden with one thumbnail or none (a set may have fewer pictures than another), made a
    // Swiper the first time it has several.
    own(thumbsRoot, 'hidden');
    const thumbGap = thumbsTrack ? gapOf(thumbsTrack) : 0;
    /** @type {Swiper | null} */
    let thumbs = null;
    const makeThumbs = () => {
        const several = thumbButtons().length > 1;
        if (thumbsRoot && thumbsRoot.hidden === several) thumbsRoot.hidden = !several;
        if (thumbs || !several || !thumbsRoot || !thumbsTrack) return thumbs;
        thumbs = new Swiper(thumbsRoot, {
            direction: data.thumbs === 'vertical' ? 'vertical' : 'horizontal',
            wrapperClass: 'acun-carousel-thumbs',
            slideClass: 'acun-carousel-thumb',
            slidesPerView: 'auto',
            spaceBetween: thumbGap,
            watchSlidesProgress: true,
            speed,
            threshold: 6,
        });

        return thumbs;
    };
    makeThumbs();

    // Swiper sends some of its events while it is being made: render() waits for it.
    /** @type {Swiper | null} */
    let made = null;
    const changed = () => {
        if (made) render();
    };
    const swiper = new Swiper(view, {
        modules: [FreeMode, Thumbs, Zoom],
        wrapperClass: TRACK,
        slideClass: SLIDE,
        // The slides' widths are the view's CSS (the per-view classes), so the no-JS page and the first paint look the same.
        slidesPerView: 'auto',
        spaceBetween: gap,
        loop: loop === 'infinite',
        rewind: loop === 'rewind',
        centeredSlides: root.hasAttribute('data-centered'),
        autoHeight: root.hasAttribute('data-auto-height'),
        freeMode: { enabled: data.snap === 'false', sticky: false },
        simulateTouch: draggable,
        grabCursor: draggable,
        speed,
        watchSlidesProgress: true,
        resizeObserver: true,
        threshold: 6,
        // A drag that moved does not click the link it started on.
        preventClicks: true,
        thumbs: { swiper: thumbs, autoScrollOffset: 1 },
        // With a full-screen view a tap opens it, so a double tap does not zoom here (the dialog zooms).
        zoom: zoom ? { maxRatio: 2.5, toggle: !lightbox, panOnMouseMove: true, containerClass: 'acun-carousel-zoom' } : false,
        initialSlide: Math.max(0, Number(data.start) || 0),
        on: {
            slideChange: changed,
            snapGridLengthChange: changed,
            lock: changed,
            unlock: changed,
            resize: changed,
            loopFix: changed,
            zoomChange: (_swiper, scale) => zoomed(scale > 1),
            click: (_swiper, event) => onSlideClick(/** @type {Event} */ (event)),
        },
    });
    made = swiper;
    root.setAttribute('data-ready', '');

    const infinite = () => !!swiper.params.loop;
    /** The position of a slide among the slides as the server drew them (the infinite loop moves them around). */
    const realIndexOf = (/** @type {HTMLElement} */ slide, /** @type {number} */ fallback) => {
        const value = slide.getAttribute('data-swiper-slide-index');

        return value === null ? fallback : Number(value);
    };
    /** The positions the carousel can stop at (with several in view and no loop: fewer than the slides). */
    const positions = () => {
        if (swiper.isLocked) return 1;
        if (infinite() || swiper.params.centeredSlides) return slides().length;

        return Math.max(1, swiper.snapGrid.length);
    };
    const current = () => {
        if (infinite()) return swiper.realIndex;
        if (swiper.params.centeredSlides) return swiper.activeIndex;

        return Math.min(swiper.snapIndex ?? swiper.activeIndex, positions() - 1);
    };

    function label() {
        const all = slides();
        all.forEach((slide, index) => {
            if (!OWN_ROLE.has(slide.tagName) && (!slide.hasAttribute('role') || roled.has(slide))) {
                roled.add(slide);
                if (slide.getAttribute('role') !== 'group') slide.setAttribute('role', 'group');
                if (slide.getAttribute('aria-roledescription') !== slideRole) slide.setAttribute('aria-roledescription', slideRole);
            }
            if ((roled.has(slide) || slide.dataset.slot === 'carousel-item') && (!slide.hasAttribute('aria-label') || named.has(slide))) {
                named.add(slide);
                const text = slideLabel.replace(':number', String(realIndexOf(slide, index) + 1)).replace(':count', String(all.length));
                if (slide.getAttribute('aria-label') !== text) slide.setAttribute('aria-label', text);
            }
            // One slide in view: the others are inert (out of the Tab order and the accessibility tree).
            const inert = !strip && !swiper.isLocked && index !== swiper.activeIndex;
            if (slide.inert !== inert) slide.inert = inert;
        });
    }

    function renderDots() {
        if (!dots || !dotTemplate) return;
        const count = positions();
        let buttons = /** @type {HTMLButtonElement[]} */ ([...dots.querySelectorAll('[data-slot="carousel-dot-button"]')]);
        if (buttons.length !== count) {
            buttons.forEach((button) => button.remove());
            buttons = [];
            const text = dotTemplate.content.firstElementChild?.getAttribute('aria-label') ?? ':number';
            for (let i = 0; i < count; i++) {
                const button = /** @type {HTMLButtonElement | undefined} */ (dotTemplate.content.firstElementChild?.cloneNode(true));
                if (!button) break;
                button.setAttribute('data-slot', 'carousel-dot-button');
                button.dataset.index = String(i);
                button.setAttribute('aria-label', text.replace(':number', String(i + 1)));
                dots.append(button);
                buttons.push(button);
            }
        }
        const active = current();
        buttons.forEach((button, i) => {
            const value = String(i === active);
            if (button.getAttribute('aria-current') !== value) button.setAttribute('aria-current', value);
        });
    }

    function render() {
        if (swiper.destroyed) return;
        const count = slides().length;
        const index = current();
        root.toggleAttribute('data-controls', !swiper.isLocked && count > 1);
        if (loop === 'off') {
            prev?.setAttribute('aria-disabled', String(swiper.isBeginning));
            next?.setAttribute('aria-disabled', String(swiper.isEnd));
        }
        label();
        renderDots();
        if (counter) {
            const text = `${Math.min(index + 1, count)} / ${count}`;
            if (counter.textContent !== text) counter.textContent = text;
        }
        thumbButtons().forEach((thumb, i) => {
            const value = String(i === index);
            if (thumb.getAttribute('aria-current') !== value) thumb.setAttribute('aria-current', value);
        });
        if (index !== lastIndex || count !== lastCount) {
            lastIndex = index;
            lastCount = count;
            root.dispatchEvent(new CustomEvent('acun:carousel:change', { bubbles: true, detail: { index, count } }));
        }
    }

    function zoomed(/** @type {boolean} */ value) {
        root.toggleAttribute('data-zoomed', value);
        if (zoomButton) zoomButton.setAttribute('aria-label', (value ? data.labelZoomOut : data.labelZoomIn) ?? '');
    }

    // A slide whose focus would be lost when it turns inert hands it to the shown slide.
    function keepFocus() {
        const active = document.activeElement;
        if (strip || !active || !track.contains(active)) return;
        const shown = slides()[swiper.activeIndex];
        if (shown && !shown.contains(active)) {
            const target = /** @type {HTMLElement | null} */ (shown.querySelector('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'));
            (target ?? next ?? root).focus({ preventScroll: true });
        }
    }

    const go = (/** @type {() => void} */ move) => {
        move();
        keepFocus();
    };
    const goNext = () => go(() => swiper.slideNext());
    const goPrevious = () => go(() => swiper.slidePrev());
    const goTo = (/** @type {number} */ index) => go(() => {
        const target = Math.max(0, Math.min(index, slides().length - 1));
        if (infinite()) swiper.slideToLoop(target);
        else swiper.slideTo(target);
    });

    function start() {
        stop();
        if (!interval || paused || hovered || focused || document.hidden || reducedMotion() || swiper.isLocked) return;
        timer = setInterval(() => {
            // Stopping at the ends, the auto-advance turns back at the last one.
            if (loop === 'off' && swiper.isEnd) swiper.slideTo(0);
            else swiper.slideNext();
        }, interval);
        live();
    }
    function stop() {
        if (timer !== null) clearInterval(timer);
        timer = null;
        live();
    }
    // Announced when the user changes the slide, not on every automatic turn.
    const live = () => {
        const value = timer ? 'off' : 'polite';
        if (track.getAttribute('aria-live') !== value) track.setAttribute('aria-live', value);
    };

    function setPaused(/** @type {boolean} */ value) {
        paused = value;
        root.toggleAttribute('data-paused', paused);
        if (pauseButton) pauseButton.setAttribute('aria-label', (paused ? pauseButton.dataset.labelPlay : pauseButton.dataset.labelPause) ?? '');
        start();
    }

    /** Shows the set (acun:carousel.set) named name in place of the shown slides; false when there is none. */
    function showSet(/** @type {string} */ name) {
        const template = /** @type {HTMLTemplateElement | undefined} */ ([...track.children].find((child) => child instanceof HTMLTemplateElement && child.dataset.slot === 'carousel-set' && child.dataset.name === name));
        if (!template || data.set === name) return false;
        const content = /** @type {DocumentFragment} */ (template.content.cloneNode(true));
        const setThumbs = content.querySelector('[data-slot="carousel-set-thumbs"]');
        setThumbs?.remove();
        if (zoom && (swiper.zoom?.scale ?? 1) > 1) swiper.zoom.out();
        const loopOn = infinite();
        if (loopOn) swiper.loopDestroy();
        slides().forEach((slide) => slide.remove());
        track.insertBefore(content, track.querySelector(':scope > template'));
        slides().forEach((slide) => mark(slide, SLIDE));
        // The set's thumbnails in place of the shown ones; a set without (one picture) hides the strip.
        if (thumbsTrack) {
            thumbButtons().forEach((thumb) => thumb.remove());
            if (setThumbs) thumbsTrack.append(...setThumbs.children);
            thumbButtons().forEach((thumb) => mark(thumb, 'acun-carousel-thumb'));
            const before = thumbs;
            makeThumbs();
            // A strip made only now: Swiper Thumbs takes it from here.
            if (thumbs && !before && swiper.thumbs) {
                /** @type {any} */ (swiper.params.thumbs).swiper = thumbs;
                swiper.thumbs.init();
            }
            thumbs?.update();
            thumbs?.slideTo(0, 0);
        }
        root.dataset.set = name;
        swiper.update();
        if (loopOn) {
            swiper.loopCreate(0);
            swiper.slideToLoop(0, 0);
        } else {
            swiper.slideTo(0, 0);
        }
        remember();
        render();
        // Said once the new slides are in: a status line that changes its text is read out.
        if (status) {
            status.textContent = '';
            setTimeout(() => {
                status.textContent = template.dataset.announce ?? '';
            }, 60);
        }

        return true;
    }

    /** A copy of a slide or thumbnail for the full-screen view: the plugin's and Swiper's marks and the ids taken off. */
    const copyOf = (/** @type {HTMLElement} */ element) => {
        const copy = /** @type {HTMLElement} */ (element.cloneNode(true));
        for (const node of [copy, ...copy.querySelectorAll('*')]) {
            [...node.classList].filter((name) => name.startsWith('swiper-') && name !== 'swiper-zoom-target').forEach((name) => node.classList.remove(name));
            node.removeAttribute('id');
        }
        copy.removeAttribute('style');
        copy.removeAttribute('inert');
        copy.removeAttribute('data-swiper-slide-index');
        copy.querySelectorAll('.acun-carousel-zoom, .acun-carousel-zoom *').forEach((node) => node.removeAttribute('style'));
        if (named.has(element)) copy.removeAttribute('aria-label');
        if (roled.has(element)) {
            copy.removeAttribute('role');
            copy.removeAttribute('aria-roledescription');
        }

        return copy;
    };

    /** The full-screen view's carousel, once it has started. */
    const lightboxInstance = () => /** @type {Partial<CarouselInstance> | null} */ (lightboxCarousel ? runtime()?.plugins?.get(lightboxCarousel, 'carousel') ?? null : null);

    /** Opens the full-screen view at the shown slide; opener gets the focus back when it closes. */
    function openFullscreen(/** @type {HTMLElement | null} */ opener = fullscreenButton) {
        const acun = runtime();
        const modal = lightbox ? acun?.plugins?.get(lightbox, 'modal') : null;
        const inner = lightboxCarousel;
        const innerTrack = inner?.querySelector('[data-slot="carousel-track"]');
        if (!modal || !inner || !innerTrack) return;
        acun.destroy(inner);
        const ordered = slides().map((slide, index) => ({ slide, index: realIndexOf(slide, index) })).sort((a, b) => a.index - b.index);
        innerTrack.replaceChildren(...ordered.map(({ slide }) => {
            const copy = copyOf(slide);
            // Every picture zooms in the full-screen view.
            if (!copy.querySelector('.acun-carousel-zoom')) {
                const wrap = document.createElement('div');
                wrap.className = 'acun-carousel-zoom flex size-full items-center justify-center';
                wrap.append(...copy.childNodes);
                copy.append(wrap);
            }

            return copy;
        }));
        const innerThumbs = inner.querySelector('.acun-carousel-thumbs');
        innerThumbs?.replaceChildren(...thumbButtons().map((thumb) => {
            const copy = copyOf(thumb);
            copy.setAttribute('aria-current', 'false');

            return copy;
        }));
        inner.dataset.start = String(infinite() ? swiper.realIndex : swiper.activeIndex);
        modal.open({ opener: opener ?? root });
        // Closed again within that frame (a double Escape): nothing starts in a closed dialog.
        requestAnimationFrame(() => {
            if (lightbox?.hasAttribute('data-open') && !lightbox.hidden) acun.init(inner);
        });
    }

    // Closed, the page's carousel shows the slide the full-screen view showed last, and the copies go.
    const onLightboxClosed = (/** @type {Event} */ event) => {
        if (event.target !== lightbox || !lightboxCarousel) return;
        const index = lightboxInstance()?.index;
        if (typeof index === 'number') {
            if (infinite()) swiper.slideToLoop(index, 0);
            else swiper.slideTo(index, 0);
        }
        runtime()?.destroy(lightboxCarousel);
        lightboxCarousel.querySelector('[data-slot="carousel-track"]')?.replaceChildren();
        lightboxCarousel.querySelector('.acun-carousel-thumbs')?.replaceChildren();
    };

    // A click or tap on a slide's picture (not a link or a button in it, not the end of a drag) opens the full-screen view.
    function onSlideClick(/** @type {Event} */ event) {
        const target = /** @type {Element | null} */ (event?.target instanceof Element ? event.target : null);
        if (!lightbox || !target || !track.contains(target) || target.closest(INTERACTIVE)) return;
        openFullscreen(fullscreenButton);
    }

    // The full-screen view's keys when the focus is on its own buttons (its close button): Left / Right move its
    // carousel, Escape first zooms out. Its carousel handles the keys itself when the focus is in it.
    function onLightboxKey(/** @type {KeyboardEvent} */ event) {
        const inner = lightboxInstance();
        if (!inner?.swiper || event.defaultPrevented || lightboxCarousel?.contains(/** @type {Node} */ (event.target))) return;
        if (event.key === 'Escape' && (inner.swiper.zoom?.scale ?? 1) > 1) inner.swiper.zoom.out();
        else if (event.key === 'ArrowLeft') inner.previous?.();
        else if (event.key === 'ArrowRight') inner.next?.();
        else return;
        event.preventDefault();
    }

    const onClick = (/** @type {MouseEvent} */ event) => {
        const target = /** @type {Element} */ (event.target);
        if (lightbox?.contains(target)) return;
        const button = /** @type {HTMLElement | null} */ (target.closest?.('button'));
        if (!button || button.closest('[data-acun-carousel]') !== root) return;
        if (button === prev) {
            if (button.getAttribute('aria-disabled') !== 'true') goPrevious();
        } else if (button === next) {
            if (button.getAttribute('aria-disabled') !== 'true') goNext();
        } else if (button === pauseButton) {
            setPaused(!paused);
        } else if (button === zoomButton) {
            swiper.zoom?.toggle();
        } else if (button === fullscreenButton) {
            openFullscreen(button);
        } else if (button.dataset.slot === 'carousel-dot-button') {
            goTo(Number(button.dataset.index));
        } else if (button.dataset.slot === 'carousel-thumb') {
            // Swiper Thumbs answers taps; this covers Enter and Space too.
            goTo(thumbButtons().indexOf(button));
        }
    };
    const inThumbs = (/** @type {EventTarget | null} */ target) => data.thumbs === 'vertical' && !!thumbsRoot?.contains(/** @type {Node} */ (target));
    const onKeydown = (/** @type {KeyboardEvent} */ event) => {
        if (lightbox?.contains(/** @type {Node} */ (event.target))) {
            onLightboxKey(event);
            return;
        }
        if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || isField(event.target)) return;
        if (event.key === 'Escape' && zoom && (swiper.zoom?.scale ?? 1) > 1) swiper.zoom.out();
        else if (event.key === 'ArrowLeft' || (event.key === 'ArrowUp' && inThumbs(event.target))) goPrevious();
        else if (event.key === 'ArrowRight' || (event.key === 'ArrowDown' && inThumbs(event.target))) goNext();
        else return;
        event.preventDefault();
    };
    const onEnter = () => {
        hovered = true;
        stop();
    };
    const onLeave = () => {
        hovered = false;
        if (!root.contains(document.activeElement)) start();
    };
    // The pointer left the page (the window lost it, another app came forward): the carousel gets no mouseleave then.
    const onPageLeave = () => {
        if (hovered) onLeave();
    };
    // A hidden tab does not advance.
    const onVisibility = () => (document.hidden ? stop() : start());
    // A press (mouse, touch) focuses what it pressed without sliding; the keyboard's Tab slides it into view.
    let pressing = false;
    const onPointerDown = () => {
        pressing = true;
        setTimeout(() => {
            pressing = false;
        }, 0);
    };
    const onFocusIn = (/** @type {FocusEvent} */ event) => {
        focused = true;
        stop();
        if (lightbox?.contains(/** @type {Node} */ (event.target))) return;
        // A strip slides a slide the focus went into into view (the browser scrolled the clipped viewport: undone).
        if (!strip || pressing) return;
        const all = slides();
        const slide = all.find((item) => item.contains(/** @type {Node} */ (event.target)));
        if (!slide || slide.classList.contains(String(swiper.params.slideFullyVisibleClass))) return;
        view.scrollLeft = 0;
        const index = all.indexOf(slide);
        requestAnimationFrame(() => (infinite() ? swiper.slideToLoop(realIndexOf(slide, index)) : swiper.slideTo(Math.min(index, Math.max(0, swiper.snapGrid.length - 1)))));
    };
    const onFocusOut = (/** @type {FocusEvent} */ event) => {
        if (root.contains(/** @type {Node | null} */ (event.relatedTarget))) return;
        focused = false;
        if (!hovered) start();
    };

    root.addEventListener('click', onClick);
    root.addEventListener('keydown', onKeydown);
    root.addEventListener('mouseenter', onEnter);
    root.addEventListener('mouseleave', onLeave);
    root.addEventListener('pointerdown', onPointerDown);
    root.addEventListener('focusin', onFocusIn);
    root.addEventListener('focusout', onFocusOut);
    lightbox?.addEventListener('acun:modal:closed', onLightboxClosed);
    document.documentElement.addEventListener('mouseleave', onPageLeave);
    window.addEventListener('blur', onPageLeave);
    document.addEventListener('visibilitychange', onVisibility);

    // Slides added or removed (a Livewire update), or a morph that took a slide's class or Swiper's styles away. What
    // Swiper last laid out is kept here (not read back from Swiper: a carousel in a hidden tab has no size, and Swiper
    // lays nothing out until it shows). The order is not compared: the infinite loop moves slides around itself.
    /** @type {Set<HTMLElement>} */
    let known = new Set();
    /** @type {WeakMap<HTMLElement, string>} */
    const margins = new WeakMap();
    const spacing = (/** @type {HTMLElement} */ slide) => slide.style.marginRight + slide.style.marginBottom;
    const remember = () => {
        known = new Set(slides());
        known.forEach((slide) => margins.set(slide, spacing(slide)));
    };
    const watched = new WeakSet();
    const observeSlide = (/** @type {HTMLElement} */ slide) => {
        if (watched.has(slide)) return;
        watched.add(slide);
        observer.observe(slide, { attributes: true, attributeFilter: ['class', 'style', 'role', 'aria-label', 'inert'] });
    };
    const observer = new MutationObserver(() => {
        const all = slides();
        const different = all.length !== known.size || all.some((slide) => !known.has(slide) || !slide.classList.contains(SLIDE) || spacing(slide) !== margins.get(slide));
        all.forEach((slide) => {
            mark(slide, SLIDE);
            observeSlide(slide);
        });
        if (different) {
            swiper.update();
            remember();
        }
        render();
    });
    observer.observe(track, { childList: true });
    slides().forEach(observeSlide);
    remember();

    render();
    if (pauseButton) setPaused(false);
    else start();

    return {
        element: root,
        swiper,
        get thumbs() {
            return thumbs;
        },
        get index() {
            return current();
        },
        get count() {
            return slides().length;
        },
        get paused() {
            return paused;
        },
        next: goNext,
        previous: goPrevious,
        goTo,
        pause: () => setPaused(true),
        play: () => setPaused(false),
        showSet,
        openFullscreen: () => openFullscreen(fullscreenButton),
        update() {
            swiper.update();
            thumbs?.update();
            render();
        },
        destroy() {
            stop();
            observer.disconnect();
            root.removeEventListener('click', onClick);
            root.removeEventListener('keydown', onKeydown);
            root.removeEventListener('mouseenter', onEnter);
            root.removeEventListener('mouseleave', onLeave);
            root.removeEventListener('pointerdown', onPointerDown);
            root.removeEventListener('focusin', onFocusIn);
            root.removeEventListener('focusout', onFocusOut);
            lightbox?.removeEventListener('acun:modal:closed', onLightboxClosed);
            document.documentElement.removeEventListener('mouseleave', onPageLeave);
            window.removeEventListener('blur', onPageLeave);
            document.removeEventListener('visibilitychange', onVisibility);
            swiper.destroy(true, true);
            thumbs?.destroy(true, true);
            dots?.querySelectorAll('[data-slot="carousel-dot-button"]').forEach((button) => button.remove());
            if (counter) counter.textContent = '';
            slides().forEach((slide) => {
                slide.inert = false;
                if (named.has(slide)) slide.removeAttribute('aria-label');
                if (roled.has(slide)) {
                    slide.removeAttribute('role');
                    slide.removeAttribute('aria-roledescription');
                }
            });
            ['data-ready', 'data-controls', 'data-paused', 'data-zoomed'].forEach((name) => root.removeAttribute(name));
        },
    };
}
