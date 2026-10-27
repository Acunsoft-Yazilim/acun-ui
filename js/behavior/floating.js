// Keeps a floating panel (a dropdown's menu, a popover) inside the window, with plain measurements and no
// library. The panel is placed by its CSS (absolute, beside its anchor) on the side its data-side names
// (bottom or top: data-[side=top]:bottom-full …); this flips data-side to the other side when the panel does
// not fit on its own side and the other has more room, and shifts it sideways (the translate property) when
// it would cross the left or right edge. Runs when the panel opens and, while it is open, when the window
// is resized or scrolled.

/** The space kept between the panel and the window's edge (px). */
const MARGIN = 8;

/**
 * Places panel once: its preferred side, the other one when that fits better, and a sideways shift.
 *
 * @param {HTMLElement} panel
 * @param {Element} anchor   what it opens from (the trigger)
 * @param {'bottom' | 'top'} preferred
 */
export function place(panel, anchor, preferred) {
    panel.style.translate = '';
    panel.dataset.side = preferred;

    const box = anchor.getBoundingClientRect();
    const own = panel.getBoundingClientRect();
    // No layout (a hidden ancestor, a test page without one): nothing to measure.
    if (!own.width && !own.height) return;

    const below = window.innerHeight - box.bottom - MARGIN;
    const above = box.top - MARGIN;
    const height = own.height;
    if (preferred === 'bottom' && height > below && above > below) panel.dataset.side = 'top';
    else if (preferred === 'top' && height > above && below > above) panel.dataset.side = 'bottom';

    const placed = panel.getBoundingClientRect();
    const right = window.innerWidth - MARGIN;
    const shift = placed.left < MARGIN ? MARGIN - placed.left : placed.right > right ? Math.max(right - placed.right, MARGIN - placed.left) : 0;
    if (shift) panel.style.translate = `${Math.round(shift)}px 0`;
}

/**
 * Places panel now and again on resize and scroll until the returned function is called (the panel keeps
 * its place while it fades out; the next opening places it again).
 *
 * @param {HTMLElement} panel
 * @param {Element} anchor
 * @param {'bottom' | 'top'} preferred
 * @returns {() => void}
 */
export function follow(panel, anchor, preferred) {
    let frame = 0;
    const update = () => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => place(panel, anchor, preferred));
    };

    place(panel, anchor, preferred);
    window.addEventListener('resize', update);
    // Capture: a scroll inside any scrolling box moves the anchor too.
    window.addEventListener('scroll', update, { capture: true, passive: true });

    return () => {
        cancelAnimationFrame(frame);
        window.removeEventListener('resize', update);
        window.removeEventListener('scroll', update, { capture: true });
    };
}
