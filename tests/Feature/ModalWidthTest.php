<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;

/** acun:modal's max-width: every width has its class; another value is an error, not a narrow dialog. */
final class ModalWidthTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function widths(): array
    {
        return [
            'sm' => ['sm', 'sm:max-w-sm'], 'md' => ['md', 'sm:max-w-md'], 'lg' => ['lg', 'sm:max-w-lg'], 'xl' => ['xl', 'sm:max-w-xl'],
            '2xl' => ['2xl', 'sm:max-w-2xl'], '3xl' => ['3xl', 'sm:max-w-3xl'], '4xl' => ['4xl', 'sm:max-w-4xl'], '5xl' => ['5xl', 'sm:max-w-5xl'],
            'full' => ['full', 'sm:max-w-none'],
        ];
    }

    #[DataProvider('widths')]
    public function test_each_width_has_its_class(string $width, string $class): void
    {
        $html = Blade::render('<acun:modal name="kosullar" title="Kullanım koşulları" :max-width="$width">Metin</acun:modal>', ['width' => $width]);

        self::assertMatchesRegularExpression('/data-slot="modal-panel"[^>]*class="[^"]*\s'.preg_quote($class, '/').'\s/', $html);
    }

    public function test_the_default_is_md(): void
    {
        self::assertStringContainsString(' sm:max-w-md ', Blade::render('<acun:modal name="kosullar">Metin</acun:modal>'));
    }

    public function test_an_unknown_width_is_an_error(): void
    {
        $this->expectExceptionMessage('acun:modal [kosullar]: max-width "6xl" is not one of sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, full.');

        Blade::render('<acun:modal name="kosullar" max-width="6xl">Metin</acun:modal>');
    }

}
