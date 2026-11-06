import Swiper from 'swiper';
export type CarouselInstance = {
    element: HTMLElement;
    /**
     * the Swiper instance (its API beyond the kit's)
     */
    swiper: Swiper;
    /**
     * the thumbnails' Swiper (data-thumbs)
     */
    thumbs: Swiper | null;
    /**
     * the shown slide (with several in view: the first)
     */
    index: number;
    /**
     * the number of slides
     */
    count: number;
    /**
     * the auto-advance was paused by the user
     */
    paused: boolean;
    next: () => void;
    previous: () => void;
    goTo: (index: number) => void;
    pause: () => void;
    play: () => void;
    /**
     * shows a set (acun:carousel.set) in place of the slides
     */
    showSet: (name: string) => boolean;
    /**
     * opens the full-screen view (data-fullscreen)
     */
    openFullscreen: () => void;
    /**
     * measures again (after a change the observers do not see)
     */
    update: () => void;
    destroy: () => void;
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
export declare function mount(root: HTMLElement): CarouselInstance | {
    element: HTMLElement;
    destroy(): void;
};
