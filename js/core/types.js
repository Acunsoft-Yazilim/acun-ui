// Types shared by the runtime's modules (JSDoc, checked by TypeScript: npm run typecheck). Nothing
// runs here: the module only carries the type definitions, which the other modules import with
// `@import`. The hosts the runtime talks to (Alpine, Livewire) are described as far as it uses them.

/**
 * The Alpine instance of the page (Livewire's, or one loaded on its own), as far as the runtime uses it.
 *
 * @typedef {object} AlpineLike
 * @property {(name: string, callback: (...args: any[]) => object) => void} data
 * @property {(name: string, value?: unknown) => any} store
 * @property {(element: Element, bindings: Record<string, unknown>) => void} bind
 * @property {(callback: (element: HostElement, skip?: () => void) => void) => void} interceptInit
 * @property {(selector: () => string) => void} addRootSelector
 * @property {<T extends (...args: any[]) => any>(callback: T) => T} skipDuringClone
 * @property {(element: Element, callback: () => void) => void} onElRemoved
 * @property {<T extends object>(value: T) => T} reactive
 * @property {(getter: () => unknown, callback: (value: any) => void) => () => void} watch
 * @property {(element: Element) => void} initTree
 * @property {(element: Element) => void} destroyTree
 * @property {(element: Element) => any} $data
 */

/**
 * The $wire of a Livewire component: any method name calls that method on the server.
 *
 * @typedef {Record<string, any>} WireLike
 */

/**
 * The Livewire global, as far as the runtime uses it.
 *
 * @typedef {object} LivewireLike
 * @property {(name: string, callback: (...args: any[]) => void) => void} hook
 * @property {(id: string) => WireLike | null | undefined} [find]
 */

/**
 * window with the globals the runtime reads and sets. AcunUI is the runtime once it ran; before
 * that a page may set { observe: false } (core/plugins.js).
 *
 * @typedef {Window & { AcunUI?: Record<string, any>, Alpine?: AlpineLike, Livewire?: LivewireLike }} AcunWindow
 */

/**
 * An element with the state Alpine and Livewire keep on it.
 *
 * @typedef {HTMLElement & { _x_ignore?: boolean, __livewire?: { $wire?: WireLike } }} HostElement
 */

export {};
