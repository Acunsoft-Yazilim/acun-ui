import type { AlpineLike } from './types.js';
/** @import { AlpineLike } from './types.js' */
declare const noop: () => void;
export declare const fallbackComponents: {
    acunDrawer: () => {
        rendered: boolean;
        entered: boolean;
        opened: boolean;
        model: boolean;
        overlay: boolean;
        openPanel: typeof noop;
        register: typeof noop;
        syncDock: typeof noop;
        close: typeof noop;
        followLink: typeof noop;
        focusableElements: () => never[];
    };
    acunModal: () => {
        open: boolean;
        model: boolean;
        show: typeof noop;
        close: typeof noop;
        requestClose: typeof noop;
        focusables: () => never[];
    };
};
/** @param {Record<string, any> | null} config */
export declare function fallbackStores(config: Record<string, any> | null): {
    sidebar: {
        collapsed: boolean;
        hover: boolean;
        hoverSuppressed: boolean;
        openHover: typeof noop;
        closeHover: typeof noop;
        toggle: typeof noop;
        set: typeof noop;
        setCollapsed: typeof noop;
    };
    navbar: {
        mode: any;
        set: typeof noop;
    };
    customizer: {
        skin: any;
        contentWidth: any;
        semiDark: boolean;
        setSkin: typeof noop;
        setContentWidth: typeof noop;
        setSemiDark: typeof noop;
        apply: typeof noop;
        reset: typeof noop;
    };
    brand: {
        color: any;
        set: typeof noop;
        forget: typeof noop;
    };
    appearance: {
        value: any;
        scope: any;
        set: typeof noop;
        toggle: typeof noop;
        apply: typeof noop;
    };
};
/**
 * @param {AlpineLike} Alpine
 * @param {Record<string, any> | null} config
 */
export declare function registerFallbacks(Alpine: AlpineLike, config: Record<string, any> | null): void;
export {};
