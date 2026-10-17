// List pages (e.g. one made by php artisan acun:table) put data-keep-in-view on the
// Livewire component's root. When an update makes such a list much shorter (a filter
// with few or no results) while its top is above the screen, the page would shrink
// under the reader and the browser would clamp the scroll position to wherever the
// new end lands. Instead the list's top is brought back into view, so the reader sees
// the new result.

import { hook } from '../core/livewire.js';

export function keepListsInView() {
    const tops = new WeakMap();

    hook('commit', ({ component }) => {
        const root = component?.el;

        if (root?.hasAttribute?.('data-keep-in-view')) tops.set(root, root.getBoundingClientRect().top);
    });

    hook('morphed', ({ el: root }) => {
        if (!root || !tops.has(root)) return;

        const top = tops.get(root);
        tops.delete(root);
        const rect = root.getBoundingClientRect();

        if (top < 0 && rect.top < 0 && rect.bottom < window.innerHeight * 0.6) {
            root.scrollIntoView({ block: 'start' });
        }
    });
}
