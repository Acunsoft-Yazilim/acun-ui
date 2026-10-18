<?php

namespace Acun\Ui\Support;

use InvalidArgumentException;

/**
 * WCAG 2 contrast of #rrggbb colours, with the maths of js/appearance/theme.js (luminance(),
 * contrast()): text needs 4.5:1, a graphic or a frame (a tick on a swatch, a focus ring) 3:1.
 * tests/js/theme-switcher.test.mjs checks that the two agree.
 *
 *   Contrast::ratio('#ffffff', '#9f6b13');   // 4.58
 *   Contrast::whiteReads('#fcaa1e');          // false: a dark tick reads better on it
 */
final class Contrast
{
    /** The weights of the red, green and blue channels in the relative luminance. */
    private const WEIGHTS = [0.2126, 0.7152, 0.0722];

    /** WCAG relative luminance of #rrggbb: 0 (black) to 1 (white). */
    public static function luminance(string $hex): float
    {
        if (! preg_match('/^#[0-9a-f]{6}\z/i', $hex)) {
            throw new InvalidArgumentException("Not a #rrggbb colour: [{$hex}].");
        }

        $sum = 0.0;

        foreach (str_split(substr($hex, 1), 2) as $i => $channel) {
            $c = hexdec($channel) / 255;
            $sum += self::WEIGHTS[$i] * ($c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4);
        }

        return $sum;
    }

    /** The contrast ratio of two #rrggbb colours: 1 (the same) to 21 (black on white). */
    public static function ratio(string $a, string $b): float
    {
        [$light, $dark] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($light + 0.05) / ($dark + 0.05);
    }

    /**
     * Whether white stands out at least $minimum:1 on the colour: a tick or an icon on a swatch is a
     * graphic (3:1); text on a fill needs 4.5:1.
     */
    public static function whiteReads(string $hex, float $minimum = 3.0): bool
    {
        return self::ratio('#ffffff', $hex) >= $minimum;
    }
}
