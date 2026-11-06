import type { PluginDefinition } from '../core/plugins.js';
/** @import { PluginDefinition } from '../core/plugins.js' */
/** @param {string} value  #rrggbb in lower case, or null for anything else */
export declare function normalColor(value: string): string | null;
/** @type {PluginDefinition} */
export declare const colorInputPlugin: PluginDefinition;
