#!/usr/bin/env node
// Palette and dark: classes → Acun UI token utilities, in Blade, PHP and JS files.
//
//   node vendor/acunsoft/acun-ui/scripts/codemod-tokens.mjs resources/views
//   node vendor/acunsoft/acun-ui/scripts/codemod-tokens.mjs --dry resources/views/admin   (prints, writes nothing)
//   node vendor/acunsoft/acun-ui/scripts/codemod-tokens.mjs --exclude file-icon --exclude shapes/ resources/views
//
// The kit's own components were converted with these rules. A colour class and its dark: twin
// (the same property and state, in the same string) become one token utility that follows the
// theme by itself, and the dark: class is deleted:
//
//   text-gray-500 dark:text-zinc-400        → text-muted
//   bg-white dark:bg-zinc-900               → bg-surface
//   border-gray-200 dark:border-zinc-700    → border-line
//   hover:bg-gray-100 dark:hover:bg-zinc-800 → hover:bg-hover
//   bg-red-50 dark:bg-red-500/10            → bg-danger-tint
//   text-brand-600 dark:text-brand-400      → text-primary-ink
//   focus-visible:outline-brand-600         → focus-visible:outline-focus
//   bg-emerald-solid text-white             → bg-success text-on-success
//
// Classes no rule covers are left as they are and listed at the end (file:line class), for a
// person to decide: a tone map (a component's variant → classes), a section that is dark in both
// themes (data-theme="dark" on it instead), a data colour (a file type, a chart series) or a pair
// whose two halves do not mean the same role. The tokens are in css/tokens.css.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

/** The palette the kit's panel.css defines (and Tailwind's own names an app may use). */
const PALETTE = 'gray|zinc|neutral|slate|stone|brand|accent|emerald|sky|amber|red|secondary|orange|violet|fuchsia|rose|green|blue|yellow|purple|pink|indigo|teal|cyan|lime';

/** Tone of a palette name: brand is the primary colour, emerald success… (Acun\Ui\Support\Tone). */
const TONES = { brand: 'primary', accent: 'accent', emerald: 'success', red: 'danger', amber: 'warning', sky: 'info', secondary: 'secondary' };

/** Colour properties by family: text-like, background-like and line-like utilities. */
const FAMILY = {
    text: 'text', decoration: 'text', caret: 'text',
    bg: 'bg', fill: 'bg', from: 'bg', via: 'bg', to: 'bg',
    border: 'line', 'border-x': 'line', 'border-y': 'line', 'border-t': 'line', 'border-b': 'line', 'border-l': 'line', 'border-r': 'line', 'border-s': 'line', 'border-e': 'line',
    ring: 'line', 'ring-offset': 'line', 'inset-ring': 'line', divide: 'line', outline: 'line', stroke: 'line', accent: 'line',
};

const PROPERTY = Object.keys(FAMILY).sort((a, b) => b.length - a.length).join('|');
const UTILITY = new RegExp(`^(-?)(${PROPERTY})-((?:${PALETTE})-(?:\\d{2,3}|solid)|white|black|dark|light|transparent|current|inherit)(?:/(\\d+(?:\\.\\d+)?))?(!?)$`);
/** Not a colour of the palette: kept, and a dark: twin that repeats it is deleted. */
const KEYWORDS = ['transparent', 'current', 'inherit'];

/** Variants are split at the colons outside [...] and (...): has-[input:disabled]:bg-gray-50. */
const SPLIT = /:(?![^[]*\])(?![^(]*\))/;

/** An interactive state: the hover role instead of the resting one (bg-sunken → hover:bg-hover). */
const INTERACTIVE = /(?:^|[-[])(?:hover|focus|active|selected|current|open)/;
/** A disabled or read-only field. */
const DISABLED = /disabled|readonly/;
/** A focus state: a brand line there is the focus ring. */
const FOCUS = /(?:^|-)focus/;

/**
 * Pair rules: [family, light colour, dark twins, token]. A dark twin may carry its own opacity
 * (zinc-800/50); otherwise an opacity on both halves (border-gray-200/80 dark:border-zinc-800/80)
 * stays on the token (border-line/80). A token object picks by state: { rest, interactive, disabled }.
 */
const PAIRS = [
    // Text.
    ['text', 'gray-900', ['zinc-100', 'zinc-50', 'white'], 'ink'],
    ['text', 'gray-800', ['zinc-100', 'zinc-200', 'white'], 'ink'],
    ['text', 'gray-700', ['zinc-300', 'zinc-200'], 'body'],
    ['text', 'gray-600', ['zinc-400'], 'dim'],
    // In the dark theme dim is muted's colour: a hover that brightens there takes body.
    ['text', 'gray-600', ['zinc-300', 'zinc-200'], { rest: 'dim', interactive: 'body' }],
    ['text', 'gray-500', ['zinc-400', 'zinc-500'], 'muted'],
    ['text', 'gray-400', ['zinc-500', 'zinc-600'], 'faint'],
    ['text', 'gray-300', ['zinc-600'], 'line-strong'],
    // Surfaces.
    ['bg', 'white', ['zinc-900'], 'surface'],
    ['bg', 'white', ['zinc-800'], 'field'],
    ['bg', 'gray-50', ['zinc-950'], 'page'],
    ['bg', 'gray-50', ['zinc-800/50'], 'sunken/50'],
    ['bg', 'gray-50', ['zinc-800/60'], { rest: 'sunken/60', interactive: 'hover/60' }],
    ['bg', 'gray-50', ['zinc-800'], { interactive: 'hover' }],
    ['bg', 'gray-50', ['zinc-900'], { disabled: 'field-disabled' }],
    ['bg', 'gray-100', ['zinc-800'], { rest: 'sunken', interactive: 'hover' }],
    ['bg', 'gray-200', ['zinc-700'], 'track'],
    ['bg', 'gray-300', ['zinc-600'], 'line-strong'],
    ['bg', 'gray-900', ['zinc-700', 'zinc-800'], 'inverse'],
    // Lines (border, ring, divide, outline).
    ['line', 'gray-200', ['zinc-700', 'zinc-800'], 'line'],
    ['line', 'gray-100', ['zinc-800'], 'line-soft'],
    ['line', 'gray-300', ['zinc-600'], 'line-strong'],
    ['line', 'gray-400', ['zinc-500'], 'line-hover'],
    ['line', 'gray-900', ['zinc-100'], 'ink'],
    ['line', 'white', ['zinc-900'], 'surface'],
    // The dark tone (navy grey; a neutral grey in the dark theme).
    ['text', 'dark', ['zinc-300'], 'dark-ink'],
    ['text', 'dark', ['zinc-200'], 'dark-tint-ink'],
    ['bg', 'dark/10', ['zinc-800'], 'dark-tint'],
    ['bg', 'dark/10', ['zinc-700'], 'dark-soft'],
    ['bg', 'dark', ['zinc-700'], 'dark'],
    ['bg', 'dark', ['zinc-300'], 'dark-line'],
    ['line', 'dark', ['zinc-300'], 'dark-line'],
    // Every tone the same way: brand → primary, emerald → success, red → danger…
    ...Object.entries(TONES).flatMap(([palette, tone]) => [
        ['text', `${palette}-600`, [`${palette}-400`, `${palette}-500`, ...(tone === 'secondary' ? [`${palette}-300`] : [])], `${tone}-ink`],
        ['text', `${palette}-700`, [`${palette}-300`, `${palette}-400`], `${tone}-tint-ink`],
        ['text', `${palette}-800`, [`${palette}-300`], `${tone}-tint-ink`],
        ['bg', `${palette}-50`, [`${palette}-500/10`], `${tone}-tint`],
        ['bg', `${palette}-100`, [`${palette}-500/15`], `${tone}-soft`],
        ['line', `${palette}-500`, [`${palette}-400`], `${tone}-line`],
        ['line', `${palette}-600`, [`${palette}-400`], `${tone}-ink`],
    ]),
];

/** The solid fill of each tone (bg-emerald-solid → bg-success); its text becomes text-on-{tone}. */
const FILLS = { 'brand-600': 'primary', 'emerald-solid': 'success', 'red-600': 'danger', 'amber-solid': 'warning', 'sky-solid': 'info', 'secondary-500': 'secondary', dark: 'dark' };

/**
 * Single rules (no dark twin): [family, colour, token, test of the variants]. A focus frame in
 * the brand colour is the focus token; the invalid frame and the required asterisk are the
 * danger line; a solid fill is its tone; the status colours' 500 shade (icons, outline frames) is
 * the tone's line.
 */
const SINGLES = [
    ['line', 'brand-600', 'focus', (variants, property) => FOCUS.test(variants) || property === 'outline'],
    ['line', 'gray-400', 'line-hover', (variants, property) => property === 'outline'],
    ['line', 'red-400', 'danger-line'],
    ['line', 'red-500', 'danger-line'],
    ['text', 'red-500', 'danger-line'],
    ...Object.entries(TONES).filter(([palette]) => palette !== 'brand').map(([palette, tone]) => ['line', `${palette}-500`, `${tone}-line`, (variants, property) => property === 'outline']),
    ...Object.entries(FILLS).map(([colour, tone]) => ['bg', colour, tone, (variants) => !INTERACTIVE.test(variants)]),
];

/** The tokens that are a fill with a text colour of their own (bg-success → text-on-success). */
const ON = { primary: 'on-primary', secondary: 'on-secondary', success: 'on-success', info: 'on-info', warning: 'on-warning', danger: 'on-danger', dark: 'on-dark', accent: 'on-accent', inverse: 'on-inverse' };

/** "hover:bg-gray-100" → { word, variants (without dark:), dark, property, family, colour, alpha }; null when it is no colour class. */
export function parse(word) {
    const parts = word.split(SPLIT);
    const utility = parts.pop().replace(/^!/, '');
    const match = utility.match(UTILITY);
    if (!match) return null;
    const dark = parts.includes('dark');
    const variants = parts.filter((part) => part !== 'dark');

    return {
        word,
        variants: variants.join(':'),
        dark,
        negative: match[1],
        property: match[2],
        family: FAMILY[match[2]],
        colour: match[3],
        alpha: match[4] ?? null,
        important: match[5],
    };
}

const withAlpha = (colour, alpha) => (alpha ? `${colour}/${alpha}` : colour);
function pick(token, variants) {
    if (typeof token === 'string') return token;
    if (DISABLED.test(variants)) return token.disabled;
    return INTERACTIVE.test(variants) ? token.interactive : token.rest;
}

/** The token for a class and its dark twin (null when no rule says). */
export function pairToken(base, twin) {
    const light = withAlpha(base.colour, base.alpha);
    const dark = withAlpha(twin.colour, twin.alpha);
    for (const [family, colour, twins, token] of PAIRS) {
        if (family !== base.family) continue;
        // The same opacity on both halves stays on the token.
        if (colour === base.colour && base.alpha && base.alpha === twin.alpha && twins.includes(twin.colour)) {
            const name = pick(token, base.variants);
            if (name && !name.includes('/')) return `${name}/${base.alpha}`;
        }
        if (colour === light && twins.includes(dark)) {
            const name = pick(token, base.variants);
            if (name) return name;
        }
    }

    return null;
}

/** The token for a class without a dark twin (null when no rule says). */
export function singleToken(base) {
    if (base.alpha || KEYWORDS.includes(base.colour)) return null;
    for (const [family, colour, token, test] of SINGLES) {
        if (family === base.family && colour === base.colour && (!test || test(base.variants, base.property))) return token;
    }

    return null;
}

/** The class with its colour replaced by token, and without dark:. */
function rebuild(cls, token) {
    const parts = cls.word.split(SPLIT);
    const utility = parts.pop();
    const variants = parts.filter((part) => part !== 'dark').map((part) => `${part}:`).join('');

    return `${variants}${utility.startsWith('!') ? '!' : ''}${cls.negative}${cls.property}-${token}${cls.important}`;
}

/** Converts one line; returns [line, converted count, leftovers]. */
export function convertLine(line) {
    // Pairs are looked for inside one string (a class attribute, a PHP string), split at the quotes.
    const words = [];
    let segment = 0;
    for (const match of line.matchAll(/[^\s'"`]+|['"`]/g)) {
        if (/^['"`]$/.test(match[0])) {
            segment++;
            continue;
        }
        const cls = parse(match[0]);
        if (cls) words.push({ ...cls, start: match.index, end: match.index + match[0].length, segment });
    }

    const edits = new Map();
    const used = new Set();
    for (const base of words.filter((cls) => !cls.dark)) {
        const twin = words.find((cls) => cls.dark && !used.has(cls) && cls.segment === base.segment && cls.variants === base.variants && cls.property === base.property);
        // A twin is taken by its base even when no rule converts the pair, so it never pairs with another class.
        if (twin) used.add(twin);
        // A dark: twin that repeats its base (dark:hover:bg-brand-600 next to hover:bg-brand-600) says nothing.
        const same = twin && twin.colour === base.colour && twin.alpha === base.alpha;
        if (same) edits.set(twin, null);
        const token = twin && !same ? pairToken(base, twin) : singleToken(base);
        if (!token) continue;
        base.token = token;
        // bg-dark is already the dark tone's token: nothing to write but its dark: twin.
        if (rebuild(base, token) !== base.word) edits.set(base, rebuild(base, token));
        else if (!twin || same) continue;
        if (twin && !same) edits.set(twin, null);
    }

    // White text on a fill: the fill's own text colour (bg-success text-white → text-on-success).
    for (const base of words.filter((cls) => cls.property === 'text' && cls.colour === 'white' && !edits.has(cls) && !cls.dark)) {
        const fill = words.find((cls) => cls.segment === base.segment && cls.family === 'bg' && cls.variants === base.variants && cls.token && ON[cls.token]);
        if (!fill) continue;
        edits.set(base, rebuild(base, withAlpha(ON[fill.token], base.alpha)));
        const twin = words.find((cls) => cls.dark && !used.has(cls) && cls.segment === base.segment && cls.variants === base.variants && cls.property === 'text' && cls.colour === 'white' && cls.alpha === base.alpha);
        if (twin) {
            used.add(twin);
            edits.set(twin, null);
        }
    }

    let result = line;
    const ordered = [...edits.entries()].sort(([a], [b]) => b.start - a.start);
    for (const [cls, replacement] of ordered) {
        if (replacement === null) {
            // Delete the dark: class with one space next to it.
            let [start, end] = [cls.start, cls.end];
            if (result[end] === ' ') end++;
            else if (result[start - 1] === ' ') start--;
            result = result.slice(0, start) + result.slice(end);
        } else {
            result = result.slice(0, cls.start) + replacement + result.slice(cls.end);
        }
    }

    // dark (bg-dark, border-dark/10…) is the dark tone's token itself; only a dark: class beside it is left.
    const leftovers = words.filter((cls) => !edits.has(cls) && !KEYWORDS.includes(cls.colour) && !(cls.colour === 'dark' && !cls.dark && !used.has(cls))).map((cls) => cls.word);

    return [result, edits.size, leftovers];
}

function* walk(target, excludes) {
    const stat = fs.statSync(target);
    if (excludes.some((part) => target.includes(part))) return;
    if (stat.isDirectory()) {
        for (const name of fs.readdirSync(target).sort()) yield* walk(path.join(target, name), excludes);
    } else if (/\.(?:php|js|mjs|vue|jsx|tsx|html)$/.test(target)) {
        yield target;
    }
}

function main(argv) {
    const dry = argv.includes('--dry');
    const excludes = argv.flatMap((arg, i) => (argv[i - 1] === '--exclude' ? [arg] : []));
    const targets = argv.filter((arg, i) => !arg.startsWith('--') && argv[i - 1] !== '--exclude');
    if (!targets.length) {
        console.error('Usage: node codemod-tokens.mjs [--dry] [--exclude <part of a path>]… <folder or file>…');
        process.exit(1);
    }

    let changed = 0;
    let classes = 0;
    const left = [];
    for (const target of targets) {
        for (const file of walk(path.resolve(target), excludes)) {
            const source = fs.readFileSync(file, 'utf8');
            let count = 0;
            const lines = source.split('\n').map((line, i) => {
                const [converted, n, leftovers] = convertLine(line);
                count += n;
                leftovers.forEach((word) => left.push(`${path.relative(process.cwd(), file)}:${i + 1} ${word}`));
                return converted;
            });
            if (count) {
                changed++;
                classes += count;
                if (!dry) fs.writeFileSync(file, lines.join('\n'));
                console.log(`${dry ? 'would convert' : 'converted'} ${String(count).padStart(3)}  ${path.relative(process.cwd(), file)}`);
            }
        }
    }

    console.log(`\n${classes} classes in ${changed} files${dry ? ' (dry run: nothing written)' : ''}.`);
    if (left.length) console.log(`\nLeft for a person (${left.length}):\n${left.map((line) => `  ${line}`).join('\n')}`);
}

if (process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1])) {
    main(process.argv.slice(2));
}
