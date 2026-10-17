<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;

/**
 * The packages' texts are English keys with a Turkish translation in lang/tr.json:
 * every key used has one, no key is Turkish, and no Turkish text sits in a view or
 * class outside __().
 */
final class TranslationsTest extends TestCase
{
    private const PACKAGES = [__DIR__.'/../..', __DIR__.'/../../../acun-ui-pro'];

    private const CALL = "~(?:__|trans|trans_choice|@lang)\\(\\s*('(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\")~u";

    // Turkish letters, and Turkish interface words without one.
    private const TURKISH = '/[çğıöşüÇĞİÖŞÜ]|\b(?:Kapat|kapat|Kaydet|Ekle|Sil|Tamam|Evet|Temizle|Yenile|Bitir|Kopyala|Oynat|Duraklat|Filtreler|Kolonlar|gezin|hane|slayt|Seciniz|Formu)\b/u';

    /** Files that hold Turkish on purpose: the search's letter folding and the language names. */
    private const ALLOWED = ['combobox.js'];

    /** @return iterable<string> */
    private function files(string $package, array $directories, string $pattern = '/\.php$/'): iterable
    {
        foreach ($directories as $directory) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$package/$directory", \FilesystemIterator::SKIP_DOTS)) as $file) {
                if (preg_match($pattern, $file->getFilename()) && ! in_array($file->getFilename(), self::ALLOWED, true)) {
                    yield $file->getPathname();
                }
            }
        }
    }

    private static function decode(string $literal): string
    {
        return $literal[0] === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], substr($literal, 1, -1)) : stripcslashes(substr($literal, 1, -1));
    }

    public function test_every_key_is_english_and_has_a_turkish_translation(): void
    {
        $missing = [];
        $turkishKeys = [];

        foreach (self::PACKAGES as $package) {
            $translations = json_decode(file_get_contents("$package/lang/tr.json"), true, flags: JSON_THROW_ON_ERROR);

            foreach ($this->files($package, ['resources/views', 'src']) as $file) {
                preg_match_all(self::CALL, file_get_contents($file), $calls);

                foreach ($calls[1] as $literal) {
                    $key = self::decode($literal);

                    if (! array_key_exists($key, $translations)) {
                        $missing[] = basename($file).': '.$key;
                    }
                    if (preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $key)) {
                        $turkishKeys[] = basename($file).': '.$key;
                    }
                }
            }
        }

        self::assertSame([], array_values(array_unique($missing)), 'Keys without a Turkish translation in lang/tr.json.');
        self::assertSame([], array_values(array_unique($turkishKeys)), 'Keys are the English text.');
    }

    public function test_no_turkish_text_outside_the_translator(): void
    {
        $found = [];

        foreach (self::PACKAGES as $package) {
            $sources = [...$this->files($package, ['resources/views', 'src'])];
            $scripts = [...$this->files($package, [is_dir("$package/resources/js") ? 'resources/js' : 'js'], '/\.js$/')];

            foreach ([...$sources, ...$scripts] as $file) {
                $source = file_get_contents($file);
                // Comments explain the code; Turkish examples there are fine.
                $source = preg_replace_callback('/\{\{--.*?--\}\}|\/\*.*?\*\//s', fn ($m) => str_repeat("\n", substr_count($m[0], "\n")), $source);

                foreach (explode("\n", $source) as $number => $line) {
                    $code = preg_replace(['~(^|\s)//.*$~', '~^\s*\*.*$~', self::CALL], '', $line);

                    if (preg_match(self::TURKISH, $code)) {
                        $found[] = basename($file).':'.($number + 1).': '.trim(mb_substr($code, 0, 120));
                    }
                }
            }
        }

        self::assertSame([], $found);
    }
}
