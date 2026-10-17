// The header part (acun:modal.header, acun:drawer.header) names its dialog: its title is
// the dialog's aria-labelledby and its description the aria-describedby. The server links
// them when it sees the header in the dialog's slot. A header drawn by a nested Livewire
// component is not there on the page's own updates (Livewire leaves an empty stub for the
// component, and the browser keeps its DOM), so those updates print the fallback name
// (aria-label) again; this puts the header's name back.

/**
 * Keeps dialog named by the header part of owner (the element with x-data, marked
 * data-acun-{component}) as Livewire's morphs change them. Returns the function that stops.
 */
export function keepHeaderName(dialog, owner, component) {
    if (!dialog) return () => {};

    const marker = `data-acun-${component}`;
    const link = () => {
        const header = [...dialog.querySelectorAll(`[data-slot="${component}-header"]`)]
            .find((element) => element.parentElement?.closest(`[${marker}]`) === owner);
        const title = header?.querySelector(':scope > div > h3[id]');
        if (!title) return;

        if (!dialog.hasAttribute('aria-labelledby')) {
            dialog.setAttribute('aria-labelledby', title.id);
            dialog.removeAttribute('aria-label');
        }

        const description = header.querySelector(':scope > div > p[id]');
        if (description && !dialog.hasAttribute('aria-describedby')) dialog.setAttribute('aria-describedby', description.id);
    };

    link();
    // The dialog's own attributes, a header that arrives (a lazy component) and the ids the
    // browser gives a header drawn without a name.
    const observer = new MutationObserver(link);
    observer.observe(dialog, { attributes: true, attributeFilter: ['aria-label', 'aria-labelledby', 'aria-describedby', 'id'], childList: true, subtree: true });

    return () => observer.disconnect();
}
