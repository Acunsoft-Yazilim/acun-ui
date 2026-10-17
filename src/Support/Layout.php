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
 * The page values live in a scoped (per-request) container binding. 'mode' is light, dark or
 * system; 'theme' is a theme's key (Appearance). A 1.x 'theme' of light, dark or system is
 * read as the mode until 3.0.
 */
final class Layout
{
    public const KEYS = ['mode', 'theme', 'brand_color', 'skin', 'content_width', 'semi_dark', 'sidebar_mode', 'navbar_mode', 'customizer', 'theme_toggle'];

    /** @deprecated The preset colours are Appearance::COLORS (kept until 3.0). */
    public const BRAND_COLORS = Appearance::COLORS;

    /** @var array<string, mixed> */
    private array $page = [];

    /**
     * The presets with their names, for acun:brand-color-picker: key => [hex, name].
     *
     * @deprecated Appearance::presets() (kept until 3.0).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function brandPresets(): array
    {
        return Appearance::presets();
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

        // 1.x: the theme was the mode.
        if (Appearance::isMode($values['theme'] ?? null)) {
            $values['mode'] ??= $values['theme'];
            unset($values['theme']);
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

    /**
     * The application's default from config/acun-ui.php, or null. A 1.x 'theme' of light, dark
     * or system is the mode's default.
     */
    public static function default(string $key): mixed
    {
        $legacy = config('acun-ui.layout.theme');

        return match ($key) {
            'mode' => config('acun-ui.layout.mode') ?? (Appearance::isMode($legacy) ? $legacy : null),
            'theme' => Appearance::isMode($legacy) ? null : $legacy,
            default => config('acun-ui.layout.'.$key),
        };
    }

    /**
     * Defaults for the browser-side stores: used only when there is neither a
     * forced/saved value nor a choice stored in the browser.
     *
     * @return array<string, mixed>
     */
    public static function clientDefaults(): array
    {
        return collect(['mode', 'brand_color', 'skin', 'content_width', 'semi_dark', 'sidebar_mode', 'navbar_mode'])
            ->mapWithKeys(fn (string $key) => [$key => self::default($key)])
            ->all();
    }
}
