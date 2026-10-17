<?php

namespace Acun\Ui\Support;

use Illuminate\Support\Facades\Log;

/**
 * Links that may come from a database, a tenant or a user (menu items, toast links):
 * only http, https, mailto, tel and relative (application) URLs are allowed.
 *
 * A protocol-relative URL ("//host/path") is not a relative one: the browser opens it on
 * that host with the page's scheme. It passes only when it points to the application's own
 * host, so "//evil.example" from stored data cannot pass as an in-app link.
 *
 * A URL that does not pass is never printed (the components print "#" instead), and the log
 * gets a warning once per request and URL, so a developer sees why a link went dead: another
 * site's address is written with https://.
 */
final class Url
{
    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    /** The request attribute that remembers the URLs already reported in this request. */
    private const REPORTED = 'acun-ui.unsafe-urls';

    /** javascript:, data:, vbscript: and any other scheme are not; neither is another host's //host. */
    public static function isSafe(string $url): bool
    {
        if (self::isProtocolRelative($url)) {
            return self::isOwnProtocolRelative($url) || self::report($url, true);
        }

        $scheme = self::scheme($url);

        return $scheme === null || in_array($scheme, self::SAFE_SCHEMES, true) || self::report($url, false);
    }

    /** The URL, or null when it is not safe (see isSafe()). */
    public static function safe(?string $url): ?string
    {
        return $url !== null && self::isSafe($url) ? $url : null;
    }

    /**
     * The URL scheme as a browser would read it: browsers drop tabs and line
     * breaks anywhere in a URL and control characters/spaces around it, so
     * "java\tscript:" is still a javascript: URL.
     */
    public static function scheme(string $url): ?string
    {
        return preg_match('/^([a-z][a-z0-9+.\-]*):/i', self::normalize($url), $matches) ? strtolower($matches[1]) : null;
    }

    /**
     * "//host/path". Browsers read a backslash as a slash in http(s) URLs and skip any
     * extra slashes, so "/\host", "\\host" and "///host" are protocol-relative too.
     */
    public static function isProtocolRelative(string $url): bool
    {
        return preg_match('~^[/\\\\]{2}~', self::normalize($url)) === 1;
    }

    /**
     * The URL stays on the application's origin: a relative URL ("/orders", "?page=2",
     * "#top"), or an absolute or protocol-relative one with the application's scheme and
     * host. For what may receive the CSRF token (a form's action). The application's origin
     * is the current request's, and the root of url() when that differs (URL::forceRootUrl()).
     */
    public static function isSameOrigin(string $url): bool
    {
        if (self::isProtocolRelative($url)) {
            return self::isOwnProtocolRelative($url);
        }

        $scheme = self::scheme($url);

        if ($scheme === null) {
            return true;
        }

        $origin = isset(self::DEFAULT_PORTS[$scheme]) ? self::origin(str_replace('\\', '/', self::normalize($url))) : null;

        return $origin !== null && in_array($origin, self::ownOrigins(), true);
    }

    /**
     * A developer warning in the log for a URL that is not printed, once per request and URL
     * (a menu renders on every page). The URL is cut to 200 characters. Always false: the URL
     * stays unsafe.
     */
    private static function report(string $url, bool $otherHost): false
    {
        if (! function_exists('app') || ! app()->bound('log')) {
            return false;
        }

        $request = app()->bound('request') ? app('request') : null;
        $reported = $request?->attributes->get(self::REPORTED, []) ?? [];

        if (in_array($url, $reported, true)) {
            return false;
        }

        $request?->attributes->set(self::REPORTED, [...$reported, $url]);

        Log::warning($otherHost
            ? 'Acun UI printed "#" for a protocol-relative link (//host) to another site: write it with https:// (only the application\'s own //host is kept).'
            : 'Acun UI printed "#" for a link with an unsafe scheme: only http, https, mailto, tel and relative links are kept.', ['url' => mb_strimwidth($url, 0, 201, '…')]);

        return false;
    }

    private static function normalize(string $url): string
    {
        return trim(str_replace(["\t", "\n", "\r"], '', $url), "\x00..\x20");
    }

    /** A protocol-relative URL keeps the page's scheme: it is read with each of the application's. */
    private static function isOwnProtocolRelative(string $url): bool
    {
        $rest = ltrim(str_replace('\\', '/', self::normalize($url)), '/');

        foreach (self::ownOrigins() as $origin) {
            if (self::origin($origin[0].'://'.$rest) === $origin) {
                return true;
            }
        }

        return false;
    }

    /**
     * [scheme, host[:port]] of an absolute URL, lower case, the port only when it is not the
     * scheme's default; null when no host can be read.
     *
     * @return array{0: string, 1: string}|null
     */
    private static function origin(string $url): ?array
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $port = isset($parts['port']) && $parts['port'] !== (self::DEFAULT_PORTS[$scheme] ?? null) ? ':'.$parts['port'] : '';

        return [$scheme, strtolower($parts['host']).$port];
    }

    /**
     * The application's origins: the current request's and url()'s root (they differ behind
     * a proxy or with URL::forceRootUrl()). None outside an application.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function ownOrigins(): array
    {
        if (! function_exists('app')) {
            return [];
        }

        $origins = [];

        if (app()->bound('request')) {
            $origins[] = self::origin(app('request')->getSchemeAndHttpHost());
        }

        if (app()->bound('url')) {
            $origins[] = self::origin(app('url')->to('/'));
        }

        return array_values(array_unique(array_filter($origins), SORT_REGULAR));
    }
}
