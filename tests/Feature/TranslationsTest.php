<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\Concerns\ChecksTranslations;
use Acun\Ui\Tests\TestCase;

/**
 * The package's texts are English keys with a Turkish translation in its own lang/tr.json: every
 * key used has one, no key is Turkish, and no Turkish text sits in a view, class or script outside
 * __(). Only this package's sources and lang/tr.json count: it works without acunsoft/acun-pro,
 * which checks its own the same way (its tests/Feature/TranslationsTest.php).
 */
final class TranslationsTest extends TestCase
{
    use ChecksTranslations;

    private const PACKAGE = __DIR__.'/../..';

    public function test_every_key_is_english_and_has_a_turkish_translation(): void
    {
        [$missing, $turkishKeys] = self::keyProblems(self::PACKAGE, self::translationsOf(self::PACKAGE));

        self::assertSame([], $missing, 'Keys without a Turkish translation in lang/tr.json.');
        self::assertSame([], $turkishKeys, 'Keys are the English text.');
    }

    public function test_no_turkish_text_outside_the_translator(): void
    {
        self::assertSame([], self::turkishOutsideTheTranslator(self::PACKAGE, 'js'));
    }
}
