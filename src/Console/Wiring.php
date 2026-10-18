<?php

namespace Acun\Ui\Console;

use JsonException;
use RuntimeException;
use UnexpectedValueException;

/**
 * The text edits of php artisan acun:install: the lines Acun UI (and Acun Pro, when it is installed)
 * needs in package.json, the CSS entry and the JS entry. Each method takes a file's content and
 * returns it with the missing lines added, and every wanted line with whether it was missing.
 *
 * Nothing else changes: no line is removed, moved or reformatted, the line endings, the final newline
 * and the indentation stay as they are, and a line in a comment does not count as present. A second
 * call on the result adds nothing.
 */
final class Wiring
{
    /** The npm dependencies: the Composer copies under vendor/, so the CSS and JS match the Blade components. */
    public const LINKS = [
        'acun-ui' => ['@acunsoft/acun-ui', 'file:vendor/acunsoft/acun-ui'],
        'acun-pro' => ['@acunsoft/acun-pro', 'file:vendor/acunsoft/acun-pro'],
    ];

    /** The range the installation guide uses. */
    public const FORMS = ['@tailwindcss/forms', '^0.5.11'];

    public const CSS = [
        'tailwind' => "@import 'tailwindcss';",
        'forms' => "@plugin '@tailwindcss/forms';",
        'acun-ui' => "@import '@acunsoft/acun-ui';",
        'acun-pro' => "@import '@acunsoft/acun-pro';",
        'acun-ui-source' => "@source '../../vendor/acunsoft/acun-ui/resources/views';",
        'acun-pro-source' => "@source '../../vendor/acunsoft/acun-pro/resources/views/components/ui';",
    ];

    public const JS = [
        'acun-ui' => "import '@acunsoft/acun-ui';",
        'acun-pro' => "import '@acunsoft/acun-pro';",
    ];

    /** How a present line is recognised, on the code without comments (quotes of either kind). */
    private const CSS_PATTERNS = [
        // A tailwindcss import, also with options (source(none), layer(…)) or of its parts (tailwindcss/theme.css).
        'tailwind' => '~^\s*@import\s+([\'"])tailwindcss(?:/[^\'"]*)?\1~',
        'forms' => '~^\s*@plugin\s+([\'"])@tailwindcss/forms\1~',
        'acun-ui' => '~^\s*@import\s+([\'"])@acunsoft/acun-ui(?:/css/panel\.css)?\1~',
        'acun-pro' => '~^\s*@import\s+([\'"])@acunsoft/acun-pro(?:/css/panel\.css)?\1~',
        // The package's views, or a folder or glob inside them.
        'acun-ui-source' => '~^\s*@source\s+([\'"])[^\'"]*vendor/acunsoft/acun-ui/resources/views[^\'"]*\1~',
        'acun-pro-source' => '~^\s*@source\s+([\'"])[^\'"]*vendor/acunsoft/acun-pro/resources/views[^\'"]*\1~',
    ];

    /**
     * package.json: the npm links of the packages in dependencies (sorted in, as npm sorts) and
     * @tailwindcss/forms in devDependencies; a missing section is added at the end. A package that is
     * already there (in either section) is left as it is; for a link with another value, "found" holds it.
     *
     * @return array{content: string, items: list<array{line: string, added: bool, pro: bool, package: string, version: string, found: ?string}>}
     *
     * @throws JsonException when the file is not JSON
     * @throws UnexpectedValueException when it is not an object, or a section is not one
     */
    public static function packageJson(string $content, bool $pro): array
    {
        $data = self::decode($content);
        $items = [];

        $wanted = [
            ['dependencies', ...self::LINKS['acun-ui'], true, false],
            ...($pro ? [['dependencies', ...self::LINKS['acun-pro'], true, true]] : []),
            ['devDependencies', ...self::FORMS, false, false],
        ];

        foreach ($wanted as [$section, $name, $version, $link, $isPro]) {
            $where = array_key_exists($name, $data['dependencies'] ?? []) ? 'dependencies' : (array_key_exists($name, $data['devDependencies'] ?? []) ? 'devDependencies' : null);

            if ($where !== null) {
                // As it is in the file.
                $found = is_string($data[$where][$name]) ? $data[$where][$name] : (string) json_encode($data[$where][$name]);
                $line = self::encode($name).': '.self::encode($found).' ('.$where.')';
                $items[] = ['line' => $line, 'added' => false, 'pro' => $isPro, 'package' => $name, 'version' => $version, 'found' => $link && ! self::sameLink($found, $version) ? $found : null];

                continue;
            }

            $line = self::encode($name).': '.self::encode($version).' ('.$section.')';
            $content = self::addMember($content, $section, $name, $version);
            $data[$section][$name] = $version;
            $items[] = ['line' => $line, 'added' => true, 'pro' => $isPro, 'package' => $name, 'version' => $version, 'found' => null];
        }

        // The edit must decode to the same data plus the additions; otherwise nothing is written.
        if (self::decode($content) != $data) {
            throw new RuntimeException('package.json could not be edited safely.');
        }

        return ['content' => $content, 'items' => $items];
    }

    /**
     * The CSS entry, in this order: @import 'tailwindcss'; (at the top), @plugin '@tailwindcss/forms';
     * and the kit's @import right after it, then the @source of the kit's views: before the first
     * @source of the file, or after the @import lines. fontSans is the line of an @theme block's
     * --font-sans in the result (it replaces the kit's fonts; it is never changed here), or null.
     *
     * @return array{content: string, items: list<array{line: string, added: bool, pro: bool}>, fontSans: ?int}
     */
    public static function css(string $content, bool $pro): array
    {
        [$lines, $eol, $final] = self::lines($content);
        $items = [];

        $keys = array_values(array_filter(array_keys(self::CSS), fn (string $key): bool => $pro || ! str_starts_with($key, 'acun-pro')));

        foreach ($keys as $key) {
            $code = self::code($lines, js: false);
            $line = self::CSS[$key];
            $present = self::find($code, self::CSS_PATTERNS[$key]) !== null;

            if (! $present) {
                // After the line it follows (after the whole statement: @plugin may hold a { } block).
                $insert = match ($key) {
                    'tailwind' => self::top($code),
                    'forms' => self::statementEnd($code, self::findLast($code, self::CSS_PATTERNS['tailwind'])) + 1,
                    'acun-ui' => self::statementEnd($code, self::find($code, self::CSS_PATTERNS['forms'])) + 1,
                    'acun-pro' => self::statementEnd($code, self::find($code, self::CSS_PATTERNS['acun-ui'])) + 1,
                    'acun-pro-source' => self::statementEnd($code, self::find($code, self::CSS_PATTERNS['acun-ui-source'])) + 1,
                    'acun-ui-source' => null,
                };

                if ($insert !== null) {
                    array_splice($lines, $insert, 0, [$line]);
                } else {
                    $lines = self::insertSource($lines, $code, $line);
                }
            }

            $items[] = ['line' => $line, 'added' => ! $present, 'pro' => str_starts_with($key, 'acun-pro')];
        }

        $content = self::join($lines, $eol, $final);

        return ['content' => $content, 'items' => $items, 'fontSans' => self::fontSans($content)];
    }

    /**
     * The JS entry: import '@acunsoft/acun-ui'; after the last import statement (at the top when there
     * is none), then Acun Pro's the same way, so right after it. Any import of the package counts
     * (import { modal } from '@acunsoft/acun-ui', a dynamic import(), require()).
     *
     * @return array{content: string, items: list<array{line: string, added: bool, pro: bool}>}
     */
    public static function js(string $content, bool $pro): array
    {
        [$lines, $eol, $final] = self::lines($content);
        $items = [];

        foreach ($pro ? ['acun-ui', 'acun-pro'] : ['acun-ui'] as $key) {
            $code = self::code($lines, js: true);
            $line = self::JS[$key];
            $present = self::importLine($code, self::LINKS[$key][0]) !== null;

            if (! $present) {
                if (($last = self::lastImport($code)) !== null) {
                    array_splice($lines, $last + 1, 0, [$line]);
                } else {
                    // No imports: on top of the code (after a leading comment), apart from it.
                    $at = self::top($code);
                    $next = $lines[$at] ?? null;
                    array_splice($lines, $at, 0, $next !== null && trim($next) !== '' ? [$line, ''] : [$line]);
                }
            }

            $items[] = ['line' => $line, 'added' => ! $present, 'pro' => $key === 'acun-pro'];
        }

        return ['content' => self::join($lines, $eol, $final), 'items' => $items];
    }

    /** "file:vendor/acunsoft/acun-ui", also written "file:./vendor/acunsoft/acun-ui/". */
    private static function sameLink(string $found, string $expected): bool
    {
        $normalise = fn (string $link): string => rtrim(preg_replace('~^file:(?:\./)?~', 'file:', trim($link)), '/');

        return $normalise($found) === $normalise($expected);
    }

    /** @return array<string, mixed> */
    private static function decode(string $content): array
    {
        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data) || ! str_starts_with(ltrim($content), '{')) {
            throw new UnexpectedValueException('package.json is not a JSON object.');
        }

        foreach (['dependencies', 'devDependencies'] as $section) {
            if (isset($data[$section]) && ! is_array($data[$section])) {
                throw new UnexpectedValueException("\"{$section}\" in package.json is not an object.");
            }
        }

        return $data;
    }

    private static function encode(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Adds "name": "version" to a top-level object of the JSON text (the section is added when missing). */
    private static function addMember(string $json, string $section, string $name, string $version): string
    {
        $eol = str_contains($json, "\r\n") ? "\r\n" : "\n";
        $root = strpos($json, '{');
        $members = self::scanObject($json, $root)['members'];
        $unit = self::indentUnit($json, $members);

        if (isset($members[$section])) {
            return self::insertMember($json, $members[$section]['valueStart'], $name, self::encode($version), true, $unit, $eol);
        }

        $object = fn (string $indent): string => '{'.$eol.$indent.$unit.self::encode($name).': '.self::encode($version).$eol.$indent.'}';

        return self::insertMember($json, $root, $section, $object, false, $unit, $eol);
    }

    /**
     * Inserts a member into the JSON object that opens at $open: before the first key that sorts after
     * it ($sorted), else after the last member, in the object's own layout (one member per line, or all
     * on one line).
     *
     * @param  string|\Closure(string): string  $value  the value's text, or a function of the member's indentation
     */
    private static function insertMember(string $json, int $open, string $key, string|\Closure $value, bool $sorted, string $unit, string $eol): string
    {
        ['close' => $close, 'members' => $members] = self::scanObject($json, $open);
        $lineIndent = self::lineIndent($json, $open);
        $first = $members === [] ? null : reset($members);
        $multiline = $first === null || str_contains(substr($json, $open, $first['keyStart'] - $open), "\n");
        $indent = $first === null ? $lineIndent.$unit : ($multiline ? self::lineIndent($json, $first['keyStart']) : '');
        $member = self::encode($key).': '.($value instanceof \Closure ? $value($indent) : $value);

        if ($first === null) {
            return substr($json, 0, $open).'{'.$eol.$indent.$member.$eol.$lineIndent.'}'.substr($json, $close + 1);
        }

        if ($sorted) {
            foreach ($members as $name => $position) {
                if (strcmp((string) $name, $key) > 0) {
                    return substr_replace($json, $multiline ? $member.','.$eol.$indent : $member.', ', $position['keyStart'], 0);
                }
            }
        }

        $last = end($members);

        return substr_replace($json, $multiline ? ','.$eol.$indent.$member : ', '.$member, $last['valueEnd'], 0);
    }

    /**
     * The members of the JSON object that opens at $open, with their offsets.
     *
     * @return array{close: int, members: array<string, array{keyStart: int, valueStart: int, valueEnd: int}>}
     */
    private static function scanObject(string $json, int $open): array
    {
        $members = [];
        $length = strlen($json);
        $i = $open + 1;

        while ($i < $length) {
            $i = self::skipSpace($json, $i);

            if ($json[$i] === '}') {
                return ['close' => $i, 'members' => $members];
            }

            if ($json[$i] === ',') {
                $i++;

                continue;
            }

            $keyStart = $i;
            $keyEnd = self::skipString($json, $i);
            $key = (string) json_decode(substr($json, $keyStart, $keyEnd - $keyStart));
            $valueStart = self::skipSpace($json, self::skipSpace($json, $keyEnd) + 1);
            $valueEnd = self::skipValue($json, $valueStart);
            $members[$key] = ['keyStart' => $keyStart, 'valueStart' => $valueStart, 'valueEnd' => $valueEnd];
            $i = $valueEnd;
        }

        throw new RuntimeException('package.json could not be read.');
    }

    private static function skipSpace(string $json, int $i): int
    {
        return $i + strspn($json, " \t\r\n", $i);
    }

    /** The offset after the string that starts at $i. */
    private static function skipString(string $json, int $i): int
    {
        for ($i++, $length = strlen($json); $i < $length; $i++) {
            if ($json[$i] === '\\') {
                $i++;
            } elseif ($json[$i] === '"') {
                return $i + 1;
            }
        }

        return $length;
    }

    /** The offset after the value that starts at $i. */
    private static function skipValue(string $json, int $i): int
    {
        if ($json[$i] === '"') {
            return self::skipString($json, $i);
        }

        if ($json[$i] === '{' || $json[$i] === '[') {
            for ($depth = 0, $length = strlen($json); $i < $length; $i++) {
                if ($json[$i] === '"') {
                    $i = self::skipString($json, $i) - 1;
                } elseif ($json[$i] === '{' || $json[$i] === '[') {
                    $depth++;
                } elseif (($json[$i] === '}' || $json[$i] === ']') && --$depth === 0) {
                    return $i + 1;
                }
            }

            return $length;
        }

        return $i + strcspn($json, ",}] \t\r\n", $i);
    }

    /** The whitespace at the start of the line that holds $offset. */
    private static function lineIndent(string $json, int $offset): string
    {
        $start = strrpos(substr($json, 0, $offset), "\n");
        $start = $start === false ? 0 : $start + 1;

        return substr($json, $start, strspn($json, " \t", $start));
    }

    /**
     * One level of indentation: the first top-level member's (four spaces when it has none).
     *
     * @param  array<string, array{keyStart: int}>  $members
     */
    private static function indentUnit(string $json, array $members): string
    {
        $first = $members === [] ? null : reset($members);
        $indent = $first === null ? '' : self::lineIndent($json, $first['keyStart']);

        return $indent !== '' ? $indent : '    ';
    }

    /**
     * The lines of a file, its line ending and whether it ends with one.
     *
     * @return array{0: list<string>, 1: string, 2: bool}
     */
    private static function lines(string $content): array
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $final = $content === '' || str_ends_with($content, "\n");
        $lines = $content === '' ? [] : explode($eol, $content);

        if ($final && $lines !== []) {
            array_pop($lines);
        }

        return [$lines, $eol, $final];
    }

    /** @param list<string> $lines */
    private static function join(array $lines, string $eol, bool $final): string
    {
        return implode($eol, $lines).($final && $lines !== [] ? $eol : '');
    }

    /**
     * The lines with comments blanked out (newlines kept, so the indexes match): CSS's block comments,
     * also JS's line comments; text in quotes (and JS template literals) is not a comment.
     *
     * @param  list<string>  $lines
     * @return list<string>
     */
    private static function code(array $lines, bool $js): array
    {
        $text = implode("\n", $lines);
        $out = '';
        $quote = null;

        for ($i = 0, $length = strlen($text); $i < $length; $i++) {
            $char = $text[$i];
            $next = $text[$i + 1] ?? '';

            if ($quote !== null) {
                $out .= $char;

                if ($char === '\\' && $i + 1 < $length) {
                    $out .= $next;
                    $i++;
                } elseif ($char === $quote || ($char === "\n" && $quote !== '`')) {
                    $quote = null;
                }
            } elseif ($char === '/' && $next === '*') {
                $end = strpos($text, '*/', $i + 2);
                $end = $end === false ? $length : $end + 2;
                $out .= preg_replace('/[^\n]/', ' ', substr($text, $i, $end - $i));
                $i = $end - 1;
            } elseif ($js && $char === '/' && $next === '/') {
                $end = strpos($text, "\n", $i);
                $end = $end === false ? $length : $end;
                $out .= str_repeat(' ', $end - $i);
                $i = $end - 1;
            } else {
                if ($char === "'" || $char === '"' || ($js && $char === '`')) {
                    $quote = $char;
                }

                $out .= $char;
            }
        }

        return explode("\n", $out);
    }

    /** @param list<string> $code */
    private static function find(array $code, string $pattern): ?int
    {
        foreach ($code as $index => $line) {
            if (preg_match($pattern, $line)) {
                return $index;
            }
        }

        return null;
    }

    /** @param list<string> $code */
    private static function findLast(array $code, string $pattern): ?int
    {
        $found = null;

        foreach ($code as $index => $line) {
            if (preg_match($pattern, $line)) {
                $found = $index;
            }
        }

        return $found;
    }

    /**
     * Where a line goes on top: at the first line of code (after a leading comment), after @charset.
     *
     * @param  list<string>  $code
     */
    private static function top(array $code): int
    {
        foreach ($code as $index => $line) {
            if (trim($line) !== '') {
                return preg_match('~^\s*@charset\b~', $line) ? $index + 1 : $index;
            }
        }

        return count($code) === 1 && $code[0] === '' ? 0 : count($code);
    }

    /**
     * The @source of the kit's views: before the first @source of the file (the kit's folders come
     * first), else after the last @import or @plugin line, set apart by blank lines.
     *
     * @param  list<string>  $lines
     * @param  list<string>  $code
     * @return list<string>
     */
    private static function insertSource(array $lines, array $code, string $line): array
    {
        $source = self::find($code, '~^\s*@source\s+[\'"]~');

        if ($source !== null) {
            array_splice($lines, $source, 0, [$line]);

            return $lines;
        }

        // The last of the leading @import, @plugin, @config and @charset statements (a block ends the search).
        $after = null;
        for ($index = 0, $count = count($code); $index < $count; $index++) {
            if (preg_match('~^\s*@(?:import|plugin|config|charset)\b~', $code[$index])) {
                $after = $index = self::statementEnd($code, $index);
            } elseif (str_contains($code[$index], '{')) {
                break;
            }
        }

        $at = ($after ?? -1) + 1;
        $next = $lines[$at] ?? null;

        if ($next === null) {
            array_splice($lines, $at, 0, ['', $line]);
        } elseif (trim($next) === '') {
            $following = $lines[$at + 1] ?? null;
            array_splice($lines, $at + 1, 0, $following !== null && trim($following) !== '' ? [$line, ''] : [$line]);
        } else {
            array_splice($lines, $at, 0, ['', $line, '']);
        }

        return $lines;
    }

    /**
     * The line where the statement that starts on line $index ends: the same line, or the line that
     * closes the { } block it opens.
     *
     * @param  list<string>  $code
     */
    private static function statementEnd(array $code, int $index): int
    {
        $depth = 0;

        for ($end = $index, $count = count($code); $end < $count; $end++) {
            $depth += substr_count($code[$end], '{') - substr_count($code[$end], '}');

            if ($depth <= 0) {
                return $end;
            }
        }

        return $index;
    }

    /** The 1-based line of a CSS file's --font-sans inside an @theme block, or null. */
    public static function fontSans(string $css): ?int
    {
        $code = implode("\n", self::code(self::lines($css)[0], js: false));
        $offset = 0;

        while (preg_match('~@theme\b[^{;]*\{~', $code, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $open = $match[0][1] + strlen($match[0][0]) - 1;
            $close = self::skipValue($code, $open);
            $block = substr($code, $open, $close - $open);

            if (preg_match('~--font-sans\s*:~', $block, $declaration, PREG_OFFSET_CAPTURE)) {
                return substr_count(substr($code, 0, $open + $declaration[0][1]), "\n") + 1;
            }

            $offset = $close;
        }

        return null;
    }

    /**
     * The line that imports the package (any form), or null.
     *
     * @param  list<string>  $code
     */
    private static function importLine(array $code, string $package): ?int
    {
        $pattern = '~(?:\bfrom\s*|\bimport\s*\(?\s*|\brequire\s*\(\s*)([\'"])'.preg_quote($package, '~').'\1~';

        return self::find($code, $pattern);
    }

    /**
     * The line where the last static import statement ends (a statement may span lines), or null.
     *
     * @param  list<string>  $code
     */
    private static function lastImport(array $code): ?int
    {
        $last = null;

        for ($index = 0, $count = count($code); $index < $count; $index++) {
            // A static import; not import(…) or import.meta.
            if (! preg_match('~^\s*import\b(?!\s*[(.])~', $code[$index])) {
                continue;
            }

            for ($end = $index; $end < $count; $end++) {
                if (preg_match('~(?:\bfrom\s*|^\s*import\s*)([\'"])[^\'"]+\1~', $code[$end])) {
                    break;
                }
            }

            if ($end === $count) {
                break;
            }

            $last = $index = $end;
        }

        return $last;
    }
}
