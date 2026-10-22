<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Appearance;
use Acun\Ui\Support\Contrast;
use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;

final class LayoutSettingsTest extends TestCase
{
    public function test_config_values_are_defaults_for_the_browser_not_saved_preferences(): void
    {
        config(['acun-ui.layout.mode' => 'dark', 'acun-ui.layout.sidebar_mode' => 'collapsed', 'acun-ui.layout.brand_color' => 'green']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.head />'));

        self::assertSame('dark', $config['defaults']['mode']);
        self::assertSame('collapsed', $config['defaults']['sidebar_mode']);
        self::assertSame('green', $config['defaults']['brand_color']);
        // A default must not override the choice stored in the browser, so it is not sent as the saved preference.
        self::assertNull($config['mode']);
        self::assertArrayNotHasKey('theme', $config['defaults']);
    }

    /** 1.5: until 3.0 a 1.x theme of light, dark or system (config, prop, Layout::page()) is the mode. */
    public function test_a_1x_theme_value_is_read_as_the_mode(): void
    {
        config(['acun-ui.layout.theme' => 'dark']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.head />'));
        self::assertSame('dark', $config['defaults']['mode']);
        self::assertSame(['key' => 'default', 'light' => 'light', 'dark' => 'dark'], $config['theme'], 'Not a theme key.');

        self::assertSame('light', $this->runtimeConfig(Blade::render('<x-ui.layout.head theme="light" />'))['mode']);
        self::assertSame('system', $this->runtimeConfig(Blade::render('<x-ui.layout.head mode="system" theme="dark" />'))['mode'], 'The mode prop wins.');
    }

    public function test_a_page_can_force_its_own_settings(): void
    {
        config(['acun-ui.layout.mode' => 'dark']);
        Layout::page(['theme' => 'light', 'navbar_mode' => 'hidden']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.head mode="dark" />'));

        self::assertSame('light', $config['mode'], 'The page wins over the saved preference and the config (a 1.x theme value is the mode).');
        self::assertSame('light', Layout::forced('mode'));
        self::assertNull(Layout::forced('theme'));
        self::assertSame('hidden', $config['navbarMode']);
        self::assertTrue($config['forced']);
    }

    /** A site theme's colour is a default the visitor may change, not a saved preference that wins over theirs. */
    public function test_a_layout_can_give_its_own_default_brand_colour(): void
    {
        config(['acun-ui.layout.brand_color' => 'green']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.blank default-brand-color="#e11d48">x</x-ui.layout.blank>'));

        self::assertSame('#e11d48', $config['defaults']['brand_color']);
        self::assertNull($config['brandColor']);
    }

    public function test_page_settings_last_for_one_request_only(): void
    {
        Layout::page(['sidebar_mode' => 'collapsed']);
        self::assertSame('collapsed', Layout::forced('sidebar_mode'));

        $this->app->forgetScopedInstances();

        self::assertNull(Layout::forced('sidebar_mode'));
    }

    public function test_unknown_settings_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Layout::page(['sidebar' => 'collapsed']);
    }

    /**
     * The presets are the palette's solid fills (plan 3.7, 9: a swatch shows the real fill, e.g. green
     * #008933, not #00ca4b); the runtime gets them as colours (the island's colors).
     */
    public function test_brand_presets_are_the_palette_fills(): void
    {
        self::assertSame(
            ['default' => '#794dff', 'green' => '#008933', 'red' => '#d83838', 'yellow' => '#9f6b13', 'blue' => '#038197', 'gray' => '#6b7280', 'dark' => '#3f404d'],
            array_map(fn (array $preset) => $preset[0], Layout::brandPresets()),
        );
        self::assertSame(Appearance::COLORS, Layout::BRAND_COLORS, 'The 1.x name is kept until 3.0.');

        $picker = Blade::render('<x-ui.brand-color-picker />');
        self::assertMatchesRegularExpression('/title="Sarı">\s*<input data-acun-control type="radio"[^>]*>\s*<span[^>]*style="background: #9f6b13"[^>]*><\/span>\s*<svg[^>]*text-surface/', $picker);
        self::assertMatchesRegularExpression('/title="Koyu">\s*<input data-acun-control type="radio"[^>]*>\s*<span[^>]*style="background: #3f404d"[^>]*><\/span>\s*<svg[^>]*text-surface/', $picker);

        // The theme's own colour (default) is no colour of the runtime: it removes the user's.
        self::assertSame(
            ['green' => '#008933', 'red' => '#d83838', 'yellow' => '#9f6b13', 'blue' => '#038197', 'gray' => '#6b7280', 'dark' => ['light' => '#3f404d', 'dark' => '#474747']],
            $this->runtimeConfig(Blade::render('<x-ui.layout.head />'))['colors'],
        );
    }

    /**
     * The tick is white while it stands out 3:1 on the swatch, else dark (cyan gave a white tick at 2.43:1):
     * the light theme's surface or ink (data-theme="light" on it), so it stays so in the dark theme. The
     * presets carry white text, so their ticks are white; a tenant's light colour gets the dark one.
     */
    public function test_the_picker_tick_takes_the_more_readable_colour_by_contrast(): void
    {
        $picker = Blade::render('<x-ui.brand-color-picker />');

        $swatch = fn (string $name, string $tick): string => '/title="'.$name.'">\s*<input data-acun-control type="radio"[^>]*>\s*<span[^>]*><\/span>\s*<svg[^>]*\b'.$tick.'\b/';

        foreach (['Varsayılan', 'Yeşil', 'Kırmızı', 'Sarı', 'Turkuaz', 'Gri', 'Koyu'] as $name) {
            self::assertMatchesRegularExpression($swatch($name, 'text-surface'), $picker, $name);
        }

        $light = Blade::render('<x-ui.brand-color-picker :presets="[\'acik\' => [\'#04b6d4\', \'Açık\'], \'sari\' => [\'#fcaa1e\', \'Sarı\']]" :custom="false" />');
        self::assertMatchesRegularExpression($swatch('Açık', 'text-ink'), $light);
        self::assertMatchesRegularExpression($swatch('Sarı', 'text-ink'), $light);
        // One helper decides: white while it stands out 3:1 (Contrast, the runtime's maths).
        self::assertFalse(Contrast::whiteReads('#04b6d4'));
        self::assertTrue(Contrast::whiteReads('#9f6b13'));
    }

    /** Presets can come from a tenant or a user: only #rrggbb values reach the style attribute. */
    public function test_the_picker_skips_presets_that_are_not_a_hex_colour(): void
    {
        $picker = Blade::render('<x-ui.brand-color-picker :presets="$presets" :custom="false" />', ['presets' => [
            'firma' => ['#0F766E', 'Firma'],
            'kotu' => ['red;background-image:url(//evil.example/x.png)', 'Kötü'],
            'kisa' => ['#abc', 'Kısa'],
            'adli' => ['teal', 'Adlı'],
            'bos' => [null, 'Boş'],
            'dizi-degil' => '#123456',
        ]]);

        self::assertStringContainsString('style="background: #0f766e"', $picker);
        self::assertSame(1, substr_count($picker, 'type="radio"'), 'Only the valid preset is drawn.');
        foreach (['evil.example', 'Kötü', 'Kısa', 'Adlı', 'Boş', '#123456'] as $skipped) {
            self::assertStringNotContainsString($skipped, $picker);
        }
        // The custom colour's starting value reads the same filtered list.
        self::assertStringContainsString('presets: '.\Illuminate\Support\Js::from(['firma' => '#0f766e']), $picker);
    }

    /** T3-D6: "$" also matched before a trailing newline, so "#aabbcc\n" reached the style attribute. */
    public function test_the_picker_skips_a_hex_colour_with_a_trailing_newline(): void
    {
        $picker = Blade::render('<x-ui.brand-color-picker :presets="$presets" :custom="false" />', ['presets' => [
            'firma' => ['#0f766e', 'Firma'],
            'satir' => ["#aabbcc\n", 'Satır'],
        ]]);

        self::assertSame(1, substr_count($picker, 'type="radio"'));
        self::assertStringNotContainsString('Satır', $picker);
        self::assertStringNotContainsString('#aabbcc', $picker);
    }
}
