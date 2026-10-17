<?php

namespace Acun\Ui\Support;

use InvalidArgumentException;

/**
 * The Heroicons set used by acun:icon (resources/icons/heroicons.php,
 * generated with npm run build:icons).
 */
final class Icon
{
    public const OUTLINE = 'outline';

    public const SOLID = 'solid';

    /** @var array{outline: array<string, array<int, string>>, solid: array<string, array<int, string|array{d: string, evenodd: bool}>>}|null */
    private static ?array $set = null;

    /**
     * @return array<int, string|array{d: string, evenodd: bool}> SVG path definitions
     */
    public static function paths(string $name, string $style = self::OUTLINE): array
    {
        return self::set()[$style][$name]
            ?? throw new InvalidArgumentException("Undefined icon [{$name}] ({$style}). List: resources/icons/heroicons.php");
    }

    public static function exists(string $name, string $style = self::OUTLINE): bool
    {
        return isset(self::set()[$style][$name]);
    }

    /**
     * @return array<string, array<int, mixed>> name => paths
     */
    public static function all(string $style = self::OUTLINE): array
    {
        return self::set()[$style] ?? [];
    }

    private static function set(): array
    {
        return self::$set ??= require __DIR__.'/../../resources/icons/heroicons.php';
    }
}
