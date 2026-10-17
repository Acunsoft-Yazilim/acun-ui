// The token guard (plan 6.1, item 4): component markup paints with the theme's tokens
// (css/tokens.css: bg-surface, text-muted, bg-primary text-on-primary…), never with a palette
// shade, a hex colour or a dark: colour class, so every colour follows the theme, the dark theme
// and a section's own data-theme. Used by the kit's and the paid package's tests.
//
// Found in a view (Blade comments included, so an example in a comment does not teach the old way):
//   - a palette shade class: text-gray-500, hover:bg-brand-600, ring-red-500/20, bg-emerald-solid
//   - white or black as a class colour: bg-white, text-white/70 (a token says which role it is)
//   - a dark: colour class: dark:bg-zinc-900, dark:text-ink, dark:ring-white/15
//   - a palette or kit variable in a class or style: [scrollbar-color:var(--color-gray-300)],
//     bg-(--btn-primary-bg), shadow-(--ui-focus-shadow)
//   - a hex colour in a class, a style attribute, a fill / stroke / stop-color attribute or a <style>
//
// The allowlist is for data colours, the same in every theme: acun:file-icon, the file kinds of
// acun:file-upload, the black scrims of acun:media-player's video (plan 6.1: also shapes/*, .pv and
// the chart series, which live outside these views).
//
// The stylesheets have the same rule (cssViolations): they read the tokens (var(--ac-line)), never a
// palette variable, a hex colour, a .dark rule or a 1.x kit variable.
import fs from 'node:fs';
import path from 'node:path';

const PALETTE = 'gray|zinc|neutral|slate|stone|brand|accent|emerald|sky|amber|red|secondary|orange|violet|fuchsia|rose|green|blue|yellow|purple|pink|indigo|teal|cyan|lime';
const PROPERTY = 'bg|text|border|border-[xytblrse]|ring|ring-offset|inset-ring|divide|outline|from|via|to|fill|stroke|placeholder|decoration|accent|caret|shadow';
const SHADE = new RegExp(`^-?(?:${PROPERTY})-(?:${PALETTE})-(?:\\d{2,3}|solid)(?:/[\\d.]+)?!?$`);
const WHITE_BLACK = new RegExp(`^-?(?:${PROPERTY})-(?:white|black)(?:/[\\d.]+)?!?$`);
/** A colour utility of any kind: a dark: variant on it is what the guard looks for. */
const COLOUR = new RegExp(`^-?(?:${PROPERTY})-(?!\\[|\\(|(?:xs|sm|md|lg|xl|\\d?xl|base|none|inner|inset|left|right|center|start|end|justify|wrap|nowrap|clip|ellipsis|solid|dashed|dotted|double|hidden|collapse|separate|auto|\\d+(?:\\.\\d+)?|px|x|y|t|b|l|r|s|e)$)[a-z]`);
const VARIABLE = /var\(--color-|\(--color-|var\(--btn-|\(--btn-|var\(--ui-|\(--ui-/;
const HEX = /#[0-9a-f]{3,8}\b/i;

/** Variants are split at the colons outside [...] and (...). */
const SPLIT = /:(?![^[]*\])(?![^(]*\))/;

/** Every view file under the folders (Blade and PHP). */
export function viewFiles(folders, skip = () => false) {
    const found = [];
    const walk = (folder) => {
        for (const name of fs.readdirSync(folder).sort()) {
            const file = path.join(folder, name);
            if (skip(file)) continue;
            if (fs.statSync(file).isDirectory()) walk(file);
            else if (file.endsWith('.php')) found.push(file);
        }
    };
    folders.forEach(walk);

    return found;
}

/** What the guard finds in one file's source: ["line: what", …]. allow(word) keeps a class. */
export function violations(source, allow = () => false) {
    const found = [];
    source.split('\n').forEach((line, i) => {
        const at = (what) => found.push(`${i + 1}: ${what}`);
        for (const word of line.split(/[\s'"`]+/)) {
            // A class glued to Blade ({{ … }}), a comma or a closing bracket is still a class.
            let cls = word.replace(/\{\{.*$/, '').replace(/^[{(,]+(?=[a-z!-])/, '');
            const unbalanced = () => (cls.match(/\)/g) ?? []).length > (cls.match(/\(/g) ?? []).length || (cls.match(/\]/g) ?? []).length > (cls.match(/\[/g) ?? []).length;
            while (/[,;)}\]]$/.test(cls) && (/[,;}]$/.test(cls) || unbalanced())) cls = cls.slice(0, -1);
            if (!cls) continue;
            const parts = cls.split(SPLIT);
            const utility = parts.pop().replace(/^!/, '');
            if (allow(cls)) continue;
            if (SHADE.test(utility)) at(`palette shade ${cls}`);
            else if (WHITE_BLACK.test(utility)) at(`white or black ${cls}`);
            else if (parts.includes('dark') && COLOUR.test(utility)) at(`dark: colour ${cls}`);
            if (/[[(]/.test(cls) && VARIABLE.test(cls)) at(`palette or kit variable ${cls}`);
            if (/[[(]/.test(cls) && HEX.test(cls)) at(`hex in a class ${cls}`);
        }
        for (const [, value] of line.matchAll(/\b(?:style|fill|stroke|stop-color|color)\s*=\s*"([^"]*)"/g)) {
            if (HEX.test(value)) at(`hex in an attribute: ${value}`);
            if (VARIABLE.test(value)) at(`palette or kit variable in an attribute: ${value}`);
        }
    });
    for (const [, css] of source.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)) {
        if (HEX.test(css)) found.push(`<style>: hex ${css.match(HEX)[0]}`);
    }

    return found;
}

/** The report for a set of files: { "relative/file": [...] } of the ones that break the rule. */
export function scan(files, base, allowlist = {}) {
    const report = {};
    for (const file of files) {
        const relative = path.relative(base, file);
        const rule = allowlist[relative] ?? {};
        const source = (rule.strip ?? []).reduce((text, pattern) => text.replace(pattern, (match) => match.replace(/[^\n]/g, '')), fs.readFileSync(file, 'utf8'));
        const found = violations(source, rule.allow);
        if (found.length) report[relative] = found;
    }

    return report;
}

/**
 * What the guard finds in a stylesheet: ["line: what", …]. Comments are skipped (an example in one
 * may name the old way). Found:
 *   - a palette or Tailwind colour variable in use: var(--color-gray-200), var(--color-brand-600)
 *   - a hex colour (hex: false allows them, for a theme's own token values)
 *   - a .dark selector: the dark theme is [data-theme$='dark'] (or @variant dark), which follows a
 *     section; class="dark" stays on <html> for application code only
 *   - a 1.x kit variable: --ui-*, --btn-*
 */
export function cssViolations(css, { hex = true } = {}) {
    const found = [];
    const source = css.replace(/\/\*[\s\S]*?\*\//g, (comment) => comment.replace(/[^\n]/g, ''));
    source.split('\n').forEach((line, i) => {
        const at = (what) => found.push(`${i + 1}: ${what}`);
        for (const [match] of line.matchAll(/var\(--color-[\w-]+/g)) at(`palette variable ${match})`);
        if (hex) for (const [match] of line.matchAll(/(?<=[\s(:,])#[0-9a-f]{3,8}\b/gi)) at(`hex ${match}`);
        if (/\.dark(?![\w-])/.test(line)) at(`.dark selector: ${line.trim()}`);
        for (const [match] of line.matchAll(/--(?:ui|btn)-[\w-]+/g)) at(`kit variable ${match}`);
    });

    return found;
}
