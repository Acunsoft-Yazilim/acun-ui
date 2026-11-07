import type { PluginDefinition } from '../core/plugins.js';
export type CarouselInstance = import('./carousel-engine.js').CarouselInstance;
/** @import { PluginDefinition } from '../core/plugins.js' */
/** @typedef {import('./carousel-engine.js').CarouselInstance} CarouselInstance */
/** @type {PluginDefinition} */
export declare const carouselPlugin: PluginDefinition;
/**
 * acun:carousel.controls (data-acun-carousel-controls="the carousel's id"): the carousel runs its arrows (see
 * carousel-engine.js); a group that enters the page after the carousel (a Livewire update) asks it for a render.
 *
 * @type {PluginDefinition}
 */
export declare const carouselControlsPlugin: PluginDefinition;
