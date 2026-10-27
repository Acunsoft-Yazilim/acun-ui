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

// Locally the Chrome tests are skipped without Chrome; on CI (CI set) a missing Chrome is an error, so
// the browser checks cannot pass by not running.
if (!chrome && process.env.CI) {
    throw new Error('Chrome is not installed on CI: set CHROME_PATH or install Chrome; the Chrome tests must run there.');
}

/**
 * @source lines for the component views of the named packages (componentSources('acun-ui',
 * 'acun-pro')), for stylesheet()'s app CSS: then the stylesheet has every class the views write, as
 * an application's has. The rendered markup alone misses the arbitrary ones Blade escapes
 * ([&:focus-visible>div]:outline-2 becomes [&amp;:focus-visible&gt;div]:outline-2).
 */
export const componentSources = (...packages) => packages.map((name) => `@source '${path.join(root, 'packages', name, 'resources/views/components/ui')}';`).join('\n');

/** Tailwind's output for the markup, with panel.css and the app CSS (e.g. a theme's own @theme) after it. */
export async function stylesheet(markup, appCss = '') {
    const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'acun-contrast-'));
    try {
        fs.writeFileSync(path.join(directory, 'markup.html'), markup);
        const input = `@import 'tailwindcss';\n@import '${path.join(root, 'packages/acun-ui/css/panel.css')}';\n@source '${path.join(directory, 'markup.html')}';\n${appCss}\n`;
        const compiler = await compile(input, { base: root, onDependency: () => {} });

        return compiler.build(new Scanner({ sources: compiler.sources }).scan());
    } finally {
        fs.rmSync(directory, { recursive: true, force: true });
    }
}

// In the page: the colour of any CSS value as [r, g, b, alpha] (a canvas pixel), the colour behind
// an element (its own and its ancestors' backgrounds, composited), WCAG contrast and the focus
// indicator an element draws (focusRings).
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
// The rings of a box-shadow value: colour, inset, blur and spread.
function shadowRings(value) {
    if (value === 'none') return [];
    const colour = /(?:rgba?|oklab|oklch|lab|lch|color)\\([^)]*\\)|#[0-9a-f]+/i;
    return value.split(/,(?![^(]*\\))/).map((layer) => {
        const numbers = layer.replace(colour, '').replace('inset', '').trim().split(/\\s+/).map(parseFloat);
        return { colour: layer.match(colour)?.[0] ?? 'transparent', inset: /\\binset\\b/.test(layer), blur: numbers[2] || 0, spread: numbers[3] || 0 };
    });
}
// The keyboard focus indicator of target, drawn by drawer (target itself, or the sibling or parent a
// peer-focus-visible: or focus-within: class styles): its outline when one is painted, each solid
// ring (box-shadow) the focus adds and a frame (border) it recolours, as { kind, colour, ratio }.
// The ratio is against what the indicator sits on: the parent's background for one outside the
// element, the element's own for an inset ring, a frame or an outline drawn inside (a negative offset).
function focusRings(target, drawer = target) {
    const before = shadowRings(getComputedStyle(drawer).boxShadow).map((ring) => JSON.stringify(ring));
    const frame = getComputedStyle(drawer).borderTopColor;
    target.focus({ focusVisible: true });
    const style = getComputedStyle(drawer);
    const rings = [];
    if (style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0 && rgba(style.outlineColor)[3] > 0) {
        const inside = parseFloat(style.outlineOffset) + parseFloat(style.outlineWidth) <= 0;
        rings.push({ kind: 'outline', colour: style.outlineColor, ground: behind(inside ? drawer : drawer.parentElement) });
    }
    for (const ring of shadowRings(style.boxShadow)) {
        if (before.includes(JSON.stringify(ring)) || ring.blur > 0 || ring.spread <= 0 || rgba(ring.colour)[3] === 0) continue;
        rings.push({ kind: ring.inset ? 'inset ring' : 'ring', colour: ring.colour, ground: behind(ring.inset ? drawer : drawer.parentElement) });
    }
    if (parseFloat(style.borderTopWidth) > 0 && style.borderTopColor !== frame && rgba(style.borderTopColor)[3] > 0) {
        rings.push({ kind: 'frame', colour: style.borderTopColor, ground: behind(drawer) });
    }
    target.blur();
    return rings.map(({ kind, colour, ground }) => ({ kind, colour: hex(colour), ratio: contrast(over(rgba(colour), ground), ground) }));
}
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
        let dom;
        try {
            dom = execFileSync(chrome, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--allow-file-access-from-files', `--user-data-dir=${path.join(directory, 'profile')}`, '--dump-dom', `file://${page}`], { stdio: ['ignore', 'pipe', 'pipe'], timeout: 60000, maxBuffer: 64 * 1024 * 1024 }).toString();
        } catch (error) {
            // On a busy machine Chrome may print the page and then take too long to shut down: its own
            // teardown watchdog ends it with exit 2. The page was measured in full (the dump is complete),
            // so its result stands; any other failure (a crash, the 60 s limit) fails the test.
            const stderr = String(error.stderr ?? '');
            const output = String(error.stdout ?? '');
            if (error.status === 2 && stderr.includes('Teardown watchdog expired') && /<pre id="acun-result">[\s\S]*?<\/pre>[\s\S]*<\/html>/.test(output)) {
                dom = output;
                console.warn('chrome.mjs: Chrome\'s teardown watchdog ended it after the page was measured; the result is kept.');
            } else {
                throw new Error(`Chrome failed (exit ${error.status}, signal ${error.signal}${error.code ? `, ${error.code}` : ''}):\n${stderr.split('\n').filter(Boolean).slice(-6).join('\n')}`);
            }
        }
        const result = dom.match(/<pre id="acun-result">([\s\S]*?)<\/pre>/)[1];

        return JSON.parse(result.replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&'));
    } finally {
        fs.rmSync(directory, { recursive: true, force: true });
    }
}
