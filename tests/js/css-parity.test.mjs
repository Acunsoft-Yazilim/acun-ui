import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import { chrome, componentSources, inChrome, stylesheet } from './chrome.mjs';
import { renderSamples } from './css-samples.mjs';

// The compiled stylesheet (dist/acun-ui.css, for projects without Tailwind) and an application's
// Tailwind build give the components the same look: the same computed styles in the light and the
// dark theme, at rest and with keyboard focus, and the same hover rules. The files need not be the
// same; what the browser paints must be. Skipped where no Chrome is installed.

const root = fileURLToPath(new URL('../../../../', import.meta.url));
const skip = chrome ? false : 'Chrome is not installed (CHROME_PATH)';

// In the page: the computed style of every element (a fixed list of properties) at rest, the same for
// each focusable element with keyboard focus, and every :hover rule as the browser parsed it.
const PROPERTIES = ['display', 'position', 'box-sizing', 'width', 'height', 'margin-top', 'margin-left', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'border-top-width', 'border-top-style', 'border-top-color', 'border-left-color', 'border-radius', 'background-color', 'background-image', 'color', 'opacity',
    'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'text-transform', 'text-decoration-line', 'gap', 'align-items', 'justify-content',
    'box-shadow', 'outline-style', 'outline-width', 'outline-color', 'outline-offset', 'cursor', 'fill', 'stroke', 'overflow-x', 'white-space', 'transition-property', 'transform'];
const measure = `
const props = ${JSON.stringify(PROPERTIES)};
const elements = [...document.querySelectorAll('section[data-sample] *')];
// Colours as the pixel they paint (the minifier may write a color-mix() it can resolve as rgb()).
const colour = (value) => value.replace(/\\btransparent\\b|(?:rgba?|color|oklab|oklch|lab|lch)\\((?:[^()]|\\([^()]*\\))*\\)/g, (c) => rgba(c).map((v, i) => (i === 3 ? Math.round(v * 100) : v)).join(','));
const read = (el) => { const s = getComputedStyle(el); return props.map((p) => colour(s.getPropertyValue(p))).join('|'); };
const rest = elements.map(read);
const focus = elements.filter((el) => el.matches('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]')).map((el) => { el.focus({ focusVisible: true }); const value = read(el); el.blur(); return value; });
const hover = {};
const walk = (rules) => { for (const rule of rules) { if (rule.cssRules && !rule.selectorText) walk(rule.cssRules); else if (rule.selectorText && rule.selectorText.includes(':hover')) for (const selector of rule.selectorText.split(/,(?![^(]*\\))/)) { const key = selector.trim(); hover[key] = [...(hover[key] ?? []), ...[...rule.style].map((p) => p + ':' + colour(rule.style.getPropertyValue(p)))].sort(); } } };
for (const sheet of document.styleSheets) walk(sheet.cssRules);
return { rest, focus, hover };
`;

test('dist/acun-ui.css paints the components as an application\'s Tailwind build does, in both themes', { skip }, async () => {
    const body = renderSamples();
    const tailwind = await stylesheet(body, componentSources('acun-ui'));
    const compiled = fs.readFileSync(`${root}packages/acun-ui/dist/acun-ui.css`, 'utf8');

    for (const theme of ['light', 'dark']) {
        const fromTailwind = inChrome({ css: tailwind, body, measure, htmlAttributes: `data-theme="${theme}"` });
        const fromDist = inChrome({ css: compiled, body, measure, htmlAttributes: `data-theme="${theme}"` });

        const differs = fromTailwind.rest.map((value, i) => (value === fromDist.rest[i] ? null : i)).filter((i) => i !== null);
        assert.deepEqual(differs, [], `${theme}: elements with another computed style at rest`);
        assert.deepEqual(fromDist.focus, fromTailwind.focus, `${theme}: another computed style with keyboard focus`);

        // Every hover rule the application's build has for this markup, the compiled file has too.
        const missing = Object.entries(fromTailwind.hover).filter(([selector, declarations]) => JSON.stringify(fromDist.hover[selector]) !== JSON.stringify(declarations)).map(([selector]) => selector);
        assert.deepEqual(missing, [], `${theme}: hover rules missing or different in dist/acun-ui.css`);
    }
});
