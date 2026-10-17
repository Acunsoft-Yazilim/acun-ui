import test from 'node:test';
import assert from 'node:assert/strict';
import { stripWoffFaces, woff2Only } from '../vite/index.js';

// woff2Only (@acunsoft/acun-ui/vite): only the WOFF2 files of laravel-vite-plugin's font build.

const face = (file, format, range) => `@font-face {\n  font-family: "Public Sans";\n  font-weight: 400;\n  src: url("/build/assets/${file}") format("${format}");\n  unicode-range: ${range};\n}\n\n`;
const css = face('ps-400-latin.woff2', 'woff2', 'U+0000-00FF') + face('ps-400-latin.woff', 'woff', 'U+0000-00FF') + ':root {\n  --font-public-sans: "Public Sans";\n}\n';

function build() {
    const asset = (fileName, source) => ({ type: 'asset', fileName, source });

    return {
        'assets/ps-400-latin.woff2': asset('assets/ps-400-latin.woff2', 'w2'),
        'assets/ps-400-latin.woff': asset('assets/ps-400-latin.woff', 'w1'),
        'assets/fonts-abc.css': asset('assets/fonts-abc.css', css),
        'assets/app-abc.css': asset('assets/app-abc.css', 'body { color: red }'),
        'fonts-manifest.json': asset('fonts-manifest.json', JSON.stringify({
            style: { file: 'assets/fonts-abc.css', familyStyles: { 'public-sans': css } },
            preloads: [{ alias: 'public-sans', file: 'assets/ps-400-latin.woff2' }],
            families: { 'public-sans': { variants: { '400-normal': { files: [{ file: 'assets/ps-400-latin.woff2', format: 'woff2' }, { file: 'assets/ps-400-latin.woff', format: 'woff' }] } } } },
        })),
        'assets/app.js': { type: 'chunk', fileName: 'assets/app.js', code: 'console.log(1)' },
    };
}

test('the WOFF @font-face rules go, the WOFF2 ones and the rest stay', () => {
    const result = stripWoffFaces(css);

    assert.match(result, /ps-400-latin\.woff2/);
    assert.doesNotMatch(result, /\.woff"\)/);
    assert.match(result, /--font-public-sans/);
});

test('the plugin drops the WOFF files and their rules and manifest entries after the font build', () => {
    const plugin = woff2Only();
    const bundle = build();

    assert.equal(plugin.enforce, 'post');
    assert.equal(plugin.apply, 'build');
    plugin.generateBundle({}, bundle);

    assert.deepEqual(Object.keys(bundle).filter((name) => name.endsWith('.woff')), []);
    assert.ok(bundle['assets/ps-400-latin.woff2']);
    assert.doesNotMatch(bundle['assets/fonts-abc.css'].source, /format\("woff"\)/);
    assert.equal(bundle['assets/app-abc.css'].source, 'body { color: red }');

    const manifest = JSON.parse(bundle['fonts-manifest.json'].source);
    assert.doesNotMatch(manifest.style.familyStyles['public-sans'], /format\("woff"\)/);
    assert.deepEqual(manifest.families['public-sans'].variants['400-normal'].files.map((file) => file.format), ['woff2']);
});

test('a build without WOFF files is left alone', () => {
    const bundle = { 'assets/app.css': { type: 'asset', fileName: 'assets/app.css', source: '@font-face { src: url(a.woff2) format("woff2") }' } };

    woff2Only().generateBundle({}, bundle);

    assert.equal(bundle['assets/app.css'].source, '@font-face { src: url(a.woff2) format("woff2") }');
});
