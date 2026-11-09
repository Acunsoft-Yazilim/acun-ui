<?php

namespace Acun\Ui\Support;

use Closure;
use Illuminate\Foundation\Vite;

/**
 * The kit's font library: 20 Google Fonts families (SIL Open Font License 1.1) that the package serves itself
 * (fonts/, from scripts/fonts.mjs): no request to Google or any other font host. Each family in the weights the kit
 * uses (400, 500, 600, 700; Lato has 400 and 700, a browser takes the nearest) and in the latin and latin-ext
 * subsets (latin-ext has the Turkish ğ, ş, ı and İ), WOFF2.
 *
 * The text and heading fonts of a page are layout settings (Layout), chosen in this order:
 *
 *   1. Layout::page(['font' => 'lora', 'heading_font' => 'playfair-display'])   this page only
 *   2. FontLibrary::using(fn () => …)        the application's choice, e.g. an administrator's setting in the database
 *   3. config('acun-ui.layout.font') and config('acun-ui.layout.heading_font')
 *
 * and else the theme's own fonts. An id the library does not have is ignored. acun:layout.head prints only the
 * chosen families' @font-face rules (a browser downloads a file only when the page uses that weight and those
 * characters) with the first paint's weights preloaded (400 and 600), and points the theme's tokens at them:
 * --ac-font-sans and --ac-font-brand (text), --ac-font-display (headings). @acunFonts then no longer preloads the
 * theme's own text or heading font that a chosen one replaces. acun:font-select is the field that picks one.
 */
final class FontLibrary
{
    /** The weights the kit uses (a family has these, or some of them: fonts.json). */
    public const WEIGHTS = [400, 500, 600, 700];

    /** The weights preloaded for the first paint (the family's nearest): body text and the semibold headings and buttons. */
    public const PRELOAD = [400, 600];

    public const PUBLISH_TAG = 'acun-ui-fonts';

    /** Under public/, where vendor:publish --tag=acun-ui-fonts copies fonts/. */
    public const PUBLISHED_DIRECTORY = 'vendor/acun-ui/fonts';

    /** The fallbacks behind each category's family while it loads (and where it cannot). */
    private const STACKS = [
        'sans-serif' => "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'",
        'serif' => "ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif",
    ];

    /** @var array<string, array{family: string, category: string, weights: list<int>, subsets: array<string, string>, license: string, source: string}>|null */
    private static ?array $library = null;

    /** @var (Closure(): (array{text?: string|null, heading?: string|null}|string|null))|null */
    private static ?Closure $resolver = null;

    /**
     * The application's choice, asked once per page: the text font's id, or ['text' => …, 'heading' => …]; null
     * for config's. For an administrator's setting kept in the database:
     *
     *   FontLibrary::using(fn () => ['text' => Setting::get('font'), 'heading' => Setting::get('heading_font')]);
     *
     * @param  (Closure(): (array{text?: string|null, heading?: string|null}|string|null))|null  $resolver
     */
    public static function using(?Closure $resolver): void
    {
        self::$resolver = $resolver;

        if (app()->bound('request')) {
            request()->attributes->remove('acun-ui.fonts');
        }
    }

    /**
     * The families: id => family, category (sans-serif | serif), weights, subsets (name => unicode-range), licence.
     *
     * @return array<string, array{family: string, category: string, weights: list<int>, subsets: array<string, string>, license: string, source: string}>
     */
    public static function all(): array
    {
        return self::$library ??= json_decode((string) file_get_contents(self::path('fonts.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function has(mixed $id): bool
    {
        return is_string($id) && array_key_exists($id, self::all());
    }

    /**
     * The families for a field: category => [id => family], sans-serif first.
     *
     * @return array<string, array<string, string>>
     */
    public static function options(): array
    {
        $groups = ['sans-serif' => [], 'serif' => []];

        foreach (self::all() as $id => $font) {
            $groups[$font['category']][$id] = $font['family'];
        }

        return array_filter($groups);
    }

    /** The CSS font-family value: the family, then its category's fallbacks. */
    public static function stack(string $id): string
    {
        $font = self::all()[$id];

        return "'".addcslashes($font['family'], "'\\")."', ".(self::STACKS[$font['category']] ?? self::STACKS['sans-serif']);
    }

    /** A file of the package's fonts/ folder. */
    public static function path(string $file = ''): string
    {
        return dirname(__DIR__, 2).'/fonts'.($file === '' ? '' : '/'.$file);
    }

    /**
     * A font file's URL, with its content hash (cached for a year): under config('acun-ui.assets.url') (a CDN, its
     * fonts/ folder), the published copy when it is the same as the package's, else the package route.
     */
    public static function url(string $id, string $file): string
    {
        $hash = Assets::hash(self::path($id.'/'.$file));
        $configured = config('acun-ui.assets.url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/').'/fonts/'.$id.'/'.$file.'?id='.$hash;
        }

        $published = public_path(self::PUBLISHED_DIRECTORY.'/'.$id.'/'.$file);

        if (is_file($published) && Assets::hash($published) === $hash) {
            return asset(self::PUBLISHED_DIRECTORY.'/'.$id.'/'.$file).'?id='.$hash;
        }

        return url(Assets::routePath('fonts/'.$id.'/'.$file)).'?id='.$hash;
    }

    /**
     * The weight a browser draws for $weight with the family's weights (CSS font matching): the same; for 400–500 the
     * next up to 500, then down, then up; under 400 down, then up; over 500 up, then down. Lato (400, 700): 600 → 700.
     */
    public static function nearest(string $id, int $weight): int
    {
        $weights = self::all()[$id]['weights'];
        sort($weights);

        if (in_array($weight, $weights, true)) {
            return $weight;
        }

        $up = array_values(array_filter($weights, fn (int $w): bool => $w > $weight));
        $down = array_reverse(array_values(array_filter($weights, fn (int $w): bool => $w < $weight)));
        $order = match (true) {
            $weight >= 400 && $weight <= 500 => [...array_filter($up, fn (int $w): bool => $w <= 500), ...$down, ...array_filter($up, fn (int $w): bool => $w > 500)],
            $weight < 400 => [...$down, ...$up],
            default => [...$up, ...$down],
        };

        return $order[array_key_first($order)];
    }

    /** The file of a weight in a subset. */
    public static function file(string $id, string $subset, int $weight): string
    {
        return $id.'-'.$subset.'-'.$weight.'-normal.woff2';
    }

    /**
     * A family's @font-face rules: every weight in every subset (a browser downloads only what the page uses).
     *
     * @param  list<int>|null  $weights
     */
    public static function faces(string $id, ?array $weights = null): string
    {
        $font = self::all()[$id];
        $family = addcslashes($font['family'], "'\\");
        $rules = [];

        foreach ($weights ?? $font['weights'] as $weight) {
            foreach ($font['subsets'] as $subset => $range) {
                $rules[] = "@font-face{font-family:'".$family."';font-style:normal;font-weight:".$weight.';font-display:swap;src:url('.self::url($id, self::file($id, $subset, $weight)).") format('woff2');unicode-range:".$range.'}';
            }
        }

        return implode("\n", $rules);
    }

    /**
     * The page's chosen fonts: Layout::page(), else the application's (using()), else config; null where none is
     * chosen or the id is not in the library (the theme's own font then).
     *
     * @return array{text: string|null, heading: string|null}
     */
    public static function chosen(): array
    {
        // The application's resolver (a database read) runs once per request.
        $attributes = request()->attributes;
        if (! $attributes->has('acun-ui.fonts')) {
            $value = self::$resolver !== null ? (self::$resolver)() : null;
            $attributes->set('acun-ui.fonts', is_array($value) ? $value : ['text' => $value]);
        }
        $resolved = $attributes->get('acun-ui.fonts');

        $pick = fn (string $key, string $role): ?string => collect([Layout::forced($key), $resolved[$role] ?? null, Layout::default($key)])
            ->first(fn (mixed $id): bool => self::has($id));

        return ['text' => $pick('font', 'text'), 'heading' => $pick('heading_font', 'heading')];
    }

    /**
     * The aliases @acunFonts still preloads: without the theme's text font (its first alias that is not a
     * monospace one) when another text font is chosen, nor its heading font (the next one) when another heading
     * font is.
     *
     * @param  list<string>  $aliases
     * @return list<string>
     */
    public static function withoutReplaced(array $aliases): array
    {
        $chosen = self::chosen();
        $roles = array_values(array_filter($aliases, fn (string $alias): bool => ! str_contains($alias, 'mono')));
        $replaced = array_filter([
            $chosen['text'] !== null && ($roles[0] ?? null) !== $chosen['text'] ? $roles[0] ?? null : null,
            $chosen['heading'] !== null && ($roles[1] ?? null) !== $chosen['heading'] ? $roles[1] ?? null : null,
        ]);

        return array_values(array_diff($aliases, $replaced));
    }

    /** The CSP nonce of the application's Vite setup (null without one). */
    public static function nonce(): ?string
    {
        $vite = app(Vite::class);

        return method_exists($vite, 'cspNonce') ? $vite->cspNonce() : null;
    }

    /**
     * acun:layout.head's tags for the chosen fonts: the first paint's weights preloaded (not on a wire:navigate
     * visit, whose head merge would fetch them again), their @font-face rules and the tokens pointed at them. Empty
     * when none is chosen.
     */
    public static function tags(?string $nonce = null): string
    {
        $chosen = self::chosen();

        if ($chosen['text'] === null && $chosen['heading'] === null) {
            return '';
        }

        $nonce ??= self::nonce();
        $nonceAttribute = $nonce ? ' nonce="'.e($nonce).'"' : '';
        $html = [];
        $css = [];
        $tokens = [];

        foreach (array_unique(array_filter($chosen)) as $id) {
            $css[] = self::faces($id);
        }

        if ($chosen['text'] !== null) {
            $stack = self::stack($chosen['text']);
            $tokens[] = '--ac-font-sans:'.$stack.';--ac-font-brand:'.$stack;

            if (! request()->hasHeader('X-Livewire-Navigate')) {
                foreach (array_unique(array_map(fn (int $weight): int => self::nearest($chosen['text'], $weight), self::PRELOAD)) as $weight) {
                    foreach (array_keys(self::all()[$chosen['text']]['subsets']) as $subset) {
                        $html[] = '<link rel="preload" as="font" type="font/woff2" href="'.e(self::url($chosen['text'], self::file($chosen['text'], $subset, $weight))).'" crossorigin />';
                    }
                }
            }
        }

        if ($chosen['heading'] !== null) {
            $tokens[] = '--ac-font-display:'.self::stack($chosen['heading']);
        }

        // Unlayered: wins over the themes' tokens (@layer base) on <html> and on every element with its own data-theme.
        $css[] = ':root,[data-theme]{'.implode(';', $tokens).'}';
        $html[] = '<style data-acun-fonts'.$nonceAttribute.'>'.implode("\n", $css).'</style>';

        return implode("\n", $html);
    }
}
