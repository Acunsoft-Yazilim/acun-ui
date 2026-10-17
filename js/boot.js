// The blocking script of acun:layout.head (dist/acun-ui-boot.js, served by the
// package): applies the theme, primary colour, skin and menu width before the first
// paint, from the config island printed just before it. Nothing else: the runtime
// (index.js) does the rest once the page is parsed.

import { readConfig } from './core/config.js';
import { registerFallbacks } from './core/fallback.js';
import { applyInitialAppearance } from './appearance/theme.js';

const config = readConfig();
applyInitialAppearance(config);

// The runtime has to be there before Alpine starts. Without it: one error that says
// what to do, and stand-ins instead of an error from every expression.
document.addEventListener('alpine:init', () => {
    if (window.AcunUI?.initialized || !window.Alpine) return;

    console.error('[Acun UI] JavaScript is not loaded: add "import \'@acunsoft/acun-ui\';" to resources/js/app.js and build the assets (npm run build), or add @acunUiScripts to your layout. Drawers, modals and the theme settings will not work.');
    registerFallbacks(window.Alpine, config);
}, { once: true });
