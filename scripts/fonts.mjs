// The kit's font library (fonts/): 20 Google Fonts families (the owner's choice, 2026-11-10), served from the package (no request to Google or any
// other font host). Run from the repository root: node packages/acun-ui/scripts/fonts.mjs
//
// Each family comes from its Fontsource package (the Google Fonts files, with their licence), pinned below, from the
// npm registry. Kept: the latin and latin-ext subsets (latin-ext has the Turkish ğ, ş, ı and İ; ç, ö and ü are in
// latin) in the weights the kit uses (400 regular, 500 medium, 600 semibold, 700 bold; those the family has), WOFF2 only, and the
// family's LICENSE (SIL Open Font License 1.1). fonts/fonts.json lists them with each subset's unicode-range, read
// by Acun\Ui\Support\FontLibrary. Running it again gives the same files.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const VERSION = '5.3.0';
const WEIGHTS = [400, 500, 600, 700];
/**
 * id => [family, category, options]; the id is the family in kebab case (laravel-vite-plugin's alias, Fontsource's
 * id). options: version (else VERSION), weights (else WEIGHTS: a family without some of them, Lato's 400 and 700; a
 * browser then takes the nearest, 600 → 700).
 */
const FAMILIES = {
    'public-sans': ['Public Sans', 'sans-serif'],
    inter: ['Inter', 'sans-serif'],
    manrope: ['Manrope', 'sans-serif'],
    'dm-sans': ['DM Sans', 'sans-serif'],
    'plus-jakarta-sans': ['Plus Jakarta Sans', 'sans-serif'],
    roboto: ['Roboto', 'sans-serif'],
    'open-sans': ['Open Sans', 'sans-serif'],
    'source-sans-3': ['Source Sans 3', 'sans-serif', { version: '5.3.1' }],
    'nunito-sans': ['Nunito Sans', 'sans-serif'],
    lato: ['Lato', 'sans-serif', { weights: [400, 700] }],
    montserrat: ['Montserrat', 'sans-serif'],
    poppins: ['Poppins', 'sans-serif'],
    'work-sans': ['Work Sans', 'sans-serif'],
    'ibm-plex-sans': ['IBM Plex Sans', 'sans-serif'],
    'noto-sans': ['Noto Sans', 'sans-serif'],
    outfit: ['Outfit', 'sans-serif'],
    lora: ['Lora', 'serif'],
    merriweather: ['Merriweather', 'serif'],
    'playfair-display': ['Playfair Display', 'serif'],
    'source-serif-4': ['Source Serif 4', 'serif', { version: '5.3.1' }],
};
const SUBSETS = ['latin', 'latin-ext'];

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const target = path.join(root, 'fonts');
const work = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-fonts-'));

/** The unicode-range of a subset in a Fontsource weight file (400.css). */
function unicodeRange(css, id, subset, weight) {
    const block = css.split('/* ').find((part) => part.startsWith(`${id}-${subset}-${weight}-normal */`));
    const range = block?.match(/unicode-range:\s*([^;]+);/)?.[1]?.trim();
    if (!range) throw new Error(`${id}: no unicode-range for ${subset} ${weight}`);

    return range;
}

try {
    fs.rmSync(target, { recursive: true, force: true });
    fs.mkdirSync(target, { recursive: true });
    const library = {};

    for (const [id, [family, category, { version = VERSION, weights = WEIGHTS } = {}]] of Object.entries(FAMILIES)) {
        const spec = `@fontsource/${id}@${version}`;
        const tarball = execFileSync('npm', ['pack', spec, '--silent', '--pack-destination', work], { encoding: 'utf8' }).trim().split('\n').pop();
        const unpacked = path.join(work, id);
        fs.mkdirSync(unpacked);
        execFileSync('tar', ['xzf', path.join(work, tarball), '-C', unpacked]);
        const source = path.join(unpacked, 'package');
        const metadata = JSON.parse(fs.readFileSync(path.join(source, 'package.json'), 'utf8'));
        if (metadata.license !== 'OFL-1.1') throw new Error(`${id}: licence ${metadata.license}, not OFL-1.1`);

        const folder = path.join(target, id);
        fs.mkdirSync(folder);
        fs.copyFileSync(path.join(source, 'LICENSE'), path.join(folder, 'LICENSE.txt'));

        const subsets = {};
        for (const weight of weights) {
            const css = fs.readFileSync(path.join(source, `${weight}.css`), 'utf8');
            for (const subset of SUBSETS) {
                const file = `${id}-${subset}-${weight}-normal.woff2`;
                fs.copyFileSync(path.join(source, 'files', file), path.join(folder, file));
                const range = unicodeRange(css, id, subset, weight);
                if (subsets[subset] !== undefined && subsets[subset] !== range) throw new Error(`${id}: ${subset} differs between weights`);
                subsets[subset] = range;
            }
        }

        library[id] = { family, category, weights, subsets, license: 'OFL-1.1', source: `${spec} (Google Fonts)` };
        console.log(`${family}: ${weights.length * SUBSETS.length} files`);
    }

    fs.writeFileSync(path.join(target, 'fonts.json'), `${JSON.stringify(library, null, 4)}\n`);
} finally {
    fs.rmSync(work, { recursive: true, force: true });
}
