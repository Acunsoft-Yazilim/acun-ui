// The kit's Tailwind classes as an application builds them: its Blade sources scanned (the
// @source lines of the application's CSS) and compiled with the workspace's Tailwind. The
// behaviour tests put the result on the page where jsdom can evaluate the selectors.
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import { compile } from '@tailwindcss/node';
import { Scanner } from '@tailwindcss/oxide';

const root = fileURLToPath(new URL('../../../../', import.meta.url));

/**
 * The CSS of the classes in the given Blade files (paths from the workspace root): the
 * utilities only by default (no theme variables or preflight, which jsdom has no use for).
 */
export async function kitCss(files, css = '@import "tailwindcss/utilities";') {
    const candidates = new Scanner({}).scanFiles(files.map((file) => ({ content: fs.readFileSync(`${root}${file}`, 'utf8'), extension: 'html' })));
    const compiler = await compile(css, { base: root, onDependency() {} });

    return compiler.build(candidates);
}
