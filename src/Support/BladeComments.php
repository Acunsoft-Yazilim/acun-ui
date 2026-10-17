<?php

namespace Acun\Ui\Support;

/**
 * A line that holds only a Blade comment ({{-- … --}}, one or more lines) is removed with
 * its line break before Blade compiles the template. Blade drops the comment but kept the
 * line, so every documented component left blank lines in the page source. Only
 * whitespace changes; @verbatim blocks are left alone.
 */
final class BladeComments
{
    public static function strip(string $template): string
    {
        if (! str_contains($template, '{{--')) {
            return $template;
        }

        $parts = preg_split('/(@verbatim.*?@endverbatim)/s', $template, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $index => $part) {
            // Odd indexes are the captured @verbatim blocks.
            if ($index % 2 === 0) {
                // Atomic: a comment followed by content on its line stays (it must not run on to the next comment).
                $parts[$index] = (string) preg_replace('/^[ \t]*(?>\{\{--.*?--\}\})[ \t]*(?:\r\n|\n|$)/ms', '', $part);
            }
        }

        return implode('', $parts);
    }
}
