// Page scroll lock for dialogs (modal, drawer, command palette). The page itself
// scrolls, so an open dialog locks it (panel.css: html.scroll-locked). Counted: with
// nested dialogs the page is released only when the last one closes.

const CLASS = 'scroll-locked';
let count = 0;

export const scrollLock = {
    lock() {
        if (count++ === 0) document.documentElement.classList.add(CLASS);
    },

    unlock() {
        if (count > 0 && --count === 0) document.documentElement.classList.remove(CLASS);
    },

    /** No dialog open any more (a wire:navigate page change). */
    reset() {
        count = 0;
        document.documentElement.classList.remove(CLASS);
    },

    get count() {
        return count;
    },

    get locked() {
        return count > 0;
    },
};
