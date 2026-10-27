// Acun UI runtime, the one entry of the shared JavaScript. In the application's JavaScript:
//
//     import '@acunsoft/acun-ui';
//
// or, without a build step, the browser build dist/acun-ui.js (@acunUiScripts with Laravel). It
// sets up window.AcunUI once per page and installs, once:
// - the plugins: the components' behaviour in plain JavaScript, bound to their markup by data-*
//   attributes (core/plugins.js; modal and drawer in plugins/): the same in plain HTML, React,
//   Vue, Blade and Livewire, with no Alpine. They start on the page's elements, and a
//   MutationObserver starts added ones and stops removed ones (AcunUI.init(root) /
//   AcunUI.destroy(root) by hand; AcunUI.observe(false) turns the observer off). Other packages
//   define their own with AcunUI.plugins.define (Acun Pro);
// - the dialog behaviours: page scroll lock, focus trap and restore, Escape, nested dialogs
//   (behavior/), used by the modal, the drawer and acun:command-palette;
// - on a page with Alpine (Livewire brings it, adapters/alpine.js): the plugins' elements as
//   Alpine roots with the 2.0 scope (close(), wire:model / x-model) and the deprecated Alpine
//   data acunModal and acunDrawer, and on pages with acun:layout.head the theme and menu stores
//   (appearance/stores.js, a view of AcunUI.appearance);
// - on pages with acun:layout.head the appearance choices without Alpine: AcunUI.appearance (mode,
//   colour, skin, content width, menu; appearance/preferences.js), and AcunUI.isDark(element) and AcunUI.tokenColor(element, '--ac-line') for code
//   that draws in the theme's colours (charts);
// - the loading of heavy components in chunks of their own, the first time their element
//   (data-acun-component) is on the page (core/components.js; the PRO package defines its own);
// - the Livewire fixes (plugin state kept through morphs, select values, morphs after
//   wire:navigate, lists kept in view, a full page load when leaving a page that forces its layout).
//
// Importing it without a browser (server-side rendering: Next.js, Nuxt) does nothing: no window,
// document or storage is touched until it runs in a page.
//
// The theme is applied before the first paint by a separate, blocking script that
// acun:layout.head loads (boot.js); this module runs deferred, before Alpine starts.

import { VERSION, createRuntime } from './core/runtime.js';
import { EVENTS, commands } from './core/events.js';
import { readConfig } from './core/config.js';
import { focusable } from './core/dom.js';
import { connectLivewire, onLivewire, onNavigated } from './core/livewire.js';
import { components, deferLazyComponents, loadComponentsOnPage, watchLazyComponents } from './core/components.js';
import { destroy, init, observe, plugins, start as startPlugins } from './core/plugins.js';
import { scrollLock } from './behavior/scroll-lock.js';
import { focusFirst, trapTab } from './behavior/focus-trap.js';
import { rememberFocus } from './behavior/focus-restore.js';
import { forgetDetachedOverlays, installOverlayKeys, overlays } from './behavior/overlay-manager.js';
import { keepListsInView } from './behavior/keep-in-view.js';
import { reloadWhenLeavingForcedLayout } from './behavior/forced-layout.js';
import { syncSelectValues } from './behavior/select-sync.js';
import { skipDetachedMorphs } from './behavior/morph-guard.js';
import { registerStores } from './appearance/stores.js';
import { appearance, startAppearance } from './appearance/preferences.js';
import { isDark, tokenColor } from './appearance/theme.js';
import { modalPlugin } from './plugins/modal.js';
import { drawerPlugin } from './plugins/drawer.js';
import { connectAlpine, scope } from './adapters/alpine.js';
import { followAppearanceThroughNavigation, keepPluginStateThroughMorphs, runCloseActions } from './adapters/livewire.js';
import { drawer, drawerScope } from './components/drawer.js';
import { modal, modalScope } from './components/modal.js';

/** @import { AcunWindow, AlpineLike } from './core/types.js' */

// The types of the public API, for TypeScript users (packages/acun-ui/types/index.d.ts).
/** @typedef {import('./core/plugins.js').Plugins} Plugins */
/** @typedef {import('./core/plugins.js').PluginDefinition} PluginDefinition */
/** @typedef {import('./core/plugins.js').PluginInstance} PluginInstance */
/** @typedef {import('./core/plugins.js').PluginContext} PluginContext */
/** @typedef {import('./core/plugins.js').OpenOptions} OpenOptions */
/** @typedef {import('./plugins/modal.js').ModalInstance} ModalInstance */
/** @typedef {import('./plugins/drawer.js').DrawerInstance} DrawerInstance */
/** @typedef {import('./behavior/overlay-manager.js').OverlayOptions} OverlayOptions */
/** @typedef {import('./core/events.js').Commands} Commands */

// The public API: window.AcunUI.
const api = {
    events: EVENTS,
    scrollLock,
    overlays,
    focus: { first: focusFirst, trap: trapTab, remember: rememberFocus, focusable },
    drawer: commands('drawer'),
    modal: commands('modal'),
    plugins,
    // Starts / stops the plugins in root (default: the document); safe to call again.
    init,
    destroy,
    observe,
    components,
    load: components.load,
    // The appearance choices (mode, colour, skin, content width, menu) without Alpine: get(), subscribe(), setMode()…
    appearance,
    // Whether an element is in a dark theme (its nearest data-theme ends in "dark"), and a token's colour on it as rgb().
    isDark,
    tokenColor,
};

/**
 * window.AcunUI: the public API, its version and whether a copy of the runtime set it up.
 *
 * @typedef {typeof api & { version: string, initialized: true }} AcunUIRuntime
 */

/** @param {AlpineLike} Alpine */
function registerAlpine(Alpine) {
    deferLazyComponents(Alpine);
    connectAlpine(Alpine);
    // Deprecated (3.0): markup written with x-data="acunModal(…)" / "acunDrawer(…)".
    Alpine.data('acunDrawer', drawer);
    Alpine.data('acunModal', modal);

    // A first page without acun:layout.head (a sign-in page with a layout of its own): the stores come
    // with the first page that has it, reached with wire:navigate, before Alpine starts its elements.
    if (appearance.ready) registerStores(Alpine);
    else storesWaiting = Alpine;
}

/** @type {AlpineLike | null} Alpine, until the appearance choices start (a page with acun:layout.head) */
let storesWaiting = null;

// The first names of the scroll lock (docs before 0.1): still there, with a warning.
/** @param {{ scrollLock: typeof scrollLock }} runtime */
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

/**
 * @param {Record<string, any> & { scrollLock: typeof scrollLock }} runtime
 * @param {{ observe?: boolean }} options
 */
function setup(runtime, { observe: watch = true }) {
    installOverlayKeys();
    connectLivewire();
    onLivewire(syncSelectValues);
    onLivewire(skipDetachedMorphs);
    onLivewire(keepPluginStateThroughMorphs);
    onLivewire(runCloseActions);
    // The appearance choices: from this page's settings, or from the first page reached with wire:navigate that has them.
    const config = readConfig();
    if (config) startAppearance(config);
    followAppearanceThroughNavigation(() => {
        if (storesWaiting) registerStores(storesWaiting);
        storesWaiting = null;
    });
    keepListsInView();
    reloadWhenLeavingForcedLayout();
    onNavigated(forgetDetachedOverlays);
    keepDeprecatedNames(runtime);

    plugins.define('modal', modalPlugin);
    plugins.define('drawer', drawerPlugin);
    scope('modal', '[data-acun-modal]', modalScope);
    scope('drawer', '[data-acun-drawer]', drawerScope);
    startPlugins({ observe: watch });

    // Livewire's script runs before this module, so its Alpine is usually here already
    // (not started yet); an Alpine loaded on its own comes later, with alpine:init.
    let registered = false;
    const register = () => {
        const Alpine = /** @type {AcunWindow} */ (window).Alpine;
        if (registered || !Alpine) return;
        registered = true;
        registerAlpine(Alpine);
    };

    register();
    if (!registered) document.addEventListener('alpine:init', register, { once: true });

    // By alpine:init every package has defined its components: those already on the first page start loading.
    document.addEventListener('alpine:init', () => loadComponentsOnPage(), { once: true });
    // Without Alpine too: the page's lazy components and the ones added later (a component defined later checks the page then).
    watchLazyComponents({ observe: watch });

    // Other packages define their plugins now (AcunUI.plugins.define), whatever loaded first.
    document.dispatchEvent(new CustomEvent('acun:init', { detail: { AcunUI: runtime } }));
}

// Nothing runs without a browser (server-side rendering imports this module too).
const inBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';
// window.AcunUI = { observe: false } before this script: no MutationObserver (the page calls AcunUI.init / destroy).
const options = { observe: inBrowser && /** @type {AcunWindow} */ (window).AcunUI?.observe !== false };
const { runtime: running, created } = inBrowser
    ? createRuntime(api, (runtime) => setup(runtime, options))
    : { runtime: null, created: false };

// When another copy of the runtime runs the page (this one stays idle), what this module exports
// is that copy's: code importing it locks the scroll, opens dialogs and defines components on
// the instances in use, not on an idle copy nobody reads.
/** @type {Partial<AcunUIRuntime> | null} */
const other = created ? null : running;
const liveComponents = other?.components ?? components;
const livePlugins = other?.plugins ?? plugins;
const liveScrollLock = other?.scrollLock ?? scrollLock;
const liveOverlays = other?.overlays ?? overlays;
const liveFocusFirst = other?.focus?.first ?? focusFirst;
const liveTrapTab = other?.focus?.trap ?? trapTab;
const liveRememberFocus = other?.focus?.remember ?? rememberFocus;
const liveFocusable = other?.focus?.focusable ?? focusable;
const liveInit = other?.init ?? init;
const liveDestroy = other?.destroy ?? destroy;
const liveObserve = other?.observe ?? observe;

// acun:passkey-button: its Alpine data comes in a chunk of its own, on pages that have one.
if (inBrowser) liveComponents.define('passkey', () => import('./components/passkey.js').then(({ passkey }) => /** @type {AcunWindow} */ (window).Alpine?.data('acunPasskey', passkey)));

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
    livePlugins as plugins,
    liveInit as init,
    liveDestroy as destroy,
    liveObserve as observe,
    modalPlugin,
    drawerPlugin,
    // Deprecated (3.0): the Alpine data of the 2.0 drawer and modal; they delegate to the plugins.
    drawer,
    modal,
    syncSelectValues,
    skipDetachedMorphs,
    isDark,
    tokenColor,
};
