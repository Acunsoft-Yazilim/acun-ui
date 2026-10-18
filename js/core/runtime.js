// The one global of Acun UI: window.AcunUI, created once per page.
//
// A second copy of the runtime on the same page (the Vite build and @acunUiScripts
// together, or two Vite entries importing it) finds the namespace already set up and
// stays idle, so no listener and no Alpine component is registered twice.

// Follows the release version (packages/acun-ui/package.json); tests/js/runtime.test.mjs checks it.
export const VERSION = '2.1.0';

/**
 * Sets up window.AcunUI with the public API and runs setup() once per page.
 * Keeps what is already on the namespace (AcunUI.pro, when the PRO JS ran first).
 *
 * @returns {{ runtime: object, created: boolean }}
 */
export function createRuntime(api, setup) {
    const existing = window.AcunUI;

    if (existing?.initialized) {
        if (existing.version !== VERSION) {
            console.warn(`[Acun UI] Two different versions are on the page (${existing.version} and ${VERSION}); only the first one runs.`);
        }

        return { runtime: existing, created: false };
    }

    const runtime = Object.assign(existing ?? {}, api, { version: VERSION, initialized: true });
    window.AcunUI = runtime;
    setup(runtime);

    return { runtime, created: true };
}
