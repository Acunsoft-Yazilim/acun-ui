<?php

namespace Acun\Ui\Support;

use Illuminate\Support\Facades\Vite;

/**
 * The browser builds of the @acunsoft/acun-ui runtime (dist/, built by `npm run build:js`).
 *
 * - acun-ui-boot.js: the small blocking script acun:layout.head loads to apply the
 *   theme before the first paint.
 * - acun-ui.js: the runtime itself, for applications that do not import
 *   '@acunsoft/acun-ui' through Vite; @acunUiScripts prints its tag (and the PRO
 *   package adds its own).
 *
 * Served by the package route like Livewire serves its own JS, from public/ once
 * published, or from config('acun-ui.assets.url'); every URL carries a content hash.
 * Scoped (one instance per request, Octane too): @acunUiScripts prints once per page.
 */
final class Assets
{
    public const BOOT = 'acun-ui-boot.js';

    public const RUNTIME = 'acun-ui.js';

    public const FILES = [self::BOOT, self::RUNTIME];

    public const PUBLISH_TAG = 'acun-ui-assets';

    /** Under public/, where vendor:publish --tag=acun-ui-assets copies dist/. */
    public const PUBLISHED_DIRECTORY = 'vendor/acun-ui';

    /** The container key of the tags other packages add to @acunUiScripts. */
    private const EXTENSIONS = 'acun-ui.assets.extensions';

    /** @var array<string, string> content hashes by path, size and modification time */
    private static array $hashes = [];

    private bool $scriptsRendered = false;

    /**
     * Adds the output of $tags to @acunUiScripts, after the runtime (the PRO package adds its
     * JS). Kept on the application, by key: every request of it prints them.
     *
     * @param  callable(array{nonce?: string|null}): string  $tags
     */
    public static function extend(string $key, callable $tags): void
    {
        $extensions = app()->bound(self::EXTENSIONS) ? app(self::EXTENSIONS) : [];
        app()->instance(self::EXTENSIONS, [...$extensions, $key => $tags]);
    }

    public static function distPath(string $file): string
    {
        return dirname(__DIR__, 2).'/dist/'.$file;
    }

    /** The route path, from config('acun-ui.assets.prefix'): acun-ui/{file} by default. */
    public static function routePath(string $file): string
    {
        $prefix = trim((string) (config('acun-ui.assets.prefix') ?? 'acun-ui'), '/');

        return ltrim($prefix.'/'.$file, '/');
    }

    /** The first 12 hex digits of the file's xxh128 hash ('missing' without the file). */
    public static function hash(string $path): string
    {
        if (! is_file($path)) {
            return 'missing';
        }

        $key = $path.'|'.filesize($path).'|'.filemtime($path);

        return self::$hashes[$key] ??= substr((string) hash_file('xxh128', $path), 0, 12);
    }

    /**
     * The URL of a file: config url, the published copy (when it is the same as the
     * package's), or the package route.
     */
    public static function url(string $file): string
    {
        $hash = self::hash(self::distPath($file));
        $configured = config('acun-ui.assets.url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/').'/'.$file.'?id='.$hash;
        }

        $published = public_path(self::PUBLISHED_DIRECTORY.'/'.$file);

        // An outdated published copy is skipped: the package's own file is always right.
        if (is_file($published) && self::hash($published) === $hash) {
            return asset(self::PUBLISHED_DIRECTORY.'/'.$file).'?id='.$hash;
        }

        return url(self::routePath($file)).'?id='.$hash;
    }

    /**
     * The blocking <script> of acun:layout.head. Not deferred: it has to run before the
     * first paint. wire:navigate leaves it alone (the same tag on every page) and reloads
     * the page when a new build changes its URL (data-navigate-track). Options: nonce
     * (default: Vite::cspNonce()).
     *
     * @param  array{nonce?: string|null}  $options
     */
    public function bootTag(array $options = []): string
    {
        return '<script src="'.e(self::url(self::BOOT)).'"'.self::nonceAttribute($options).' data-acun-ui-boot data-navigate-track></script>';
    }

    /**
     * @acunUiScripts: the runtime (and the PRO JS when installed); empty after the first
     * call of the request. Options: nonce (default: Vite::cspNonce()).
     *
     * @param  array{nonce?: string|null}  $options
     */
    public function scripts(array $options = []): string
    {
        if ($this->scriptsRendered) {
            return '';
        }

        $this->scriptsRendered = true;

        // defer: runs after the page is parsed and before Livewire starts Alpine on
        // DOMContentLoaded. The runtime stays idle when the Vite build has already set it up.
        $tag = '<script src="'.e(self::url(self::RUNTIME)).'"'.self::nonceAttribute($options).' defer data-acun-ui data-navigate-track data-navigate-once></script>';

        $extensions = app()->bound(self::EXTENSIONS) ? app(self::EXTENSIONS) : [];

        return $tag.implode('', array_map(fn (callable $tags): string => (string) $tags($options), $extensions));
    }

    /** @param  array{nonce?: string|null}  $options */
    private static function nonceAttribute(array $options): string
    {
        $nonce = $options['nonce'] ?? Vite::cspNonce();

        return is_string($nonce) && $nonce !== '' ? ' nonce="'.e($nonce).'"' : '';
    }
}
