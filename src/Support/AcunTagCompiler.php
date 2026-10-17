<?php

namespace Acun\Ui\Support;

/**
 * The short tag syntax: <acun:button> is <x-acun::button> (the kit's own component
 * namespace), <acun:accordion.heading> is <x-acun::accordion.heading>, <acun:slot:footer>
 * is <x-slot:footer>. Only the tag name is rewritten, before Blade compiles the template;
 * attributes stay as written. @verbatim blocks, @php … @endphp blocks and <?php ?> sections
 * are left alone (they show code or hold PHP strings).
 */
final class AcunTagCompiler
{
    public const PREFIX = 'acun';

    public static function compile(string $template): string
    {
        if (! str_contains($template, '<'.self::PREFIX.':') && ! str_contains($template, '</'.self::PREFIX.':')) {
            return $template;
        }

        // @php followed by a space or line break opens a block; @php(...) is a single expression.
        $parts = preg_split('/(@verbatim.*?@endverbatim|@php(?!\s*\()\s.*?@endphp|<\?php.*?\?>)/s', $template, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $index => $part) {
            // Odd indexes are the captured blocks.
            if ($index % 2 === 0) {
                $parts[$index] = self::rename($part);
            }
        }

        return implode('', $parts);
    }

    private static function rename(string $template): string
    {
        return (string) preg_replace_callback(
            '/<(\/?)\s*'.self::PREFIX.':([\w\-.:]+)/',
            function (array $match): string {
                [, $slash, $name] = $match;

                // <acun:slot name="x"> and <acun:slot:x> are Blade's own slot tags.
                if ($name === 'slot' || str_starts_with($name, 'slot:')) {
                    return '<'.$slash.'x-'.$name;
                }

                return '<'.$slash.'x-acun::'.$name;
            },
            $template,
        );
    }
}
