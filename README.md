# Acun UI

The free edition of **Acun UI** for Laravel + Livewire + Tailwind CSS, one Composer package (`acunsoft/acun-ui`) that also carries its npm package (`@acunsoft/acun-ui`, linked from `vendor/` by `acun:install`):

- **Blade and Livewire components** for Laravel (`<acun:*>`, `src/`, `resources/views`), auto-discovered through `Acun\Ui\AcunUiServiceProvider`: buttons, cards, alerts, badges, form fields, tables and list parts, modals, drawers, dropdowns, tabs, the command palette, toasts, avatars, keyboard keys, skeletons, switches, empty states, copy buttons and more, the theme switcher (`acun:theme-switcher`), and the document layouts `acun:layout.head` and `acun:layout.blank`.
- **The CSS and JavaScript core**: a Tailwind CSS v4 layer with the design tokens and the built-in light and dark themes (`css/panel.css`, `tokens.css`, `themes.css`) and the runtime (`js/`) the components' markup runs on, with or without Livewire.

**Acun Pro** (`acunsoft/acun-pro`, Composer, paid) adds the panel (the sidebar, horizontal, auth, message and print layouts, the JSON menu, the user menu and the list filters), advanced components, guarded form modals, charts, maps and e-commerce (storefront pages and the Nova, Vivid and Atelier themes) on top of this package. Its JS and CSS ship inside that package (npm `@acunsoft/acun-pro` from `vendor/`). Without it, a tag of Acun Pro (`<acun:layout.sidebar>`, `<acun:menu>`, `<acun:combobox>`…) stops with an error that says so.

A package of components can join the `acun` tag prefix with `Acun\Ui\AcunUi::addComponentPath($path)`; folders added with `published: true` (vendor:publish copies) are searched before every package folder.

## Requirements

- Laravel 12 or 13 with Livewire 4 (it brings Alpine.js 3)
- Tailwind CSS 4 (no plugin: the kit draws its own form controls, `css/forms.css`)

## Install

In a Laravel app, two commands:

```bash
composer require acunsoft/acun-ui
php artisan acun:install
```

`acun:install` adds only the lines that are missing and changes nothing else:

- `package.json`: `"@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui"`, so npm takes the CSS and JavaScript from the copy in `vendor/` and they always match the installed Blade components;
- `resources/css/app.css`: `@import '@acunsoft/acun-ui';` and `@source '../../vendor/acunsoft/acun-ui/resources/views';` after `@import 'tailwindcss';` (Laravel's own font, an `@theme` block's `--font-sans`, overrides the kit's: an interactive run asks to remove it, `--remove-font` removes it without asking, and without interaction it is left with a warning);
- `resources/js/app.js`: `import '@acunsoft/acun-ui';`.

It no longer adds `@tailwindcss/forms` (2.1 and older did; a line that is there is left alone and does no harm). It offers to run `npm install` and `npm run build` (`--npm`, `--no-npm`; pnpm, yarn and bun by their lock file), copies an example page with `--example`, adds Acun Pro's lines when `acunsoft/acun-pro` is installed, and asks nothing with `--no-interaction` (CI). A second run changes nothing. `php artisan acun:install --check` changes nothing: it lists what is set up and what is missing, and exits with 1 when something is. Then put `<acun:layout.blank>` on a page (or `<acun:layout.head>` in your own layout) and run `npm run dev`.

The same lines by hand: https://docs.acunui.com/installation.html#manual

The npm package always comes from `vendor/` through that `file:` line, so the CSS and JavaScript match the installed Blade components; it is not installed from the npm registry.

### CSS

Import it after Tailwind and tell Tailwind where your markup lives (in `resources/css/app.css`; `acun:install` writes these lines):

```css
@import 'tailwindcss';
@import '@acunsoft/acun-ui';

/* Laravel: the Blade components of this package */
@source '../../vendor/acunsoft/acun-ui/resources/views';
```

Without the `@source` line the components render unstyled. If Laravel's default file has an `@theme` block that sets `--font-sans`, remove it: Acun UI sets the fonts.

The kit draws its own form controls: every `<input>`, `<select>` and `<textarea>` a component renders carries `data-acun-control`, and `css/forms.css` styles only those (the `@tailwindcss/forms` plugin is not needed, and the application's other fields are left alone). Your own markup puts `data-acun-control` on a field to get the same base.

**Global rules.** `css/panel.css` is `css/core.css` (tokens, themes, form base, the kit's own rules) plus `css/base.css` (rules that reach the whole page: the theme's line on a border without a colour class, the pointer cursor, the headings' letter spacing, the code font). For none of them, import `@acunsoft/acun-ui/css/core.css` instead of `@acunsoft/acun-ui`.

### Theming

The components paint with design tokens, not palette shades, so each one follows the theme by itself. Use the same utilities in your own markup instead of `gray-*`, `zinc-*` and `dark:` classes: `bg-page`, `bg-surface`, `text-ink`, `text-muted`, `border-line`, and per tone (primary, secondary, success, info, warning, danger, dark, accent) `bg-primary text-on-primary`, `text-primary-ink`, `bg-danger-tint text-danger-tint-ink`…

- **Mode and theme:** `acun:layout.head` writes `data-theme="light"` or `"dark"` on `<html>` before the first paint, from the mode: `light`, `dark` or `system` (`config('acun-ui.layout.mode')`, the layout's `mode` prop, or the user's choice). `data-theme` on any element makes that part another theme: `<aside data-theme="dark" class="bg-surface">`.
- **Primary colour:** set `--ac-tone-primary` (the other tones: `--ac-tone-success`…):

    ```css
    @layer base {
        :root, [data-theme] { --ac-tone-primary: #0f766e; }
    }
    ```

- **Theme switcher:** one component, `acun:theme-switcher`. `<acun:theme-switcher />` is the light/dark button; `as="drawer"` (a panel from the right) and `as="inline"` show the mode, the primary colour (presets or the user's own, measured for contrast) and the themes a package offers; `sections` picks them.

All the tokens, sections, the user's colour and the switcher's options: https://docs.acunui.com/theming.html

### JavaScript

```js
import '@acunsoft/acun-ui';
```

This is the Acun UI runtime; panel pages need it. It sets up one global, `window.AcunUI`, and installs once per page:

- the dialog behaviour of drawers, modals and the command palette: a counted page scroll lock, Escape and Tab for the newest dialog only, focus restore;
- the Alpine components `acunDrawer` and `acunModal`, and on pages with `acun:layout.head` the theme and menu stores (the menu stores drive Acun Pro's panel layouts);
- the Livewire fixes: `<select>` values after morphs, no morphs into detached components, lists kept in view, a full page load when leaving a page that forces its layout.

Without Vite, `@acunUiScripts` loads a browser build of the same code (`dist/`). The mode, the theme (`data-theme`) and the user's colour are applied before the first paint by a small blocking script that `acun:layout.head` loads (`js/boot.js`).

The API: `AcunUI.modal.open(name)` / `close(name?)`, `AcunUI.drawer.open(name)` / `close(name?)` / `toggle(name)`, `AcunUI.scrollLock.lock()` / `unlock()`, and `AcunUI.overlays.open(element, { close, trap })` / `close(element)` for a dialog of your own (scroll lock, Escape, Tab trap and focus restore together). The same commands as events on `window`, with `{ name }`: `acun:modal:open`, `acun:modal:close`, `acun:drawer:open`, `acun:drawer:close`, `acun:drawer:toggle` (close without a name closes every open one). Notifications: `acun:modal:opened`, `acun:modal:closed`, `acun:drawer:opened`, `acun:drawer:closed`. The first names (`open-modal`, `close-drawer`, `drawer-state-changed`, `modal-closed`…) and `window.acunScrollLock` (with a console warning) still work.

The JS of the PRO components (charts, maps, date ranges, the unsaved-changes guard, the form modal) and the form modal and dialog styles come with `acunsoft/acun-pro`; see its README.

## Exports

| Import | Contents |
|---|---|
| `@acunsoft/acun-ui` (CSS, `style` condition) | `css/panel.css` — tokens, themes and component layer |
| `@acunsoft/acun-ui` (JS) | `js/index.js` — sets up `window.AcunUI`; exports `VERSION`, `EVENTS`, `scrollLock`, `overlays`, `focusFirst`, `trapTab`, `rememberFocus`, `focusable`, `drawer`, `modal`, `syncSelectValues`, `skipDetachedMorphs` |
| `@acunsoft/acun-ui/css/*` | `panel.css` (= `core.css` + `base.css`; `core.css` imports `tokens.css`, `themes.css` and `forms.css`) |
| `@acunsoft/acun-ui/js/*` | individual modules, e.g. `js/behavior/select-sync.js` |
| `@acunsoft/acun-ui/vite` | `woff2Only()` — a Vite plugin that keeps only the WOFF2 files of laravel-vite-plugin's font build |

## Türkçe

Acun UI'nin ücretsiz sürümü: Laravel için Blade ve Livewire bileşenleri, `acun:layout.head` ve `acun:layout.blank` belge düzenleri, CSS ve JavaScript çekirdeği tek pakette (Composer `acunsoft/acun-ui`; npm paketi `@acunsoft/acun-ui` `vendor/` klasöründen bağlanır). Acun UI yalnız Laravel + Livewire + Tailwind CSS içindir. Panel düzenleri, JSON menü ve PRO bileşenler ücretli `acunsoft/acun-pro` paketindedir; o paket bu paketi kullanır. Kurulum iki komuttur: `composer require acunsoft/acun-ui`, ardından `php artisan acun:install`. Türkçe doküman ürün sitesindedir.

## License

MIT © Acunsoft
