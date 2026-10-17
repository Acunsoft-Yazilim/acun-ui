<?php

namespace Acun\Ui\Support;

/**
 * Number formats for the components' own texts (the selection bar's count): the
 * application's current language decides the separators. Not a formatting library;
 * the application formats its own data.
 */
final class Format
{
    /** Decimal and thousands separators per language when the intl extension is missing. */
    private const SEPARATORS = [
        'tr' => [',', '.'],
        'de' => [',', '.'],
        'fr' => [',', ' '],
        'es' => [',', '.'],
        'it' => [',', '.'],
        'nl' => [',', '.'],
        'pt' => [',', '.'],
        'ru' => [',', ' '],
    ];

    /** 1.234,5 in Turkish, 1,234.5 in English. */
    public static function number(int|float $value, int $decimals = 0, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if (extension_loaded('intl')) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
            $formatted = $formatter->format($value);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        [$decimal, $thousands] = self::SEPARATORS[strtolower(substr($locale, 0, 2))] ?? ['.', ','];

        return number_format($value, $decimals, $decimal, $thousands);
    }
}
