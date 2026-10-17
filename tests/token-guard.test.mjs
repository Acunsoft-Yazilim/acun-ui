import test from 'node:test';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import { scan, viewFiles, violations } from './js/token-guard.mjs';

// Plan 6.1, item 4: the kit's components paint with tokens only (see js/token-guard.mjs).
const views = fileURLToPath(new URL('../resources/views', import.meta.url));

const allowlist = {
    // The file kinds' colours (pdf red, spreadsheet green…) are data, the same in every theme.
    'components/ui/file-icon.blade.php': { allow: () => true },
};

test('no palette shade, hex or dark: colour class in the kit\'s component markup', () => {
    const files = viewFiles([views]);
    assert.ok(files.length > 100, `${files.length} views`);
    assert.deepEqual(scan(files, views, allowlist), {});
});

test('the guard finds what it is for', () => {
    const found = violations([
        '<div class="bg-white text-gray-500 dark:text-zinc-400 hover:bg-brand-600 ring-black/10">',
        "<p @class(['text-ink dark:text-ink' => $a, 'bg-[#fff] [scrollbar-color:var(--color-gray-300)_transparent]'])>",
        '<span class="rounded-sm bg-surface shadow-xl dark:bg-zinc-900{{ $x }}" style="color: #18181b" fill="#fff"></span>',
        '<span class="bg-(--btn-primary-bg) focus:shadow-(--ui-focus-shadow) dark:border-2 text-2xs ring-inset">',
        '<style>.x { color: #abc; }</style>',
    ].join('\n'));
    assert.deepEqual(found.map((line) => line.replace(/ .*?(\S+)$/, ' $1')), [
        '1: bg-white', '1: text-gray-500', '1: dark:text-zinc-400', '1: hover:bg-brand-600', '1: ring-black/10',
        '2: dark:text-ink', '2: bg-[#fff]', '2: [scrollbar-color:var(--color-gray-300)_transparent]',
        '3: dark:bg-zinc-900', '3: #18181b', '3: #fff',
        '4: bg-(--btn-primary-bg)', '4: focus:shadow-(--ui-focus-shadow)',
        '<style>: #abc',
    ]);
});
