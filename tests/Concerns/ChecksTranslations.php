<?php

namespace Acun\Ui\Tests\Concerns;

/**
 * The texts of a package are English keys with a Turkish translation in lang/tr.json: the keys its
 * views and classes use, the Turkish keys among them, and Turkish text outside __() in its views,
 * classes and scripts. Each package's TranslationsTest runs these over its own sources only.
 */
trait ChecksTranslations
{
    private const TRANSLATION_CALL = "~(?:__|trans|trans_choice|@lang)\\(\\s*('(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\")~u";

    // Turkish letters, and Turkish interface words without one.
    private const TURKISH_TEXT = '/[çğıöşüÇĞİÖŞÜ]|\b(?:Kapat|kapat|Kaydet|Ekle|Sil|Tamam|Evet|Temizle|Yenile|Bitir|Kopyala|Oynat|Duraklat|Filtreler|Kolonlar|gezin|hane|slayt|Seciniz|Formu)\b/u';

    /**
     * @param  list<string>  $directories  Relative to the package.
     * @param  list<string>  $allowed  File names that hold Turkish on purpose.
     * @return iterable<string>
     */
    private static function sourceFiles(string $package, array $directories, string $pattern = '/\.php$/', array $allowed = []): iterable
    {
        foreach ($directories as $directory) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$package/$directory", \FilesystemIterator::SKIP_DOTS)) as $file) {
                if (preg_match($pattern, $file->getFilename()) && ! in_array($file->getFilename(), $allowed, true)) {
                    yield $file->getPathname();
                }
            }
        }
    }

    private static function decodeLiteral(string $literal): string
    {
        return $literal[0] === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], substr($literal, 1, -1)) : stripcslashes(substr($literal, 1, -1));
    }

    /**
     * The keys the package's views and classes use that $translations does not have, and the Turkish keys.
     *
     * @param  array<string, string>  $translations
     * @return array{0: list<string>, 1: list<string>} [missing, Turkish], as "file: key"
     */
    private static function keyProblems(string $package, array $translations): array
    {
        $missing = [];
        $turkishKeys = [];

        foreach (self::sourceFiles($package, ['resources/views', 'src']) as $file) {
            // A key in a Blade comment (a usage example) is not used.
            preg_match_all(self::TRANSLATION_CALL, (string) preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($file)), $calls);

            foreach ($calls[1] as $literal) {
                $key = self::decodeLiteral($literal);

                if (! array_key_exists($key, $translations)) {
                    $missing[] = basename($file).': '.$key;
                }
                if (preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $key)) {
                    $turkishKeys[] = basename($file).': '.$key;
                }
            }
        }

        return [array_values(array_unique($missing)), array_values(array_unique($turkishKeys))];
    }

    /**
     * Lines of the package's views, classes and scripts with Turkish outside __() and comments.
     *
     * @param  list<string>  $allowed  File names that hold Turkish on purpose.
     * @return list<string>
     */
    private static function turkishOutsideTheTranslator(string $package, string $scripts, array $allowed = []): array
    {
        $found = [];

        foreach ([...self::sourceFiles($package, ['resources/views', 'src'], allowed: $allowed), ...self::sourceFiles($package, [$scripts], '/\.js$/', $allowed)] as $file) {
            $source = file_get_contents($file);
            // Comments explain the code; Turkish examples there are fine.
            $source = preg_replace_callback('/\{\{--.*?--\}\}|\/\*.*?\*\//s', fn ($m) => str_repeat("\n", substr_count($m[0], "\n")), $source);

            foreach (explode("\n", $source) as $number => $line) {
                $code = preg_replace(['~(^|\s)//.*$~', '~^\s*\*.*$~', self::TRANSLATION_CALL], '', $line);

                if (preg_match(self::TURKISH_TEXT, $code)) {
                    $found[] = basename($file).':'.($number + 1).': '.trim(mb_substr($code, 0, 120));
                }
            }
        }

        return $found;
    }

    /** @return array<string, string> */
    private static function translationsOf(string $package): array
    {
        return json_decode(file_get_contents("$package/lang/tr.json"), true, flags: JSON_THROW_ON_ERROR);
    }
}
