<?php

namespace Acun\Ui\Tests\Concerns;

/**
 * Js::from writes non-ASCII characters as \uXXXX before Laravel 13 (\\uXXXX inside its
 * JSON.parse('…') form) and as plain UTF-8 since.
 * Both are the same JavaScript string; decoding them lets an assertion read the same on every
 * supported Laravel version. Escaped ASCII (" for a quote) is left as it is.
 */
trait DecodesUnicodeEscapes
{
    protected static function decodeUnicodeEscapes(string $html): string
    {
        return (string) preg_replace_callback(
            '/(?:\\\\){1,2}u([0-9a-fA-F]{4})/',
            fn (array $match): string => hexdec($match[1]) >= 0x80 ? mb_chr(hexdec($match[1]), 'UTF-8') : $match[0],
            $html,
        );
    }
}
