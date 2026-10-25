import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';

// The Acun DataTable theme bridge (css/acun-datatable.css, imported by css/core.css): the package's
// stylesheet (@acunsoft/datatable-dom/styles.css) reads --adt-* custom properties and turns itself dark
// under data-theme="dark", .dark or the system setting. The bridge maps those properties to the kit's
// tokens, so the theme decides, never the package's own switch. It is in the free kit: an application
// with Acun UI alone gets it.
const read = (file) => fs.readFileSync(fileURLToPath(new URL(`../css/${file}`, import.meta.url)), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
const css = read('acun-datatable.css');
const roots = '.adt, .adt-table-wrap, .adt-paging, .adt-info, .adt-length, .adt-search, .adt-selection, .adt-columns, .adt-toolbar, .adt-import';
const block = css.match(/^(:is\(\.adt,[^{]+)\{([^}]*)\}/m);

test('core.css imports the bridge outside any cascade layer', () => {
    const panel = read('core.css');
    const line = panel.match(/^@import '\.\/acun-datatable\.css'([^;]*);/m);
    assert.ok(line, 'core.css imports acun-datatable.css.');
    // No layer(): an unlayered package stylesheet would beat a layered bridge whatever its specificity.
    assert.equal(line[1].trim(), '');
    // Among the imports at the top, before the first rule.
    assert.ok(line.index < panel.search(/^[^@\s]/m));
});

test('the bridge sits on the outermost Acun DataTable element, unlayered, with one class of specificity', () => {
    assert.ok(block, 'The Acun DataTable block is in acun-datatable.css.');
    // The same roots as the package declares its defaults on; :where() in the :not() adds nothing,
    // so the package's zero-specificity light, dark and auto rules all lose to it.
    assert.equal(block[1].trim(), `:is(${roots}):not(:where(${roots}) *)`);
    // At the top level (not inside @layer or @media): an unlayered package stylesheet beats any layer.
    const depth = [...css.slice(0, block.index)].reduce((open, char) => open + (char === '{') - (char === '}'), 0);
    assert.equal(depth, 0);
});

test('every colour the package switches for its dark mode reads a kit token', () => {
    const properties = Object.fromEntries([...block[2].matchAll(/(--adt-[\w-]+):\s*([^;]+);/g)].map(([, name, value]) => [name, value.trim()]));
    // What the package's dark rules set (styles.css), plus the accent text, focus ring, corners and font.
    const switched = ['--adt-bg', '--adt-surface', '--adt-fg', '--adt-muted', '--adt-line', '--adt-head-fg', '--adt-hover', '--adt-selected', '--adt-accent', '--adt-danger', '--adt-shadow'];
    for (const name of [...switched, '--adt-head-bg', '--adt-accent-fg', '--adt-focus', '--adt-radius', '--adt-control-radius', '--adt-font']) {
        assert.ok(properties[name], `${name} is set`);
        assert.match(properties[name], /var\(--ac-[\w-]+\)/, `${name} reads a token`);
        assert.doesNotMatch(properties[name], /#[0-9a-f]{3,8}\b|rgb\(/i, `${name} has no colour of its own`);
    }
    // The package's color-scheme: light on the root would keep native controls light in the dark theme.
    assert.match(block[2], /color-scheme:\s*inherit;/);
    // Sizes stay the package's (adt-size--sm / --lg set them with zero specificity, which this block would beat).
    assert.deepEqual(Object.keys(properties).filter((name) => /font-size|padding|height|gap/.test(name)), []);
});
