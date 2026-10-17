<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;

final class LayoutSettingsTest extends TestCase
{
    public function test_config_values_are_defaults_for_the_browser_not_saved_preferences(): void
    {
        config(['acun-ui.layout.theme' => 'dark', 'acun-ui.layout.sidebar_mode' => 'collapsed', 'acun-ui.layout.brand_color' => 'green']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.head />'));

        self::assertSame('dark', $config['defaults']['theme']);
        self::assertSame('collapsed', $config['defaults']['sidebar_mode']);
        self::assertSame('green', $config['defaults']['brand_color']);
        // A default must not override the choice stored in the browser, so it is not sent as the saved preference.
        self::assertNull($config['theme']);
    }

    public function test_a_page_can_force_its_own_settings(): void
    {
        config(['acun-ui.layout.theme' => 'dark']);
        Layout::page(['theme' => 'light', 'navbar_mode' => 'hidden']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.head theme="dark" />'));

        self::assertSame('light', $config['theme'], 'The page wins over the saved preference and the config.');
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

    /** The primary colour presets are the palette's colours; a light swatch gets a dark tick. */
    public function test_brand_presets_are_the_palette_colours(): void
    {
        self::assertSame(
            ['default' => '#794dff', 'green' => '#00ca4b', 'red' => '#fb4141', 'yellow' => '#fcaa1e', 'blue' => '#04b6d4', 'gray' => '#6b7280', 'dark' => '#3f404d'],
            array_map(fn (array $preset) => $preset[0], Layout::brandPresets()),
        );

        $picker = Blade::render('<x-ui.brand-color-picker />');
        self::assertMatchesRegularExpression('/title="Sarı"[^>]*style="background: #fcaa1e"\s*>\s*<svg[^>]*text-ink/', $picker);
        self::assertMatchesRegularExpression('/title="Koyu"[^>]*style="background: #3f404d"\s*>\s*<svg[^>]*text-surface/', $picker);

        // A name that is no longer a preset (saved before the palette changed) falls back to the default.
        self::assertSame(['default', 'green', 'red', 'yellow', 'blue', 'gray', 'dark'], $this->runtimeConfig(Blade::render('<x-ui.layout.head />'))['brandPresets']);
    }

    /**
     * The tick is white while it stands out 3:1 on the swatch, else dark (cyan gave a white tick at 2.43:1):
     * the light theme's surface or ink (data-theme="light" on it), so it stays so in the dark theme.
     */
    public function test_the_picker_tick_takes_the_more_readable_colour_by_contrast(): void
    {
        $picker = Blade::render('<x-ui.brand-color-picker />');

        foreach (['Varsayılan' => 'text-surface', 'Yeşil' => 'text-ink', 'Kırmızı' => 'text-surface', 'Sarı' => 'text-ink', 'Turkuaz' => 'text-ink', 'Gri' => 'text-surface', 'Koyu' => 'text-surface'] as $name => $tick) {
            self::assertMatchesRegularExpression('/title="'.$name.'"[^>]*>\s*<svg[^>]*\b'.$tick.'\b/', $picker, $name);
        }
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
        self::assertSame(1, substr_count($picker, 'role="radio"'), 'Only the valid preset is drawn.');
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

        self::assertSame(1, substr_count($picker, 'role="radio"'));
        self::assertStringNotContainsString('Satır', $picker);
        self::assertStringNotContainsString('#aabbcc', $picker);
    }
}
