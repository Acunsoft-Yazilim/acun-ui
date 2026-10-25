import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { cssViolations, scan, unmarkedControls, viewFiles, violations } from './js/token-guard.mjs';

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

test('every form control of the kit\'s markup carries data-acun-control (css/forms.css draws it)', () => {
    const report = Object.fromEntries(viewFiles([views]).map((file) => [path.relative(views, file), unmarkedControls(fs.readFileSync(file, 'utf8'))]).filter(([, found]) => found.length));
    assert.deepEqual(report, {});
    assert.deepEqual(unmarkedControls([
        '<input type="text">', '<select', '    name="plan">', '{{-- <input> in a comment --}}', '<input data-acun-control type="radio">',
        '<input type="hidden" name="token">', '<textarea', '    data-acun-control', '></textarea>',
    ].join('\n')), ['1: <input type="text">', '2: <select']);
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

/**
 * The kit's stylesheets read the tokens too: no palette variable, hex colour, .dark rule or 1.x kit
 * variable (--ui-*, --btn-*). panel.css keeps the palette scales in its @theme block for application
 * code and data colours; themes.css holds the built-in themes' own values (hex allowed there);
 * forms.css, the form controls' base, and acun-datatable.css, the Acun DataTable bridge, read the
 * tokens too.
 */
test('no palette variable, hex colour, .dark rule or 1.x kit variable in the kit\'s CSS', () => {
    const css = (file) => fs.readFileSync(new URL(`../css/${file}`, import.meta.url), 'utf8');
    const panel = css('panel.css');
    assert.match(panel, /\n@theme \{\n/);
    const outsideTheme = panel.replace(/\n@theme \{[\s\S]*?\n\}/, (block) => block.replace(/[^\n]/g, ''));
    assert.deepEqual({
        'panel.css': cssViolations(outsideTheme),
        'tokens.css': cssViolations(css('tokens.css')),
        'themes.css': cssViolations(css('themes.css'), { hex: false }),
        'forms.css': cssViolations(css('forms.css')),
        'acun-datatable.css': cssViolations(css('acun-datatable.css')),
    }, { 'panel.css': [], 'tokens.css': [], 'themes.css': [], 'forms.css': [], 'acun-datatable.css': [] });
});

test('the CSS guard finds what it is for', () => {
    const found = cssViolations([
        '.a { border-color: var(--color-gray-200); color: #fff; }',
        '/* .dark { color: #000 } in a comment */ dialog#livewire-error::backdrop { background: rgb(0 0 0 / .6); }',
        '.dark .b, :root.dark .c { --ui-control-border: var(--color-zinc-500, #787878); }',
        "[data-theme$='dark'] .d { background: linear-gradient(black, transparent); --btn-primary-bg: red; }",
        'html.dark { color-scheme: dark; } .text-dark, .dark-mode { color: inherit; }',
    ].join('\n'));
    assert.deepEqual(found.map((line) => line.replace(/^(\d+: [^:]*?)(?:: .*)?$/, '$1')), [
        '1: palette variable var(--color-gray-200)', '1: hex #fff',
        '3: palette variable var(--color-zinc-500)', '3: hex #787878', '3: .dark selector', '3: kit variable --ui-control-border',
        '4: kit variable --btn-primary-bg', '5: .dark selector',
    ]);
});
