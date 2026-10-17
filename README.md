# Acun UI

The free edition of **Acun UI**, one package on Composer (`acunsoft/acun-ui`) and npm (`@acunsoft/acun-ui`):

- **Blade and Livewire components** for Laravel (`<acun:*>`, `src/`, `resources/views`), auto-discovered through `Acun\Ui\AcunUiServiceProvider`.
- **The CSS and JavaScript core**: a Tailwind CSS v4 theme layer (`css/panel.css`) and the Alpine.js runtime (`js/`). It is framework-agnostic; the Acun UI HTML pages use the same classes and scripts.

**Acun Pro** (`acunsoft/acun-pro`, Composer, paid) adds advanced components, guarded form modals, a Livewire list page generator, charts, maps and e-commerce (storefront pages and the Nova, Vivid and Atelier themes) on top of this package. Its JS and CSS ship inside that package (npm `@acunsoft/acun-pro` from `vendor/`), not on npm.

## Requirements

- Tailwind CSS 4 with `@tailwindcss/forms`
- Alpine.js 3 (bundled with Livewire 4 in Laravel apps)

## Install

In a Laravel app the package comes with Composer:

```bash
composer require acunsoft/acun-ui
```

Point npm at the copy in `vendor/`, so the CSS and JavaScript always match the installed Blade components:

```json
"dependencies": {
    "@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui"
}
```

Without Composer, install the npm archive from the release (the CSS, `js/` and `vite/` only):

```bash
npm install ./acunsoft-acun-ui-<version>.tgz
```

### CSS

Import it after Tailwind and tell Tailwind where your markup lives:

```css
@import 'tailwindcss';
@plugin '@tailwindcss/forms';
@import '@acunsoft/acun-ui';

/* Laravel: the Blade components of this package */
@source '../../vendor/acunsoft/acun-ui/resources/views';

/* Optional: brand colour and fonts */
@theme {
    --color-brand-600: #7c3aed;
}
```

The theme defines the `brand`, `accent`, `gray`, `zinc`, `emerald`, `sky`, `amber` and `red` scales, shadows, radii, the type scale and dark mode (`.dark` on `<html>`). Every `brand-*` tone derives from `--color-brand-600`, so one variable recolours the whole panel.

### JavaScript

```js
import '@acunsoft/acun-ui';
```

This is the Acun UI runtime; panel pages need it. It sets up one global, `window.AcunUI`, and installs once per page:

- the dialog behaviour of drawers, modals and the command palette: a counted page scroll lock, Escape and Tab for the newest dialog only, focus restore;
- the Alpine components `acunDrawer` and `acunModal`, and on pages with `acun:layout.head` the theme and menu stores;
- the Livewire fixes: `<select>` values after morphs, no morphs into detached components, lists kept in view, a full page load when leaving a page that forces its layout.

Without Vite, `@acunUiScripts` loads a browser build of the same code (`dist/`). The theme itself is applied before the first paint by a small blocking script that `acun:layout.head` loads (`js/boot.js`).

The API: `AcunUI.modal.open(name)` / `close(name?)`, `AcunUI.drawer.open(name)` / `close(name?)` / `toggle(name)`, `AcunUI.scrollLock.lock()` / `unlock()`, and `AcunUI.overlays.open(element, { close, trap })` / `close(element)` for a dialog of your own (scroll lock, Escape, Tab trap and focus restore together). The same commands as events on `window`, with `{ name }`: `acun:modal:open`, `acun:modal:close`, `acun:drawer:open`, `acun:drawer:close`, `acun:drawer:toggle` (close without a name closes every open one). Notifications: `acun:modal:opened`, `acun:modal:closed`, `acun:drawer:opened`, `acun:drawer:closed`. The first names (`open-modal`, `close-drawer`, `drawer-state-changed`, `modal-closed`…) and `window.acunScrollLock` (with a console warning) still work.

The JS of the PRO components (charts, maps, date ranges, the unsaved-changes guard, the form modal) and the form modal and dialog styles come with `acunsoft/acun-pro`; see its README.

## Exports

| Import | Contents |
|---|---|
| `@acunsoft/acun-ui` (CSS, `style` condition) | `css/panel.css` — theme and panel layer |
| `@acunsoft/acun-ui` (JS) | `js/index.js` — sets up `window.AcunUI`; exports `VERSION`, `EVENTS`, `scrollLock`, `overlays`, `focusFirst`, `trapTab`, `rememberFocus`, `focusable`, `drawer`, `modal`, `syncSelectValues`, `skipDetachedMorphs` |
| `@acunsoft/acun-ui/css/*` | `panel.css` |
| `@acunsoft/acun-ui/js/*` | individual modules, e.g. `js/behavior/select-sync.js` |
| `@acunsoft/acun-ui/vite` | `woff2Only()` — a Vite plugin that keeps only the WOFF2 files of laravel-vite-plugin's font build |

## Türkçe

Acun UI'nin ücretsiz sürümü: Laravel için Blade ve Livewire bileşenleri, CSS ve JavaScript çekirdeği tek pakette (Composer `acunsoft/acun-ui`, npm `@acunsoft/acun-ui`). Ücretli `acunsoft/acun-pro` ve HTML sürümü bu paketi kullanır. Kurulum yukarıdaki adımlarla aynıdır; Türkçe doküman ürün sitesindedir.

## License

MIT © Acunsoft
