<?php

namespace Acun\Ui\Support;

use Illuminate\Support\HtmlString;

/**
 * Small inline SVG flags (3:2) for acun:flag and acun:locale-switcher: no image files, no
 * requests. Country codes (ISO 3166-1 alpha-2, lowercase); an unknown code gives null.
 */
final class Flags
{
    private const SVG = [
        'tr' => '<rect width="30" height="20" fill="#E30A17"/><circle cx="11.25" cy="10" r="5" fill="#fff"/><circle cx="12.5" cy="10" r="4" fill="#E30A17"/><polygon fill="#fff" points="14.85,10 16.58,9.44 16.58,7.62 17.65,9.09 19.37,8.53 18.31,10 19.37,11.47 17.65,10.91 16.58,12.38 16.58,10.56"/>',
        'gb' => '<rect width="30" height="20" fill="#012169"/><path d="M0 0l30 20M30 0L0 20" stroke="#fff" stroke-width="4"/><path d="M0 0l30 20M30 0L0 20" stroke="#C8102E" stroke-width="2.6" clip-path="url(#{id})"/><path d="M15 0v20M0 10h30" stroke="#fff" stroke-width="6.5"/><path d="M15 0v20M0 10h30" stroke="#C8102E" stroke-width="4"/><clipPath id="{id}"><path d="M15 10h15v10zM15 10v10H0zM15 10H0V0zM15 10V0h15z"/></clipPath>',
        'us' => '<rect width="30" height="20" fill="#fff"/><path d="M0 0h30M0 3.08h30M0 6.15h30M0 9.23h30M0 12.31h30M0 15.38h30M0 18.46h30" stroke="#B22234" stroke-width="1.54" transform="translate(0 .77)"/><rect width="12" height="10.77" fill="#3C3B6E"/><g fill="#fff"><circle cx="2" cy="2" r=".6"/><circle cx="6" cy="2" r=".6"/><circle cx="10" cy="2" r=".6"/><circle cx="4" cy="4.4" r=".6"/><circle cx="8" cy="4.4" r=".6"/><circle cx="2" cy="6.8" r=".6"/><circle cx="6" cy="6.8" r=".6"/><circle cx="10" cy="6.8" r=".6"/><circle cx="4" cy="9.1" r=".6"/><circle cx="8" cy="9.1" r=".6"/></g>',
        'de' => '<rect width="30" height="20" fill="#FFCE00"/><rect width="30" height="13.33" fill="#DD0000"/><rect width="30" height="6.67" fill="#000"/>',
        'fr' => '<rect width="30" height="20" fill="#EF4135"/><rect width="20" height="20" fill="#fff"/><rect width="10" height="20" fill="#0055A4"/>',
        'it' => '<rect width="30" height="20" fill="#CE2B37"/><rect width="20" height="20" fill="#fff"/><rect width="10" height="20" fill="#009246"/>',
        'es' => '<rect width="30" height="20" fill="#AA151B"/><rect y="5" width="30" height="10" fill="#F1BF00"/>',
        'nl' => '<rect width="30" height="20" fill="#21468B"/><rect width="30" height="13.33" fill="#fff"/><rect width="30" height="6.67" fill="#AE1C28"/>',
        'ru' => '<rect width="30" height="20" fill="#D52B1E"/><rect width="30" height="13.33" fill="#0039A6"/><rect width="30" height="6.67" fill="#fff"/>',
    ];

    private static int $instance = 0;

    public static function exists(?string $code): bool
    {
        return $code !== null && isset(self::SVG[strtolower($code)]);
    }

    /**
     * The flag as an <svg> (decorative: aria-hidden; the text next to it names the language).
     * $class: the classes come from the view (Tailwind scans the views, not this file).
     */
    public static function svg(?string $code, string $class = ''): ?HtmlString
    {
        if (! self::exists($code)) {
            return null;
        }

        // The clip path of the UK flag needs an id of its own for each flag on the page.
        $body = str_replace('{id}', 'acun-flag-'.strtolower($code).'-'.++self::$instance, self::SVG[strtolower($code)]);

        return new HtmlString('<svg class="'.e($class).'" viewBox="0 0 30 20" aria-hidden="true" focusable="false">'.$body.'</svg>');
    }
}
