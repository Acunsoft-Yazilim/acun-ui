export type OverlayOptions = {
    /**
     * closes it (Escape)
     */
    close?: () => void;
    /**
     * locks the page scroll (default true)
     */
    lockScroll?: boolean;
    /**
     * the element Tab stays in (none: null)
     */
    trap?: HTMLElement | null | (() => HTMLElement | null);
    /**
     * where an Escape belongs to it (default 'inside')
     */
    escape?: 'inside' | 'anywhere' | false | (() => 'inside' | 'anywhere' | false);
    /**
     * gives the focus back when it closes (default true)
     */
    restoreFocus?: boolean;
    /**
     * the element the focus goes back to (default: the one focused now)
     */
    opener?: Element | null;
};
export type OverlayEntry = {
    element: HTMLElement;
    close: () => void;
    trap: OverlayOptions['trap'] | null;
    escape: NonNullable<OverlayOptions['escape']>;
    locked: boolean;
    restore: (() => void) | null;
    zIndex: string | null;
};
export declare const overlays: {
    /**
     * Registers an open dialog. element: its outermost element (the layer).
     * options.close(): closes it (Escape) · lockScroll (true) · trap: the element Tab
     * stays in (or a function returning it, null for none) · escape: 'inside' (default),
     * 'anywhere' or false · restoreFocus (true) · opener: the element the focus goes back to
     * (default: the one focused now).
     *
     * @param {HTMLElement | null | undefined} element
     * @param {OverlayOptions} [options]
     */
    open(element: HTMLElement | null | undefined, options?: OverlayOptions): void;
    /**
     * Unregisters it: releases the scroll lock and gives the focus back.
     *
     * @param {Element | null | undefined} element
     */
    close(element: Element | null | undefined): void;
    /** @param {Element} element */
    isOpen(element: Element): boolean;
    /**
     * The newest open dialog's element.
     *
     * @returns {HTMLElement | null}
     */
    top(): HTMLElement | null;
    readonly count: number;
};
/** Called once by the runtime. */
export declare function installOverlayKeys(): void;
/** After a wire:navigate page change: dialogs of the old page are gone; one kept by @persist stays. */
export declare function forgetDetachedOverlays(): void;
