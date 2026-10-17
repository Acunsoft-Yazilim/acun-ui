<?php

namespace Acun\Ui\Support;

use InvalidArgumentException;

/**
 * The page's colours: the mode, the theme and the user's colour, as the runtime applies them.
 *
 *   mode     light | dark | system: which member of the theme's pair <html data-theme> names
 *   theme    a pair of data-theme names (css/themes.css): the built-in one is "default", with
 *            the members light and dark; a product defines its own (Appearance::define())
 *   colour   a preset (self::COLORS) or #rrggbb: written once as --ac-user-* on <html>, measured
 *            against the hardest text backgrounds of the enabled themes (self::targets())
 *   scope    the browser storage the choices are kept in (localStorage 'acun-ui:{scope}'), so
 *            two products on one origin (/panel and /shop) never share a colour or a mode
 *
 *   // A product's provider, once per enabled theme:
 *   Appearance::define('atelier', 'atelier', 'atelier-dark', ['light' => '#fffdf8', 'dark' => '#2e2a23']);
 *   // A middleware, for the current request:
 *   Appearance::use('atelier', scope: 'shop');
 *
 * The keys light, dark, system and default are reserved, so a 1.x theme value (light | dark |
 * system, read as the mode until 3.0) never names a theme.
 */
final class Appearance
{
    public const MODES = ['light', 'dark', 'system'];

    public const RESERVED = ['light', 'dark', 'system', 'default'];

    /** The built-in pair's key. */
    public const DEFAULT = 'default';

    /**
     * The text backgrounds a user's colour is measured against when a theme declares none: the
     * built-in light theme's surface and the dark theme's field (its lightest text background),
     * both from css/themes.css (tests/brand-contrast.test.mjs checks that they agree).
     */
    public const TARGETS = ['light' => '#ffffff', 'dark' => '#1f1f1f'];

    /**
     * The preset colours: key => the fill (#rrggbb), or ['light' => …, 'dark' => …] for a colour
     * with its own fill in a dark theme. Each is the palette's solid fill (white text reads 4.5:1
     * on it); the runtime measures the rest (js/appearance/theme.js brandShades). 'default' is the
     * theme's own colour: no user variables; its value here is the built-in purple, for the swatch.
     */
    public const COLORS = [
        'default' => '#794dff',
        'green' => '#008933',
        'red' => '#d83838',
        'yellow' => '#9f6b13',
        'blue' => '#038197',
        'gray' => '#6b7280',
        // A dark colour would disappear on the black dark theme: there it is the dark grey.
        'dark' => ['light' => '#3f404d', 'dark' => '#474747'],
    ];

    /** Request attributes for the current theme and scope (Appearance::use()). */
    private const THEME_ATTRIBUTE = 'acun-ui.appearance.theme';

    private const SCOPE_ATTRIBUTE = 'acun-ui.appearance.scope';

    /** @var array<string, array{light: string, dark: ?string, targets: array{light: string, dark: string}}> */
    private array $themes = [];

    /**
     * Registers a theme pair: its light member (default: the key) and its dark member (null: a
     * single, light member; the mode choice is then hidden), and the text backgrounds a user's
     * colour must read on (light: the darkest light one, dark: the lightest dark one).
     *
     * @param  array{light?: string, dark?: string}  $targets
     */
    public static function define(string $key, ?string $light = null, ?string $dark = null, array $targets = []): void
    {
        $light ??= $key;
        $name = '/^[a-z][a-z0-9-]*\z/';

        foreach (['key' => $key, 'light member' => $light] + ($dark === null ? [] : ['dark member' => $dark]) as $what => $value) {
            if (! preg_match($name, $value)) {
                throw new InvalidArgumentException("Theme {$what} [{$value}]: use lowercase letters, digits and dashes.");
            }

            if (in_array($value, self::RESERVED, true)) {
                throw new InvalidArgumentException("Theme {$what} [{$value}] is reserved (".implode(', ', self::RESERVED).').');
            }
        }

        if (str_ends_with($light, 'dark')) {
            throw new InvalidArgumentException("Theme [{$key}]: a light member's name must not end in \"dark\" ([{$light}]).");
        }

        if ($dark !== null && ! str_ends_with($dark, 'dark')) {
            throw new InvalidArgumentException("Theme [{$key}]: a dark member's name must end in \"dark\" ([{$dark}]).");
        }

        foreach ($targets as $side => $hex) {
            if (! array_key_exists($side, self::TARGETS) || ! is_string($hex) || ! preg_match('/^#[0-9a-f]{6}\z/i', $hex)) {
                throw new InvalidArgumentException("Theme [{$key}]: targets are ['light' => '#rrggbb', 'dark' => '#rrggbb'].");
            }
        }

        $theme = ['light' => $light, 'dark' => $dark, 'targets' => array_map('strtolower', $targets) + self::TARGETS];
        $state = app(self::class);

        if (isset($state->themes[$key]) && $state->themes[$key] !== $theme) {
            throw new InvalidArgumentException("Theme [{$key}] is already defined with other members.");
        }

        foreach ($state->themes as $other => $defined) {
            $shared = array_intersect(array_filter([$light, $dark]), array_filter([$defined['light'], $defined['dark']]));

            if ($other !== $key && $shared !== []) {
                throw new InvalidArgumentException("Theme [{$key}] uses a data-theme name of [{$other}]: ".implode(', ', $shared).'.');
            }
        }

        $state->themes[$key] = $theme;
    }

    /**
     * Every theme pair, the built-in one first: key => [light, dark, targets].
     *
     * @return array<string, array{light: string, dark: ?string, targets: array{light: string, dark: string}}>
     */
    public static function themes(): array
    {
        return [self::DEFAULT => ['light' => 'light', 'dark' => 'dark', 'targets' => self::TARGETS]] + app(self::class)->themes;
    }

    public static function has(?string $key): bool
    {
        return is_string($key) && array_key_exists($key, self::themes());
    }

    /**
     * The theme of the current request: the one Appearance::use() chose, else config
     * ('acun-ui.layout.theme', when it names a theme), else the built-in one.
     */
    public static function current(): string
    {
        $chosen = request()->attributes->get(self::THEME_ATTRIBUTE);
        $configured = config('acun-ui.layout.theme');

        return match (true) {
            self::has($chosen) => $chosen,
            self::has($configured) => $configured,
            default => self::DEFAULT,
        };
    }

    /**
     * The pair a page uses: the theme $key names (an unknown key or null: the current one).
     *
     * @return array{key: string, light: string, dark: ?string}
     */
    public static function pair(?string $key = null): array
    {
        $key = self::has($key) ? $key : self::current();
        $theme = self::themes()[$key];

        return ['key' => $key, 'light' => $theme['light'], 'dark' => $theme['dark']];
    }

    /** The current pair's light member, the data-theme of <html> before the boot script runs. */
    public static function light(): string
    {
        return self::pair()['light'];
    }

    /** The current pair's dark member (a dark section: data-theme="{{ Appearance::dark() }}"); the light one when it has none. */
    public static function dark(): string
    {
        $pair = self::pair();

        return $pair['dark'] ?? $pair['light'];
    }

    /**
     * Chooses the theme and the storage scope for the current request (a product's middleware).
     */
    public static function use(string $key, ?string $scope = null): void
    {
        if (! self::has($key)) {
            throw new InvalidArgumentException("Unknown theme [{$key}]. Defined: ".implode(', ', array_keys(self::themes())).'.');
        }

        request()->attributes->set(self::THEME_ATTRIBUTE, $key);

        if ($scope !== null) {
            request()->attributes->set(self::SCOPE_ATTRIBUTE, self::scopeName($scope));
        }
    }

    /** The storage scope Appearance::use() set for this request, or null. */
    public static function scope(): ?string
    {
        return request()->attributes->get(self::SCOPE_ATTRIBUTE);
    }

    /** A scope name for the storage key: letters, digits, dashes and underscores. */
    public static function scopeName(string $scope): string
    {
        if (! preg_match('/^[a-z0-9][a-z0-9_-]*\z/i', $scope)) {
            throw new InvalidArgumentException("Appearance scope [{$scope}]: use letters, digits, dashes and underscores.");
        }

        return $scope;
    }

    /**
     * The hardest text backgrounds across the enabled themes: the darkest light one and the
     * lightest dark one. A colour measured against them reads in every section of every theme.
     *
     * @return array{light: string, dark: string}
     */
    public static function targets(): array
    {
        $targets = array_column(self::themes(), 'targets');
        $lights = array_column($targets, 'light');
        $darks = array_column($targets, 'dark');
        usort($lights, fn (string $a, string $b) => self::luminance($a) <=> self::luminance($b));
        usort($darks, fn (string $a, string $b) => self::luminance($b) <=> self::luminance($a));

        return ['light' => $lights[0], 'dark' => $darks[0]];
    }

    /**
     * The preset colours the runtime applies (the config island): key => #rrggbb or
     * ['light' => …, 'dark' => …]. 'default' is not one: it removes the user's colour.
     *
     * @return array<string, string|array{light: string, dark: string}>
     */
    public static function colors(): array
    {
        return array_diff_key(self::COLORS, [self::DEFAULT => true]);
    }

    /**
     * The presets with their names, for a colour picker: key => [swatch, name]. The swatch is the
     * colour's fill (the light one for a colour with its own dark fill).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function presets(): array
    {
        $names = [
            'default' => __('Default'), 'green' => __('Green'), 'red' => __('Red'), 'yellow' => __('Yellow'),
            'blue' => __('Cyan'), 'gray' => __('Gray'), 'dark' => __('Dark'),
        ];

        $presets = [];

        foreach (self::COLORS as $key => $color) {
            $presets[$key] = [is_array($color) ? $color['light'] : $color, $names[$key]];
        }

        return $presets;
    }

    public static function isMode(mixed $value): bool
    {
        return in_array($value, self::MODES, true);
    }

    /**
     * The mode and the theme a page passes (a head or layout prop, Layout::page()), as one
     * pair: a value the page forces wins, and a 1.x theme value (light | dark | system) is the mode.
     *
     * @return array{mode: ?string, theme: array{key: string, light: string, dark: ?string}}
     */
    public static function page(?string $mode = null, ?string $theme = null): array
    {
        $mode = Layout::forced('mode') ?? $mode;
        $theme = Layout::forced('theme') ?? $theme;

        if (self::isMode($theme)) {
            $mode ??= $theme;
            $theme = null;
        }

        return ['mode' => self::isMode($mode) ? $mode : null, 'theme' => self::pair($theme)];
    }

    /**
     * The data-theme of <html> on the first frame (and without JS): the dark member when the
     * page's mode is dark, else the light one; the boot script corrects it from the saved choice.
     *
     * @param  array{mode: ?string, theme: array{key: string, light: string, dark: ?string}}  $page  self::page()
     */
    public static function documentTheme(array $page): string
    {
        return $page['mode'] === 'dark' && $page['theme']['dark'] !== null ? $page['theme']['dark'] : $page['theme']['light'];
    }

    /** WCAG relative luminance of #rrggbb, as js/appearance/theme.js measures it. */
    private static function luminance(string $hex): float
    {
        $weights = [0.2126, 0.7152, 0.0722];
        $sum = 0.0;

        foreach (str_split(substr($hex, 1), 2) as $i => $channel) {
            $c = hexdec($channel) / 255;
            $sum += $weights[$i] * ($c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4);
        }

        return $sum;
    }
}
