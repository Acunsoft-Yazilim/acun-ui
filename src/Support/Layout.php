<?php

namespace Acun\Ui\Support;

use InvalidArgumentException;

/**
 * Appearance settings of the panel layouts, in three layers:
 *
 *   1. Layout::page([...])   this page only; forced, wins over everything below
 *   2. the user's choice     a layout prop (saved account preference) or the browser
 *   3. config('acun-ui.layout')  the application's defaults
 *
 * and finally the package defaults. Keys: see self::KEYS and config/acun-ui.php.
 *
 *   // In a controller or a Livewire component's mount(), or at the top of a Blade page:
 *   Layout::page(['sidebar_mode' => 'collapsed', 'navbar_mode' => 'static']);
 *
 * The page values live in a scoped (per-request) container binding.
 */
final class Layout
{
    public const KEYS = ['theme', 'brand_color', 'skin', 'content_width', 'semi_dark', 'sidebar_mode', 'navbar_mode', 'customizer', 'theme_toggle'];

    /**
     * Primary colour presets (the html data-brand-color value => fill colour), from the
     * Acun UI palette: primary, success, danger, warning, info, secondary, dark. panel.css
     * gives each one its button shades; see brandPresets() for the names.
     *
     * 'default' is no preset: the page keeps its stylesheet's own --color-brand-600, which a
     * theme may change. Its value here is the kit's (panel.css @theme), for the server;
     * acun:brand-color-picker paints that swatch with the page's colour.
     */
    public const BRAND_COLORS = [
        'default' => '#794dff',
        'green' => '#00ca4b',
        'red' => '#fb4141',
        'yellow' => '#fcaa1e',
        'blue' => '#04b6d4',
        'gray' => '#6b7280',
        'dark' => '#3f404d',
    ];

    /** @var array<string, mixed> */
    private array $page = [];

    /**
     * The presets with their names, for acun:brand-color-picker: key => [hex, name].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function brandPresets(): array
    {
        $names = [
            'default' => __('Default'), 'green' => __('Green'), 'red' => __('Red'), 'yellow' => __('Yellow'),
            'blue' => __('Cyan'), 'gray' => __('Gray'), 'dark' => __('Dark'),
        ];

        $presets = [];

        foreach (self::BRAND_COLORS as $key => $hex) {
            $presets[$key] = [$hex, $names[$key]];
        }

        return $presets;
    }

    /**
     * Forces settings for the current page (merged with earlier calls).
     *
     * @param  array<string, mixed>  $values
     */
    public static function page(array $values): void
    {
        $unknown = array_diff(array_keys($values), self::KEYS);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown layout setting: '.implode(', ', $unknown).'. Allowed: '.implode(', ', self::KEYS).'.');
        }

        $state = app(self::class);
        $state->page = [...$state->page, ...$values];
    }

    /** Whether this page forces any setting (the runtime then leaves it with a full page load). */
    public static function forcesAny(): bool
    {
        return app(self::class)->page !== [];
    }

    /** The value this page forces, or null. */
    public static function forced(string $key): mixed
    {
        return app(self::class)->page[$key] ?? null;
    }

    /** The application's default from config/acun-ui.php, or null. */
    public static function default(string $key): mixed
    {
        return config('acun-ui.layout.'.$key);
    }

    /**
     * Defaults for the browser-side stores: used only when there is neither a
     * forced/saved value nor a choice stored in the browser.
     *
     * @return array<string, mixed>
     */
    public static function clientDefaults(): array
    {
        return collect(['theme', 'brand_color', 'skin', 'content_width', 'semi_dark', 'sidebar_mode', 'navbar_mode'])
            ->mapWithKeys(fn (string $key) => [$key => self::default($key)])
            ->all();
    }
}
