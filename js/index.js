// Acun UI runtime, the one entry of the shared JavaScript. In the application's app.js:
//
//     import '@acunsoft/acun-ui';
//
// Without Vite, @acunUiScripts loads the same code as a browser build. It sets up
// window.AcunUI once per page and installs, once:
// - the dialog behaviours: page scroll lock, focus trap and restore, Escape, nested
//   dialogs (behavior/), used by acun:drawer, acun:modal and acun:command-palette;
// - the Alpine components acunDrawer and acunModal (acunPasskey is a chunk of its own), and on pages with
//   acun:layout.head the theme and menu stores (appearance/), and AcunUI.isDark(element) and
//   AcunUI.tokenColor(element, '--ac-line') for code that draws in the theme's colours (charts);
// - the loading of heavy components in chunks of their own, the first time their element
//   (data-acun-component) is on the page (core/components.js; the PRO package defines its own);
// - the Livewire fixes (select values, morphs after wire:navigate, lists kept in view,
//   a full page load when leaving a page that forces its layout).
//
// The theme is applied before the first paint by a separate, blocking script that
// acun:layout.head loads (boot.js); this module runs deferred, before Alpine starts.

import { VERSION, createRuntime } from './core/runtime.js';
import { EVENTS, commands } from './core/events.js';
import { readConfig } from './core/config.js';
import { focusable } from './core/dom.js';
import { connectLivewire, onLivewire, onNavigated } from './core/livewire.js';
import { components, deferLazyComponents, loadComponentsOnPage } from './core/components.js';
import { scrollLock } from './behavior/scroll-lock.js';
import { focusFirst, trapTab } from './behavior/focus-trap.js';
import { rememberFocus } from './behavior/focus-restore.js';
import { forgetDetachedOverlays, installOverlayKeys, overlays } from './behavior/overlay-manager.js';
import { keepListsInView } from './behavior/keep-in-view.js';
import { reloadWhenLeavingForcedLayout } from './behavior/forced-layout.js';
import { syncSelectValues } from './behavior/select-sync.js';
import { skipDetachedMorphs } from './behavior/morph-guard.js';
import { registerStores } from './appearance/stores.js';
import { isDark, tokenColor } from './appearance/theme.js';
import { drawer } from './components/drawer.js';
import { modal } from './components/modal.js';

// The public API: window.AcunUI.
const api = {
    events: EVENTS,
    scrollLock,
    overlays,
    focus: { first: focusFirst, trap: trapTab, remember: rememberFocus, focusable },
    drawer: commands('drawer'),
    modal: commands('modal'),
    components,
    load: components.load,
    // Whether an element is in a dark theme (its nearest data-theme ends in "dark"), and a token's colour on it as rgb().
    isDark,
    tokenColor,
};

function registerAlpine(Alpine) {
    deferLazyComponents(Alpine);
    Alpine.data('acunDrawer', drawer);
    Alpine.data('acunModal', modal);

    const config = readConfig();
    if (config) registerStores(Alpine, config);
    else registerStoresLater(Alpine);
}

// A first page without acun:layout.head (a sign-in page with a layout of its own): the stores come
// with the first page that has it, reached with wire:navigate, before Alpine starts its elements.
function registerStoresLater(Alpine) {
    const register = (event) => {
        if (typeof event.detail?.onSwap !== 'function') return;

        event.detail.onSwap(() => {
            const config = readConfig();
            if (!config) return;

            document.removeEventListener('livewire:navigating', register);
            registerStores(Alpine, config);
        });
    };

    document.addEventListener('livewire:navigating', register);
}

// The first names of the scroll lock (docs before 0.1): still there, with a warning.
function keepDeprecatedNames(runtime) {
    let warned = false;

    Object.defineProperty(window, 'acunScrollLock', {
        configurable: true,
        get() {
            if (!warned) console.warn('[Acun UI] Use AcunUI.scrollLock instead of window.acunScrollLock.');
            warned = true;

            return runtime.scrollLock;
        },
    });
}

const { runtime: running, created } = createRuntime(api, (runtime) => {
    installOverlayKeys();
    connectLivewire();
    onLivewire(syncSelectValues);
    onLivewire(skipDetachedMorphs);
    keepListsInView();
    reloadWhenLeavingForcedLayout();
    onNavigated(forgetDetachedOverlays);
    keepDeprecatedNames(runtime);

    // Livewire's script runs before this module, so its Alpine is usually here already
    // (not started yet); an Alpine loaded on its own comes later, with alpine:init.
    let registered = false;
    const register = () => {
        if (registered || !window.Alpine) return;
        registered = true;
        registerAlpine(window.Alpine);
    };

    register();
    if (!registered) document.addEventListener('alpine:init', register, { once: true });

    // By alpine:init every package has defined its components: those already on the first page start loading.
    document.addEventListener('alpine:init', () => loadComponentsOnPage(), { once: true });
});

// When another copy of the runtime runs the page (this one stays idle), what this module exports
// is that copy's: code importing it locks the scroll, opens dialogs and defines components on
// the instances in use, not on an idle copy nobody reads.
const other = created ? null : running;
const liveComponents = other?.components ?? components;
const liveScrollLock = other?.scrollLock ?? scrollLock;
const liveOverlays = other?.overlays ?? overlays;
const liveFocusFirst = other?.focus?.first ?? focusFirst;
const liveTrapTab = other?.focus?.trap ?? trapTab;
const liveRememberFocus = other?.focus?.remember ?? rememberFocus;
const liveFocusable = other?.focus?.focusable ?? focusable;

// acun:passkey-button: its Alpine data comes in a chunk of its own, on pages that have one.
liveComponents.define('passkey', () => import('./components/passkey.js').then(({ passkey }) => window.Alpine.data('acunPasskey', passkey)));

export {
    VERSION,
    EVENTS,
    liveComponents as components,
    liveScrollLock as scrollLock,
    liveOverlays as overlays,
    liveFocusFirst as focusFirst,
    liveTrapTab as trapTab,
    liveRememberFocus as rememberFocus,
    liveFocusable as focusable,
    drawer,
    modal,
    syncSelectValues,
    skipDetachedMorphs,
    isDark,
    tokenColor,
};
