// Colours as the browser paints them: the components' real markup (render.php), the real
// stylesheet (Tailwind with panel.css) and headless Chrome, which resolves the cascade,
// color-mix(), the dark theme and the placeholder. The contrast tests use it; they are skipped
// where no Chrome is installed (CHROME_PATH names one in another place).
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { compile } from '@tailwindcss/node';
import { Scanner } from '@tailwindcss/oxide';

const root = fileURLToPath(new URL('../../../../', import.meta.url));

export const chrome = [
    process.env.CHROME_PATH,
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/Applications/Chromium.app/Contents/MacOS/Chromium',
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
].find((file) => file && fs.existsSync(file));

/** Tailwind's output for the markup, with panel.css and the app CSS (e.g. a theme's own @theme) after it. */
export async function stylesheet(markup, appCss = '') {
    const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-contrast-'));
    try {
        fs.writeFileSync(path.join(directory, 'markup.html'), markup);
        const input = `@import 'tailwindcss';\n@plugin '@tailwindcss/forms';\n@import '${path.join(root, 'packages/acun-ui/css/panel.css')}';\n@source '${path.join(directory, 'markup.html')}';\n${appCss}\n`;
        const compiler = await compile(input, { base: root, onDependency: () => {} });

        return compiler.build(new Scanner({ sources: compiler.sources }).scan());
    } finally {
        fs.rmSync(directory, { recursive: true, force: true });
    }
}

// In the page: the colour of any CSS value as [r, g, b, alpha] (a canvas pixel), the colour behind
// an element (its own and its ancestors' backgrounds, composited) and WCAG contrast.
const helpers = `
const canvas = document.createElement('canvas'); canvas.width = canvas.height = 1;
const context = canvas.getContext('2d', { willReadFrequently: true });
function rgba(color) { context.clearRect(0, 0, 1, 1); context.fillStyle = '#000'; context.fillStyle = color; context.fillRect(0, 0, 1, 1); const d = context.getImageData(0, 0, 1, 1).data; return [d[0], d[1], d[2], d[3] / 255]; }
const over = (top, bottom) => top.slice(0, 3).map((channel, i) => channel * top[3] + bottom[i] * (1 - top[3]));
function behind(element) { const layers = []; for (let node = element; node; node = node.parentElement) layers.unshift(rgba(getComputedStyle(node).backgroundColor)); return layers.reduce((colour, layer) => over(layer, colour), [255, 255, 255]); }
const luminance = (rgb) => rgb.map((v) => v / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4)).reduce((sum, v, i) => sum + v * [0.2126, 0.7152, 0.0722][i], 0);
function contrast(a, b) { const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x); return Math.round(((light + 0.05) / (dark + 0.05)) * 100) / 100; }
function textContrast(element, pseudo) { const background = behind(element); return contrast(over(rgba(getComputedStyle(element, pseudo).color), background), background); }
const hex = (color) => '#' + rgba(color).slice(0, 3).map((v) => v.toString(16).padStart(2, '0')).join('');
`;

/**
 * Opens a page in headless Chrome (transitions off: measure may switch the theme and read at once) and returns what measure (the body of a function run in the page,
 * with the helpers above) returns, as JSON. htmlAttributes go on <html> (data-theme, the user's --ac-user-* colour…).
 */
export function inChrome({ css, body, measure, htmlAttributes = '' }) {
    const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-chrome-'));
    try {
        const page = path.join(directory, 'page.html');
        fs.writeFileSync(path.join(directory, 'panel.css'), css);
        fs.writeFileSync(page, `<!doctype html><html ${htmlAttributes}><head><meta charset="utf-8"><link rel="stylesheet" href="panel.css"><style>*, ::before, ::after { transition: none !important; }</style></head><body class="bg-page">${body}<pre id="acun-result"></pre><script>${helpers}\ndocument.getElementById('acun-result').textContent = JSON.stringify((() => { ${measure} })());</script></body></html>`);
        const dom = execFileSync(chrome, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--user-data-dir=${path.join(directory, 'profile')}`, '--dump-dom', `file://${page}`], { stdio: ['ignore', 'pipe', 'ignore'], timeout: 60000 }).toString();
        const result = dom.match(/<pre id="acun-result">([\s\S]*?)<\/pre>/)[1];

        return JSON.parse(result.replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&'));
    } finally {
        fs.rmSync(directory, { recursive: true, force: true });
    }
}
