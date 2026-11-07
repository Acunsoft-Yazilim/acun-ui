<?php

namespace Acun\Ui\Support;

/**
 * The page container's width (ac-container, <acun:container>, the panel's .panel-container): one of
 * the named widths, or a CSS length of the application's own.
 *
 *   default   1280px (the storefront's default)
 *   wide      1440px (boxed, the 2.x name, is the same)
 *   wider     1600px
 *   full      no limit: the whole content area (the panel's default)
 *   a length  '1520px', '90rem', '85vw' …
 *
 * The layouts write the effective width on <html>: data-content-width="wide" for a name, and
 * data-content-width="custom" style="--ac-container-width: 1520px" for a length; full writes nothing
 * (no attribute is the whole width). Anything else falls back to default.
 */
final class ContentWidth
{
    /** The named widths and the token each one reads (css/core.css). */
    public const WIDTHS = [
        'default' => 'var(--ac-container-default)',
        'wide' => 'var(--ac-container-wide)',
        'wider' => 'var(--ac-container-wider)',
        'full' => 'none',
    ];

    /** Earlier names: boxed (2.x, at most 1440px) is wide. */
    public const ALIASES = ['boxed' => 'wide'];

    /** A positive number with a unit: 1520px, 90rem, 85vw, 72ch, 95%. */
    private const LENGTH = '/^(?:\d{1,5}(?:\.\d{1,4})?|\.\d{1,4})(?:px|rem|em|vw|ch|%)$/';

    /**
     * The value as the container takes it: a name, an alias's name or a length; null for nothing
     * (null or an empty string), and default for anything else.
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        $value = is_string($value) ? strtolower(trim($value)) : $value;

        if (! is_string($value)) {
            return 'default';
        }

        $value = self::ALIASES[$value] ?? $value;

        return isset(self::WIDTHS[$value]) || self::isLength($value) ? $value : 'default';
    }

    /** Whether the value is a CSS length the container accepts (1520px, 90rem). */
    public static function isLength(string $value): bool
    {
        return preg_match(self::LENGTH, $value) === 1 && (float) $value > 0;
    }

    /**
     * The first value given (in the order of precedence: a forced value, the layout's prop, the
     * package's default, the application's config), normalized; full when none is.
     */
    public static function resolve(mixed ...$values): string
    {
        foreach ($values as $value) {
            $width = self::normalize($value);

            if ($width !== null) {
                return $width;
            }
        }

        return 'full';
    }

    /** The CSS value of --ac-container-width for a width (null and nothing: full). */
    public static function css(?string $width): string
    {
        $width = self::normalize($width) ?? 'full';

        return self::WIDTHS[$width] ?? $width;
    }

    /**
     * The attributes of <html> for the width, with a leading space: ' data-content-width="wide"',
     * ' data-content-width="custom" style="--ac-container-width: 1520px"', or '' for full.
     */
    public static function rootAttributes(?string $width): string
    {
        $width = self::normalize($width) ?? 'full';

        return match (true) {
            $width === 'full' => '',
            isset(self::WIDTHS[$width]) => ' data-content-width="'.$width.'"',
            default => ' data-content-width="custom" style="--ac-container-width: '.e($width).'"',
        };
    }
}
