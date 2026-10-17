# Changelog

## Unreleased (2.0)

Details, with who is affected and how to fix each change: https://ui.acunsoft.com/upgrade.html

### Breaking changes

- One free package: `acunsoft/acun-ui-core` (the CSS and JS) and `acunsoft/acun-ui-free` (the Blade and Livewire components) are now `acunsoft/acun-ui` on Composer and `@acunsoft/acun-ui` on npm. The PHP namespace (`Acun\Ui\`), the service provider, the `acun-ui` view namespace, the `<acun:*>` tags, the config file and the publish tags are unchanged. Apps change their `composer.json`, the npm dependency (`"@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui"`), and the `@acunsoft/acun-ui-core` imports and `vendor/acunsoft/acun-ui-free` paths in their CSS, JS and Vite config.

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
