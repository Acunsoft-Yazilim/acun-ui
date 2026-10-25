import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { compile } from '@tailwindcss/node';
import { Scanner } from '@tailwindcss/oxide';
import { listFiles } from '../../../../scripts/build-css.mjs';

// An application's Tailwind scans the kit's Blade views (the @source lines of acun:install) and the
// compiled stylesheets do the same (npm run build:css). A utility the kit only writes from PHP or
// JavaScript would be missing from both. This compiles the kit's CSS once with the views and once with
// the views, the PHP support classes and the JavaScript, and checks that the second adds nothing but
// words that are never written as classes (names in parsers, variables, comments, examples).

const root = fileURLToPath(new URL('../../../../', import.meta.url));

// Not classes the kit writes: Support\Field parses an application's classes with these names; theme.js
// names CSS properties (--ac-user-fill-hover…); the others are words in comments, messages and examples.
const NOT_WRITTEN = [
    '[acun:combobox]', 'bg-brand-600', 'border-(--c)', 'container', 'fill-dark', 'fill-hover', 'flow-root',
    'inline-grid', 'lowercase', 'order-4821', 'resize', 'rounded', 'rounded-b', 'rounded-bl', 'rounded-br',
    'rounded-e', 'rounded-ee', 'rounded-es', 'rounded-l', 'rounded-r', 'rounded-s', 'rounded-se', 'rounded-ss',
    'rounded-t', 'rounded-tl', 'rounded-tr', 'shrink', 'text-wrap',
];

const selectors = (css) => new Set([...css.matchAll(/\.((?:\\.|[\w-])+)/g)].map((match) => match[1].replace(/\\/g, '')));

async function classes(entries, files) {
    const css = ["@import 'tailwindcss';", ...entries.map((entry) => `@import '${path.join(root, entry)}';`)].join('\n');
    const candidates = new Scanner({}).scanFiles(files.map((file) => ({ content: fs.readFileSync(path.join(root, file), 'utf8'), extension: file.endsWith('.js') ? 'js' : 'php' })));

    return selectors((await compile(css, { base: root, onDependency() {} })).build(candidates));
}

test('every utility the kit uses is in its Blade views: PHP and JavaScript add none', async () => {
    const entries = ['packages/acun-ui/css/panel.css', 'packages/acun-pro/resources/css/panel.css'];
    const views = listFiles(['packages/acun-ui/resources/views', 'packages/acun-pro/resources/views'], ['.blade.php']);
    const code = [
        ...listFiles(['packages/acun-ui/src', 'packages/acun-pro/src'], ['.php']),
        ...listFiles(['packages/acun-ui/js', 'packages/acun-pro/resources/js'], ['.js']),
    ];

    const fromViews = await classes(entries, views);
    const fromAll = await classes(entries, [...views, ...code]);
    const added = [...fromAll].filter((name) => !fromViews.has(name) && !NOT_WRITTEN.includes(name)).sort();

    assert.deepEqual(added, [], 'A class only PHP or JavaScript writes: put it in a view or an @source inline() line of css/core.css.');
});
