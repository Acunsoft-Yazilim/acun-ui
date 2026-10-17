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

        $parts = self::split($template);

        foreach ($parts as $index => $part) {
            // Odd indexes are the captured blocks.
            if ($index % 2 === 0) {
                $parts[$index] = self::rename($part);
            }
        }

        return implode('', $parts);
    }

    /**
     * The component names the template's tags use, <acun:NAME> and <x-acun::NAME> (opening and
     * self-closing tags; not slots), outside Blade comments and the blocks compile() leaves alone.
     *
     * @return list<string>
     */
    public static function names(string $template): array
    {
        if (! str_contains($template, self::PREFIX.':')) {
            return [];
        }

        $names = [];

        foreach (self::split((string) preg_replace('/\{\{--.*?--\}\}/s', '', $template)) as $index => $part) {
            if ($index % 2 === 0 && preg_match_all('/<\s*(?:x-'.self::PREFIX.'::|'.self::PREFIX.':)([\w\-.]+)/', $part, $matches)) {
                array_push($names, ...array_filter($matches[1], fn (string $name): bool => $name !== 'slot'));
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The template cut around @verbatim blocks, @php … @endphp blocks and <?php ?> sections: the
     * blocks are the odd items.
     *
     * @return list<string>
     */
    private static function split(string $template): array
    {
        // @php followed by a space or line break opens a block; @php(...) is a single expression.
        return preg_split('/(@verbatim.*?@endverbatim|@php(?!\s*\()\s.*?@endphp|<\?php.*?\?>)/s', $template, -1, PREG_SPLIT_DELIM_CAPTURE);
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
