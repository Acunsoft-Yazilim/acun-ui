// Vite helpers of Acun UI, for the application's vite.config.js:
//
//     import { woff2Only } from '@acunsoft/acun-ui/vite';
//
//     plugins: [laravel({ input: […], fonts: [bunny('Public Sans', { … })] }), woff2Only(), tailwindcss()],

const WOFF_FACE = /@font-face\s*\{[^}]*?url\(\s*["']?([^"')]+\.woff)["']?\s*\)\s*format\(\s*["']woff["']\s*\)[^}]*\}\s*/g;

const baseName = (file) => String(file).split(/[\\/]/).pop();

/**
 * The fonts CSS without the @font-face rules of WOFF files: with `files`, only of those files
 * (the font build's own, by name), so the application's other WOFF fonts keep their rules.
 */
export function stripWoffFaces(css, files = null) {
    const names = files ? new Set([...files].map(baseName)) : null;

    return css.replace(WOFF_FACE, (rule, url) => (!names || names.has(baseName(url)) ? '' : rule));
}

/**
 * Keeps only the WOFF2 files of laravel-vite-plugin's font build (fonts: [bunny(…)]).
 *
 * The font providers send every face as WOFF2 and as WOFF, and the plugin writes them
 * as two @font-face rules with the same descriptors, the WOFF one last: the browser
 * takes the last one, so it downloaded the bigger WOFF files and left the WOFF2
 * preloads unused. Every browser Acun UI supports reads WOFF2; this drops the WOFF
 * files, their rules (fonts-*.css) and their entries (fonts-manifest.json).
 *
 * Only the font build's own WOFF files go (the ones fonts-manifest.json lists): an icon
 * font or another WOFF font of the application, and its @font-face rule, stay. Without
 * the manifest (no fonts: [...] in laravel()) nothing is touched.
 */
export function woff2Only() {
    return {
        name: 'acun-ui:woff2-only',
        // After laravel:fonts, which writes the fonts CSS and manifest in its own post generateBundle.
        enforce: 'post',
        apply: 'build',

        generateBundle(_options, bundle) {
            const manifestAsset = Object.values(bundle).find((asset) => asset.type === 'asset' && asset.fileName.endsWith('fonts-manifest.json'));
            if (!manifestAsset) return;

            const manifest = JSON.parse(String(manifestAsset.source));
            const fontFiles = woffFilesOf(manifest);
            const woff = Object.keys(bundle).filter((fileName) => fileName.endsWith('.woff') && fontFiles.has(baseName(fileName)));
            if (woff.length === 0) return;

            woff.forEach((fileName) => delete bundle[fileName]);

            for (const asset of Object.values(bundle)) {
                if (asset.type !== 'asset' || asset === manifestAsset) continue;

                if (asset.fileName.endsWith('.css') && String(asset.source).includes('@font-face')) {
                    asset.source = stripWoffFaces(String(asset.source), fontFiles);
                }
            }

            manifestAsset.source = JSON.stringify(withoutWoff(manifest, fontFiles), null, 2);
        },
    };
}

/** The names of the WOFF files the font build wrote (fonts-manifest.json's variants). */
function woffFilesOf(manifest) {
    const names = new Set();
    for (const family of Object.values(manifest.families ?? {})) {
        for (const variant of Object.values(family.variants ?? {})) {
            (variant.files ?? []).filter((file) => file.format === 'woff' && file.file).forEach((file) => names.add(baseName(file.file)));
        }
    }

    return names;
}

function withoutWoff(manifest, fontFiles) {
    const familyStyles = manifest.style?.familyStyles ?? {};
    Object.keys(familyStyles).forEach((alias) => {
        familyStyles[alias] = stripWoffFaces(familyStyles[alias], fontFiles);
    });

    if (typeof manifest.style?.inline === 'string') manifest.style.inline = stripWoffFaces(manifest.style.inline, fontFiles);

    for (const family of Object.values(manifest.families ?? {})) {
        for (const variant of Object.values(family.variants ?? {})) {
            variant.files = (variant.files ?? []).filter((file) => file.format !== 'woff');
        }
    }

    manifest.preloads = (manifest.preloads ?? []).filter((preload) => !String(preload.file ?? preload.url ?? '').endsWith('.woff'));

    return manifest;
}
