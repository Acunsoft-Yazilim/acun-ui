// The fading edges of acun:scroll-area shadows, in plain JavaScript (no Alpine):
//
//     <div data-acun-scroll-shadows data-direction="vertical" tabindex="0" class="overflow-y-auto …">…</div>
//
// While more content is hidden in a direction, that edge fades (a CSS mask, so it works on any background), over
// the first 24px of scrolling instead of switching on at once. Measured again when the area or its content changes
// size, content is added (a Livewire update) or a morph rewrites the style attribute.

/** @import { PluginDefinition } from '../core/plugins.js' */

/** @param {number} distance */
const ramp = (distance) => Math.round(Math.min(Math.max(distance, 0) / 24, 1) * 100) / 100;
/** @param {number} fade */
const edge = (fade) => `rgb(0 0 0 / ${1 - fade})`;

/**
 * @param {HTMLElement} root
 */
function createScrollShadows(root) {
    const direction = root.dataset.direction ?? 'vertical';
    const vertical = direction !== 'horizontal';
    const horizontal = direction !== 'vertical';
    let mask = 'none';

    const measure = () => {
        const x = root.scrollLeft;
        const top = ramp(root.scrollTop);
        const bottom = ramp(root.scrollHeight - root.clientHeight - root.scrollTop);
        const start = ramp(x);
        const end = ramp(root.scrollWidth - root.clientWidth - x);
        const layers = [];
        if (vertical && (top || bottom)) layers.push(`linear-gradient(to bottom, ${edge(top)}, #000 1.5rem, #000 calc(100% - 1.5rem), ${edge(bottom)})`);
        if (horizontal && (start || end)) layers.push(`linear-gradient(to right, ${edge(start)}, #000 1.5rem, #000 calc(100% - 1.5rem), ${edge(end)})`);
        mask = layers.length ? layers.join(', ') : 'none';
        apply();
    };
    const apply = () => {
        if (root.style.maskImage === mask) return;
        root.style.maskImage = mask;
        root.style.setProperty('mask-composite', 'intersect');
    };

    const resizes = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(measure);
    const watch = () => {
        resizes?.observe(root);
        [...root.children].forEach((child) => resizes?.observe(child));
    };
    const mutations = typeof MutationObserver === 'undefined' ? null : new MutationObserver((records) => {
        if (records.some((record) => record.type === 'childList')) {
            watch();
            measure();
        } else {
            apply();
        }
    });
    watch();
    mutations?.observe(root, { childList: true, attributes: true, attributeFilter: ['style'] });
    root.addEventListener('scroll', measure, { passive: true });
    measure();

    return {
        element: root,
        destroy() {
            resizes?.disconnect();
            mutations?.disconnect();
            root.removeEventListener('scroll', measure);
        },
    };
}

/** @type {PluginDefinition} */
export const scrollShadowsPlugin = {
    attribute: 'data-acun-scroll-shadows',
    mount: createScrollShadows,
};
