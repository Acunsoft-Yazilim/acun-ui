// Vite helpers of Acun UI, for the application's vite.config.js:
//
//     import { woff2Only } from '@acunsoft/acun-ui/vite';
//
//     plugins: [laravel({ input: […], fonts: [bunny('Public Sans', { … })] }), woff2Only(), tailwindcss()],

const WOFF_FACE = /@font-face\s*\{[^}]*?url\(\s*["']?[^"')]+\.woff["']?\s*\)\s*format\(\s*["']woff["']\s*\)[^}]*\}\s*/g;

/** The fonts CSS without the @font-face rules of WOFF files. */
export function stripWoffFaces(css) {
    return css.replace(WOFF_FACE, '');
}

/**
 * Keeps only the WOFF2 files of laravel-vite-plugin's font build (fonts: [bunny(…)]).
 *
 * The font providers send every face as WOFF2 and as WOFF, and the plugin writes them
 * as two @font-face rules with the same descriptors, the WOFF one last: the browser
 * takes the last one, so it downloaded the bigger WOFF files and left the WOFF2
 * preloads unused. Every browser Acun UI supports reads WOFF2; this drops the WOFF
 * files, their rules (fonts-*.css) and their entries (fonts-manifest.json).
 */
export function woff2Only() {
    return {
        name: 'acun-ui:woff2-only',
        // After laravel:fonts, which writes the fonts CSS and manifest in its own post generateBundle.
        enforce: 'post',
        apply: 'build',

        generateBundle(_options, bundle) {
            const woff = Object.keys(bundle).filter((fileName) => fileName.endsWith('.woff'));
            if (woff.length === 0) return;

            woff.forEach((fileName) => delete bundle[fileName]);

            for (const asset of Object.values(bundle)) {
                if (asset.type !== 'asset') continue;

                if (asset.fileName.endsWith('.css') && String(asset.source).includes('@font-face')) {
                    asset.source = stripWoffFaces(String(asset.source));
                } else if (asset.fileName.endsWith('fonts-manifest.json')) {
                    asset.source = JSON.stringify(withoutWoff(JSON.parse(String(asset.source))), null, 2);
                }
            }
        },
    };
}

function withoutWoff(manifest) {
    const familyStyles = manifest.style?.familyStyles ?? {};
    Object.keys(familyStyles).forEach((alias) => {
        familyStyles[alias] = stripWoffFaces(familyStyles[alias]);
    });

    if (typeof manifest.style?.inline === 'string') manifest.style.inline = stripWoffFaces(manifest.style.inline);

    for (const family of Object.values(manifest.families ?? {})) {
        for (const variant of Object.values(family.variants ?? {})) {
            variant.files = (variant.files ?? []).filter((file) => file.format !== 'woff');
        }
    }

    manifest.preloads = (manifest.preloads ?? []).filter((preload) => !String(preload.file ?? preload.url ?? '').endsWith('.woff'));

    return manifest;
}
