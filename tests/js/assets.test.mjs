import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { BANNER_PATTERN, BUILDS, sourceHash } from '../../../../scripts/build-js.mjs';

// packages/acun-ui/dist: the browser builds of packages/acun-ui/js (npm run build:js) that
// the package serves (acun-ui-boot.js for acun:layout.head, acun-ui.js for @acunUiScripts).

const dist = new URL('../../dist/', import.meta.url);
const releaseVersion = JSON.parse(fs.readFileSync(new URL('../../package.json', import.meta.url), 'utf8')).version;

for (const { file } of BUILDS) {
    test(`${file} is up to date with packages/acun-ui/js (npm run build:js)`, () => {
        const code = fs.readFileSync(new URL(file, dist), 'utf8');
        const [, builtFrom] = code.match(BANNER_PATTERN) ?? [];

        assert.equal(builtFrom, sourceHash(), `dist/${file} is older than packages/acun-ui/js: run npm run build:js`);
        assert.match(code, new RegExp(`^/\\*! Acun UI ${releaseVersion.replace(/\./g, '\\.')} `));
    });

    test(`${file} is a plain script with no imports and no global of its own`, () => {
        const code = fs.readFileSync(new URL(file, dist), 'utf8');

        assert.doesNotMatch(code, /^\s*import\s|\bimport\s*\(|\bexport\s/m);
        assert.doesNotThrow(() => new Function(code));
        assert.match(code.split('\n')[1], /^\(function\(\)\{/, 'Wrapped in a function: no top-level variables.');
    });
}

// 6.25 KB (was 5.75, 5.5, before that 5): since 2.0 it also gives the semi-dark menu and the navbar their saved choice as
// the parser adds them (no flash of the server's value) and reads the choices of the page's scope; since 2.1 its
// stand-ins (a page whose runtime did not load) also give the modal and drawer roots, which carry no x-data any
// more, a scope, so their content's close() does not reach window.close(). Since 2026-11-07 it puts the page
// container's width on <html> (the named widths, a CSS length, boxed as wide; 5.6 → 6.1 KB measured), so a width
// kept in the browser is on the first frame.
test('the boot script stays small: it blocks the first paint', () => {
    assert.ok(fs.statSync(new URL('acun-ui-boot.js', dist)).size < 6.25 * 1024);
});

// acun:carousel's Swiper is a file of its own beside the runtime (dist/acun-ui-carousel.js): only a page with a carousel
// loads it (plugins/carousel-script.js; carousel-chrome.test.mjs loads it in a browser), so acun-ui.js carries none of it.
test('acun-ui.js has no Swiper; acun-ui-carousel.js has it, with its version in the banner', () => {
    const runtime = fs.readFileSync(new URL('acun-ui.js', dist), 'utf8');
    const carousel = fs.readFileSync(new URL('acun-ui-carousel.js', dist), 'utf8');
    const swiper = JSON.parse(fs.readFileSync(new URL('../../package.json', import.meta.url), 'utf8')).dependencies.swiper;

    for (const marker of ['swiper-slide-active', 'swiper-zoom-target', 'slidesPerGroup']) {
        assert.equal(runtime.includes(marker), false, `dist/acun-ui.js has ${marker} in it`);
        assert.equal(carousel.includes(marker), true, `dist/acun-ui-carousel.js misses ${marker}`);
    }
    assert.match(carousel.split('\n')[0], new RegExp(`with Swiper ${swiper.replace(/\./g, '\\.')} \\(MIT`));
    assert.match(swiper, /^\d+\.\d+\.\d+$/, 'An exact version.');
});
