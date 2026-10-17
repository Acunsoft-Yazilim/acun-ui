// Stand-ins for the runtime's Alpine components and stores, for a page whose runtime
// did not load: the boot script registers them next to the one console error that names
// the problem. Drawers and modals stay closed, the layout shows the saved appearance,
// and no expression throws "acunDrawer is not defined" or reads an undefined store.
// Same names as the real ones (tests/js/runtime.test.mjs compares them).

import { initialPreferences } from '../appearance/theme.js';

const noop = () => {};

export const fallbackComponents = {
    acunDrawer: () => ({ rendered: false, entered: false, opened: false, model: false, overlay: false, openPanel: noop, register: noop, syncDock: noop, close: noop, followLink: noop, focusableElements: () => [] }),
    acunModal: () => ({ open: false, model: false, show: noop, close: noop, requestClose: noop, focusables: () => [] }),
};

export function fallbackStores(config) {
    const start = initialPreferences(config);

    return {
        sidebar: { collapsed: start.sidebarCollapsed, hover: false, hoverSuppressed: false, openHover: noop, closeHover: noop, toggle: noop, set: noop, setCollapsed: noop },
        navbar: { mode: start.navbarMode, set: noop },
        customizer: { skin: start.skin, contentWidth: start.contentWidth, semiDark: start.semiDark, setSkin: noop, setContentWidth: noop, setSemiDark: noop, apply: noop, reset: noop },
        brand: { color: start.brandColor, set: noop, forget: noop },
        appearance: { value: start.mode, set: noop, toggle: noop, apply: noop },
    };
}

export function registerFallbacks(Alpine, config) {
    Object.entries(fallbackComponents).forEach(([name, component]) => Alpine.data(name, component));
    if (config) Object.entries(fallbackStores(config)).forEach(([name, store]) => Alpine.store(name, store));
}
