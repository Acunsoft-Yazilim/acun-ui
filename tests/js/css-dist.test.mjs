import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BANNER_PATTERN, BUILDS, sourceHash } from '../../../../scripts/build-css.mjs';

// The compiled stylesheets for projects without Tailwind (npm run build:css): dist/acun-ui.css,
// dist/acun-ui-core.css and Acun Pro's dist/acun-pro.css, made from the packages' Tailwind sources.

const root = fileURLToPath(new URL('../../../../', import.meta.url));
const releaseVersion = JSON.parse(fs.readFileSync(path.join(root, 'packages/acun-ui/package.json'), 'utf8')).version;
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

for (const build of BUILDS) {
    test(`${build.file} is up to date with its Tailwind sources (npm run build:css)`, () => {
        const css = read(build.file);
        const [, builtFrom] = css.match(BANNER_PATTERN) ?? [];

        assert.equal(builtFrom, sourceHash(build), `${build.file} is older than its sources: run npm run build:css`);
        assert.match(css, new RegExp(`^/\\*! ${build.name.replace(/[()]/g, '\\$&')} ${releaseVersion.replace(/\./g, '\\.')} `));
    });

    test(`${build.file} needs nothing else: no @import, @apply, @theme or @source left`, () => {
        const css = read(build.file).replace(/\/\*[\s\S]*?\*\//g, '');

        assert.doesNotMatch(css, /@(import|apply|theme|source|custom-variant|utility|plugin)\b/);
        // The tokens, both built-in themes and the components' utilities are in it.
        assert.match(css, /--ac-surface:/);
        assert.match(css, /\[data-theme\$=dark\]|\[data-theme\$="dark"\]|\[data-theme\$='dark'\]/);
        assert.match(css, /\.bg-primary\{/);
        assert.match(css, /\.ui-table/);
    });
}

test('acun-ui.css is acun-ui-core.css plus the global base, and only the full file has it', () => {
    const full = read('packages/acun-ui/dist/acun-ui.css');
    const core = read('packages/acun-ui/dist/acun-ui-core.css');
    const baseRule = /letter-spacing:-\.011em/;

    assert.match(full, baseRule);
    assert.doesNotMatch(core, baseRule);
});

test('the npm packages export the compiled stylesheets', () => {
    const free = JSON.parse(read('packages/acun-ui/package.json'));
    const paid = JSON.parse(read('packages/acun-pro/package.json'));

    assert.equal(free.exports['./acun-ui.css'], './dist/acun-ui.css');
    assert.equal(free.exports['./acun-ui-core.css'], './dist/acun-ui-core.css');
    assert.equal(paid.exports['./acun-pro.css'], './dist/acun-pro.css');
});

test('examples/html/index.html needs no Tailwind: every class it uses is in dist/acun-ui.css', async () => {
    const { compile } = await import('@tailwindcss/node');
    const { Scanner } = await import('@tailwindcss/oxide');
    // Class names in the selectors (a name starts with a letter, _, - or an escape; .45 is a number).
    const selectors = (css) => new Set([...css.matchAll(/\.((?:\\.|[A-Za-z_-])(?:\\.|[\w-])*)/g)].map((match) => match[1].replace(/\\/g, '')));

    const example = read('examples/html/index.html');
    const compiler = await compile(`@import 'tailwindcss';\n@import '${path.join(root, 'packages/acun-ui/css/panel.css')}';`, { base: root, onDependency() {} });
    const used = selectors(compiler.build(new Scanner({}).scanFiles([{ content: example, extension: 'html' }])));
    const compiled = selectors(read('packages/acun-ui/dist/acun-ui.css'));

    assert.match(example, /<link rel="stylesheet" href="[^"]*dist\/acun-ui\.css">/);
    assert.doesNotMatch(example, /cdn\.tailwindcss|<script[^>]+tailwind/i);
    assert.deepEqual([...used].filter((name) => !compiled.has(name)), []);
});
