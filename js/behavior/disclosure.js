// A region that a button opens and closes (an accordion section, acun:collapse), shared by plugins/accordion.js
// and plugins/collapse.js. The height animates in CSS, with no measuring: the region is a one-row grid whose
// row goes from 0fr to 1fr under data-open (grid-rows-[0fr] data-open:grid-rows-[1fr] transition-[grid-template-rows];
// its child has min-h-0 overflow-hidden). Closed, it is hidden once the transition has ended (core/transition.js).
//
// Lazy content waits in a <template data-acun-lazy> until the region first opens. On a page with Alpine the
// template is Alpine's (x-if on the component's scope, which Livewire's morph knows how to keep); without
// Alpine the region puts the template's content in its place itself.

import { enter, leave } from '../core/transition.js';

/** @param {HTMLElement} region */
function renderLazy(region) {
    if (/** @type {Window & { Alpine?: unknown }} */ (window).Alpine) return;

    region.querySelectorAll('template[data-acun-lazy]').forEach((template) => {
        template.replaceWith(/** @type {HTMLTemplateElement} */ (template).content.cloneNode(true));
    });
}

/**
 * Opens or closes region (and the trigger's aria-expanded); returns the function that cancels a closing still
 * running (the region opened again meanwhile).
 *
 * @param {HTMLElement} region
 * @param {HTMLElement | null} trigger
 * @param {boolean} open
 * @returns {() => void}
 */
export function setRegion(region, trigger, open) {
    trigger?.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
        renderLazy(region);
        enter([region], [region]);
        return () => {};
    }

    return leave([region], [region]);
}
