<?php

namespace Acun\Ui\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

/**
 * @acunFonts: Laravel's @fonts without the inline style block.
 *
 * The font plugin of laravel-vite-plugin (vite.config.js: fonts: [bunny(...)]) builds a
 * fonts-*.css file next to the font files, but @fonts prints that file's content into
 * every page (one @font-face per weight, subset and format). This prints the same preload
 * links and a <link rel="stylesheet"> to the built file instead: cached by the browser,
 * nothing inline. While the Vite dev server runs it is @fonts as it is. Before Laravel 13
 * (no font plugin, no @fonts) it links the families from Bunny Fonts instead (fallback()), the
 * provider the font plugin uses by default; config('acun-ui.fonts_fallback') false turns that off
 * for an application that loads the fonts in its own CSS.
 * @acunFonts('inter') for some of the families preloads only theirs and links the same
 * file: it holds every family's @font-face, but a browser downloads only the fonts a page
 * uses, so one cached file is cheaper than inlining a page's families on every page.
 */
final class Fonts
{
    /** @var array<string, array{0: string, 1: mixed}> path => [modified time and size, decoded manifest] */
    private static array $manifests = [];
    /** @param  list<string>|string|null  $aliases */
    public static function tags(array|string|null $aliases = null): HtmlString
    {
        // A text or heading font chosen from the font library (FontLibrary, acun:layout.head) replaces the theme's own:
        // that one is not preloaded, so it is never downloaded.
        if (is_array($aliases)) {
            $aliases = FontLibrary::withoutReplaced(array_values(array_filter($aliases, 'is_string')));
        }

        $vite = app(Vite::class);

        if (! method_exists($vite, 'fonts')) {
            return self::fallback($aliases);
        }

        $html = (string) $vite->fonts($aliases);

        if ($vite->isRunningHot() || ! str_contains($html, '<style')) {
            return new HtmlString($html);
        }

        // The build directory, manifest name, URL builder and nonce of the application's Vite setup.
        [$directory, $manifestFile, $nonce] = (fn (): array => [$this->buildDirectory, $this->fontsManifestFilename, $this->nonce])->call($vite);
        $manifest = self::manifest(public_path($directory.'/'.$manifestFile));
        $file = $manifest['style']['file'] ?? null;

        if (! is_string($file) || $file === '') {
            return new HtmlString($html);
        }

        $url = (fn (string $path): string => $this->assetPath($path))->call($vite, $directory.'/'.$file);
        $link = '<link rel="stylesheet" href="'.e($url).'"'.($nonce ? ' nonce="'.e($nonce).'"' : '').' />';

        return new HtmlString((string) preg_replace('~<style\b[^>]*>.*?</style>~s', $link, $html, 1));
    }

    /**
     * Before Laravel 13: the families (default: the kit's Public Sans and DM Mono) by their aliases
     * (public-sans, dm-mono, fraunces…): those of the font library (FontLibrary) from the package, the
     * others from Bunny Fonts (Bunny's names too). Empty when acun-ui.fonts_fallback is false.
     *
     * @param  list<string>|string|null  $aliases
     */
    public static function fallback(array|string|null $aliases = null): HtmlString
    {
        if (! config('acun-ui.fonts_fallback', true)) {
            return new HtmlString('');
        }

        $aliases = collect($aliases === null ? ['public-sans', 'dm-mono'] : (array) $aliases)
            ->filter(fn ($alias): bool => is_string($alias) && preg_match('~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $alias) === 1);

        // The font library's families come from the package itself; only the others (DM Mono) from Bunny Fonts.
        $local = $aliases->filter(fn (string $alias): bool => FontLibrary::has($alias))->unique()
            ->map(fn (string $alias): string => FontLibrary::faces($alias))
            ->implode("\n");
        $nonce = FontLibrary::nonce();
        $html = $local === '' ? '' : '<style data-acun-fonts'.($nonce ? ' nonce="'.e($nonce).'"' : '').'>'.$local.'</style>';

        $families = $aliases->reject(fn (string $alias): bool => FontLibrary::has($alias))
            // DM Mono has 400 and 500 (the kit's code and numeric labels); a text family the weights the kit uses.
            ->map(fn (string $alias): string => $alias.':'.($alias === 'dm-mono' ? '400,500' : '400,500,600,700'))
            ->unique()
            ->implode('|');

        if ($families === '') {
            return new HtmlString($html);
        }

        $url = 'https://fonts.bunny.net/css?family='.$families.'&display=swap';

        return new HtmlString($html.'<link rel="preconnect" href="https://fonts.bunny.net" crossorigin /><link rel="stylesheet" href="'.e($url).'" />');
    }

    /** The decoded fonts manifest, read again only when the file changes. */
    private static function manifest(string $path): mixed
    {
        clearstatcache(true, $path);
        $stat = @stat($path);

        if ($stat === false) {
            return null;
        }

        $version = $stat['mtime'].'-'.$stat['size'];

        if ((self::$manifests[$path][0] ?? null) !== $version) {
            self::$manifests[$path] = [$version, json_decode((string) file_get_contents($path), true)];
        }

        return self::$manifests[$path][1];
    }
}
