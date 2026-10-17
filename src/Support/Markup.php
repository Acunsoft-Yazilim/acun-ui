<?php

namespace Acun\Ui\Support;

/**
 * Tidy page source: one tag per line, one indentation, no blank lines left behind by
 * Blade comments and conditions (acun:layout.head).
 */
final class Markup
{
    /**
     * Every tag on its own line, trimmed and indented with $indent, except the first: it
     * follows the indentation of the line the output is printed on. The content of
     * <script> and <style> elements is left exactly as it is.
     */
    public static function lines(string $html, string $indent = '    '): string
    {
        $lines = [];

        foreach (preg_split('~(<(?:script|style)\b[^>]*>.*?</(?:script|style)>)~si', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $index => $part) {
            // Odd parts are the <script>/<style> elements.
            if (preg_match('~^<(?:script|style)\b~i', $part)) {
                $lines[] = trim($part);

                continue;
            }

            // Elsewhere a new line before every opening tag, also between tags printed side by side (@vite).
            foreach (preg_split('~\R|(?<=>)\s*(?=<[^/])~', $part) as $line) {
                if (($line = trim($line)) !== '') {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n".$indent, $lines);
    }
}
