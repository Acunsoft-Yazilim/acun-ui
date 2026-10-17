# Changelog

## Unreleased (2.0)

Details, with who is affected and how to fix each change: https://ui.acunsoft.com/upgrade.html

### Breaking changes

- One free package: `acunsoft/acun-ui-core` (the CSS and JS) and `acunsoft/acun-ui-free` (the Blade and Livewire components) are now `acunsoft/acun-ui` on Composer and `@acunsoft/acun-ui` on npm. The PHP namespace (`Acun\Ui\`), the service provider, the `acun-ui` view namespace, the `<acun:*>` tags, the config file and the publish tags are unchanged. Apps change their `composer.json`, the npm dependency (`"@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui"`), and the `@acunsoft/acun-ui-core` imports and `vendor/acunsoft/acun-ui-free` paths in their CSS, JS and Vite config.
- The `pro` block of `config/acun-ui.php` moved to `config/acun-pro.php` (`panel.assets`) in the paid `acunsoft/acun-pro`, which still reads a 1.x block until 3.0. The layouts include `acun-pro::layout-extras` (was `acun-ui-pro::layout-extras`).
- The panel shell moved to the paid `acunsoft/acun-pro`: the layouts `acun:layout.sidebar`, `horizontal`, `auth`, `message` and `print`, `acun:menu` with `Acun\Ui\Menu\Menu` (now `Acun\Panel\Menu`; the old name is an alias there until 3.0), `acun:nav.*`, `acun:user-menu`, `acun:filter-button`, `acun:filter-context`, `acun:auth-panel` and `acun:message-panel`, with their CSS (the content area and docked filter panel, the sticky top bar, the side menu) and their translations. This package keeps `acun:layout.head` and `acun:layout.blank`. An application with only this package that uses them adds `acunsoft/acun-pro`; with both packages nothing changes. Without it a paid tag stops with "… is an Acun Pro component (acunsoft/acun-pro) …".
- The PRO dialogs' 1.x variables `--ac-surface-subtle`, `--ac-border`, `--ac-border-strong`, `--ac-text`, `--ac-text-muted`, `--ac-radius-md` and `--ac-radius-lg` are gone, and `--ac-surface`, `--ac-primary`, `--ac-secondary`, `--ac-danger` and `--ac-shadow-*` are now design tokens (`--ac-danger` and `--ac-secondary` are those tones' fills). An application that set them sets the tokens instead (`--ac-line`, `--ac-ink`, `--ac-muted`, `--ac-radius`…).
- The components paint with the design tokens (`bg-surface`, `text-muted`, `border-line`, `bg-primary text-on-primary`, `bg-danger-tint text-danger-tint-ink`…) instead of palette shades and `dark:` classes, so each one follows the theme by itself. The light theme looks the same; in the dark theme a few shades are made consistent (secondary text is zinc-400, body text zinc-300, status text on a tint the 300 shade, status icons, dots and progress bars the 400 shade, a checked checkbox, radio or switch the primary fill, the light button dark grey). Application CSS or tests that look for a kit class (`text-gray-500`, `bg-brand-50`, `dark:bg-zinc-900`, `ui-btn-primary`…) use the token class instead.
- `.ui-btn`, `.ui-btn-*`, `.ui-btn-shadow` and the per-variant variables (`--btn-success-bg`, `--btn-light-color`…) are gone: `acun:button` uses the tone tokens (`bg-success hover:bg-success-hover text-on-success`, `shadow-success/90`). A theme changes a tone with `--ac-tone-success` (and so on). The primary colour's `--btn-primary-bg`, `--btn-primary-hover` and `--btn-primary-color` are gone too: the user's colour is `--ac-user-*` (below).
- The fields' focus shadow is `focus:shadow-(--ac-focus-shadow)` (`--ac-focus-shadow-danger` on an invalid field), which a theme sets in its block; `--ui-focus-shadow` and `--ui-focus-shadow-error` are no longer read.
- The mode and the theme are `data-theme` on `<html>`: the light or dark member of the page's theme pair (`light` and `dark` for the built-in one), by the mode (light, dark or system, followed live). `class="dark"` is still written next to it for application code until 3.0, but it no longer selects the dark tokens: a dark section is `data-theme="dark"`. The semi-dark menu is `data-theme="dark"` on the aside (with its `dark` class, for application markup, until 3.0).
- The user's colour: `data-brand-color` is gone. A preset or a custom #rrggbb is measured once against the hardest text backgrounds of the enabled themes and written on `<html>` as `--ac-user-fill`, `--ac-user-fill-hover`, `--ac-user-on-fill`, `--ac-user-tone`, `--ac-user-tone-dark` (and `--ac-user-fill-dark`, `-fill-hover-dark`, `-on-fill-dark` for a colour with its own dark fill); every theme block and nested section reads them first. Overrides of `--color-brand-600`, `--brand-custom-600`, `--ui-focus-ring`, `--ui-backdrop` and `--ui-backdrop-blur` are no longer read: a theme sets `--ac-tone-primary` (`--ac-focus`, `--ac-backdrop`…) in `:root, [data-theme] { … }`.
- The presets are colours, `Acun\Ui\Support\Appearance::COLORS` (`Layout::BRAND_COLORS` is an alias until 3.0; its `dark` entry is now `['light' => '#3f404d', 'dark' => '#474747']`). A swatch shows the real fill (green #008933, red #d83838, yellow #9f6b13, blue #038197, not the bright 500 shades). The config island sends `colors` (key => colour) instead of `brandPresets`. Measured, the dark preset's tone in a dark theme is #696a74 (it was the hand-picked #71717b).
- Browser storage: one JSON object per scope, `localStorage['acun-ui:{scope}']` (`mode`, `brandColor`, `skin`, `contentWidth`, `sidebar`, `navbar`, `semiDark`). The scope is the head's `scope` prop, else `Appearance::use(…, scope:)`, else `panel` in the panel layouts, else `config('acun-ui.layout.scope')`, else `app`; the storefront uses `shop`. So two products on one origin never share a colour or a mode. The 1.x keys (`appearance`, `brandColor`, `skin`, `contentWidth`, `sidebarCollapsed`, `navbarMode`, `semiDark`) are not read: users' saved choices reset once.
- `config('acun-ui.layout.mode')` is the mode's default and `layout.theme` a theme's key; a 1.x `light`, `dark` or `system` in `layout.theme`, in a `theme` prop or in `Layout::page(['theme' => …])` is read as the mode until 3.0. `acun:layout.head`, `acun:layout.blank` (and the panel layouts) take `mode` and `scope`. The config island carries `scope`, `theme` (`{key, light, dark}`), `targets`, `colors` and `mode`, and `defaults.mode` (was `defaults.theme`); the preference POST sends `{ mode }` instead of `{ theme }`.
- `rounded-sm` … `rounded-3xl` read `--ac-radius` and `font-sans`, `font-mono`, `font-brand` read `--ac-font-*`: an application's `@theme { --radius-sm: … }` or `--font-sans` override sets the token instead. The shadow utilities keep their composed values (a colour modifier such as `shadow-black/40` needs them).
- JS: `$store.appearance.value` is the mode and `toggle()` reads `data-theme`; `brandShades(color, targets)` returns `{ fill, hover, onFill, tone, toneDark }`.
- `bg-dark`, `text-dark`, `border-dark` and `outline-dark` are the dark tone: #3f404d in the light theme, the neutral grey #2e2e2e in the dark theme (it was #3f404d in both). The kit's components already paint another colour there with `dark:`; application markup without a `dark:` class gets the grey.

### New

- Design tokens. `css/themes.css` sets the `--ac-*` roles of the built-in light and dark themes: the neutrals (page, surface, field, sunken, hover, track, inverse, line, control, ink, body, dim, muted, faint) and eight tones (primary, secondary, success, info, warning, danger, dark, accent), each with a fill, hover, `on-` text, ink, line, tint, soft and tint-ink colour, plus focus, backdrop, radius, font and shadow tokens. `css/tokens.css` turns the colours into Tailwind utilities that read the token on the element (`bg-surface`, `text-muted`, `bg-primary text-on-primary`, `border-line/80`). The kit's CSS entry imports both. `brand-*` (application code, until 3.0) follows the element's theme and the user's colour.
- `Acun\Ui\Support\Appearance`: `define($key, $light, $dark, $targets)` registers a theme pair (the keys `light`, `dark`, `system` and `default` are reserved, a dark member's name ends in `dark`), `use($key, scope:)` chooses the theme and the storage scope of a request, `pair()`, `light()`, `dark()` (`data-theme="{{ Appearance::dark() }}"` for a dark section), `targets()`, `colors()`, `presets()`.
- `AcunUI.isDark(element)` (the nearest `data-theme` ends in `dark`) and `AcunUI.tokenColor(element, '--ac-line')` (a token's colour on an element as `rgb()`), for code that draws in the theme's colours. `$store.brand.forget()` forgets the saved colour.
- The boot script gives the semi-dark menu (`data-acun-semi-dark`) and the navbar (`data-acun-navbar`) the browser's saved choice as the parser adds them, before the first paint: no flash of the server's value. `wire:navigate` restores `data-theme`, the user's colour and the menus; a page of another scope starts from that scope's choices.
- `scripts/codemod-tokens.mjs` rewrites palette and `dark:` classes in your own views with the rules the kit used (`node vendor/acunsoft/acun-ui/scripts/codemod-tokens.mjs resources/views`, `--dry` to preview); it lists what it leaves for a person.
- Sections: the parts that stay light or dark in every theme are sections with their own `data-theme` (the QR code and the colour picker's tick are light, the carousel's controls sit on the picture in a light section).
- `acun:avatar`, `acun:avatar-group`, `acun:kbd`, `acun:skeleton`, `acun:switch`, `acun:empty-state` and `acun:copy-button` are free (they were PRO), with their translations.
- `Acun\Ui\AcunUi::addComponentPath($path, published: false)` adds a component folder to the `acun` prefix. Published copies (`resources/views/vendor/acun-ui/components/ui`, and Acun Pro's `vendor/acun-pro` and 1.x `vendor/acun-ui-pro`) are searched before both packages, whatever order Composer lists the providers in, so a copy keeps replacing its component after the component moved between the packages.

## 1.1.0

Details, with who is affected and how to fix each change: https://ui.acunsoft.com/upgrade.html

### New

- `input-class` on form fields styles the control itself; a class in it replaces the kit's class of the same kind, never the error frame.
- Modal and drawer parts can be drawn by a nested Livewire component.
- Dark-theme placeholders, the command palette's hints and button counters read at least 4.5:1. The button counter is framed in the text colour instead of a tinted fill.
- Toast types are read regardless of case; `download` can also be a file name in a JavaScript event.
- Preset colours: links, the active menu item and focus frames use the button's own colour (each preset's `--color-brand-600` is its button fill). The `dark` preset is a neutral grey in the dark theme.
- Custom colours: the button text stays the same on hover (the background gets 12% black, or 12% white where dark text would not read). The dark-theme tone stands out 3:1 on every dark surface, the lightest being zinc-800.
- `--ui-focus-ring` sets the colour of the focus ring, with the `outline-focus` and `ring-focus` utilities.
- A modal or drawer whose parts a nested Livewire component draws keeps its accessible name after Livewire updates.
- Tab stays inside an open modal `<dialog>` within a trapped panel (the form modal's delete dialog).

### Breaking changes

- `<acun:*>` tags render from the kit's own `acun` namespace: copies in `resources/views/vendor/acun-ui/components/ui/` are now used, and `resources/views/components/ui/` no longer overrides `<acun:*>`.
- `acun:input`, `select`, `textarea`, `checkbox`, `radio` and `input-group` put `class`, `style`, `x-show`, `x-cloak` and `wire:key` on the outer wrapper; use `input-class` to style the control. `acun:search` moves `style`, `x-show` and `wire:key` there too.
- `acun:checkbox` keeps its `<label>` as the root when an error shows; the message is a `<span>` inside it.
- Unsafe links (`javascript:`, `data:`, another host's `//host`) print `#` in every link prop, and a warning is logged.
- `acun:search` with an unsafe `action` searches with GET on the current page, without a CSRF token.
- The locale layer left the kit: `SetLocale`, `Localization`, the `acun-ui.locale` route and config. `acun:locale-switcher` only draws and needs `action` and `locales`.
- `WithToasts::toast()` and `toastAfterRedirect()` have a new `bool $download = false` parameter.
- Field ids repeated in one render get `-2`, `-3`.
- `lang/tr.json` lost the keys `Showing`, `of`, `to`, `results`, `Static`, `open`, `close` and `move`: the pagination summary is one sentence (`Showing :first to :last of :total results`).
- `navbar-mode="hidden"` hides the top bar only from `lg` up.
- The layouts wrap the page content in `<main id="main-content">`.
- Tailwind CSS 4.1 or newer is required: `panel.css` uses `@source inline()`.
- The select fix (writing a `<select wire:model>`'s value again after a Livewire update) is opt-in: `config('acun-ui.select_sync')` or `data-acun-select-sync` on a container.
