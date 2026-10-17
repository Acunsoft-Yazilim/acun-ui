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
 * (no font plugin, no @fonts) it prints nothing: the fonts then come from the CSS.
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
        $vite = app(Vite::class);

        if (! method_exists($vite, 'fonts')) {
            return new HtmlString('');
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
