<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;

final class LayoutSettingsTest extends TestCase
{
    private function renderSidebar(string $attributes = ''): string
    {
        return Blade::render("<x-ui.layout.sidebar {$attributes}>x</x-ui.layout.sidebar>");
    }

    public function test_config_values_are_defaults_for_the_browser_not_saved_preferences(): void
    {
        config(['acun-ui.layout.theme' => 'dark', 'acun-ui.layout.sidebar_mode' => 'collapsed', 'acun-ui.layout.brand_color' => 'green']);

        $config = $this->runtimeConfig($this->renderSidebar());

        self::assertSame('dark', $config['defaults']['theme']);
        self::assertSame('collapsed', $config['defaults']['sidebar_mode']);
        self::assertSame('green', $config['defaults']['brand_color']);
        // A default must not override the choice stored in the browser, so it is not sent as the saved preference.
        self::assertNull($config['theme']);
    }

    /** A site theme's colour is a default the visitor may change, not a saved preference that wins over theirs. */
    public function test_a_layout_can_give_its_own_default_brand_colour(): void
    {
        config(['acun-ui.layout.brand_color' => 'green']);

        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.blank default-brand-color="#e11d48">x</x-ui.layout.blank>'));

        self::assertSame('#e11d48', $config['defaults']['brand_color']);
        self::assertNull($config['brandColor']);
    }

    public function test_navbar_mode_and_semi_dark_menu_use_the_config_on_the_first_frame(): void
    {
        config(['acun-ui.layout.navbar_mode' => 'static', 'acun-ui.layout.semi_dark' => true]);

        $html = $this->renderSidebar();

        self::assertStringContainsString('data-navbar="static"', $html);
        self::assertMatchesRegularExpression('/class="panel-sidebar [^"]*\bdark\b/', $html);
    }

    public function test_a_page_can_force_its_own_settings(): void
    {
        config(['acun-ui.layout.theme' => 'dark']);
        Layout::page(['theme' => 'light', 'navbar_mode' => 'hidden']);

        $html = $this->renderSidebar('theme="dark"');

        self::assertSame('light', $this->runtimeConfig($html)['theme'], 'The page wins over the saved preference and the config.');
        self::assertStringContainsString('data-navbar="hidden"', $html);
    }

    public function test_the_top_bar_the_page_and_the_footer_share_one_container(): void
    {
        foreach (['sidebar', 'horizontal'] as $layout) {
            $html = Blade::render("<x-ui.layout.{$layout}><x-slot:footer>Alt bilgi</x-slot:footer>
Sayfa</x-ui.layout.{$layout}>");

            // One gutter around each, one container inside: their edges line up on every screen.
            self::assertMatchesRegularExpression('/class="panel-topbar panel-gutter [^"]*"[^>]*>\s*<header class="panel-container /', $html, $layout);
            self::assertMatchesRegularExpression('/<main id="main-content"[^>]*class="[^"]*\bpanel-gutter\b[^"]*">\s*<div class="panel-container">\s*Sayfa/', $html, $layout);
            self::assertMatchesRegularExpression('/<div class="panel-gutter w-full min-w-0">\s*<div class="panel-container">Alt bilgi/', $html, $layout);
            self::assertStringNotContainsString('data-content-width', $html, 'Wide unless chosen.');
        }
    }

    public function test_content_width_follows_the_page_then_the_prop_then_config(): void
    {
        config(['acun-ui.layout.content_width' => 'boxed']);

        // The config is the browser's default, applied by the boot script.
        $html = Blade::render('<x-ui.layout.horizontal>x</x-ui.layout.horizontal>');
        self::assertSame('boxed', $this->runtimeConfig($html)['defaults']['content_width']);
        self::assertNull($this->runtimeConfig($html)['contentWidth']);

        // A saved preference is printed on <html> for the first frame.
        $html = $this->renderSidebar('content-width="boxed"');
        self::assertMatchesRegularExpression('/<html lang="[a-z-]+" class="panel-layout" data-content-width="boxed">/', $html);
        self::assertSame('boxed', $this->runtimeConfig($html)['contentWidth']);

        // The page wins.
        Layout::page(['content_width' => 'full']);
        $html = $this->renderSidebar('content-width="boxed"');
        self::assertStringNotContainsString('data-content-width', $html);
        self::assertSame('full', $this->runtimeConfig($html)['contentWidth']);
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
        self::assertMatchesRegularExpression('/title="Sarı"[^>]*style="background: #fcaa1e"\s*>\s*<svg[^>]*text-gray-900/', $picker);
        self::assertMatchesRegularExpression('/title="Koyu"[^>]*style="background: #3f404d"\s*>\s*<svg[^>]*text-white/', $picker);

        // A name that is no longer a preset (saved before the palette changed) falls back to the default.
        self::assertSame(['default', 'green', 'red', 'yellow', 'blue', 'gray', 'dark'], $this->runtimeConfig($this->renderSidebar())['brandPresets']);
    }

    /** The tick is white while it stands out 3:1 on the swatch, else dark (cyan gave a white tick at 2.43:1). */
    public function test_the_picker_tick_takes_the_more_readable_colour_by_contrast(): void
    {
        $picker = Blade::render('<x-ui.brand-color-picker />');

        foreach (['Varsayılan' => 'text-white', 'Yeşil' => 'text-gray-900', 'Kırmızı' => 'text-white', 'Sarı' => 'text-gray-900', 'Turkuaz' => 'text-gray-900', 'Gri' => 'text-white', 'Koyu' => 'text-white'] as $name => $tick) {
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

    /** stacked: the horizontal menu gets its own row under the logo, search and user menu. */
    public function test_horizontal_layout_can_put_the_menu_on_its_own_row(): void
    {
        $stacked = Blade::render('<x-ui.layout.horizontal stacked><x-slot:nav><a href="/menu">Menü öğesi</a></x-slot:nav> x </x-ui.layout.horizontal>');
        self::assertMatchesRegularExpression('/<nav aria-label="Ana menü" class="hidden h-12 min-w-0 items-stretch border-t[^"]*">\s*<a href="\/menu">Menü öğesi<\/a>/', $stacked);

        $single = Blade::render('<x-ui.layout.horizontal><x-slot:nav><a href="/menu">Menü öğesi</a></x-slot:nav> x </x-ui.layout.horizontal>');
        self::assertStringNotContainsString('h-12 min-w-0 items-stretch border-t', $single);
        self::assertMatchesRegularExpression('/<nav aria-label="Ana menü" class="hidden min-w-0 flex-1 items-center self-stretch lg:flex">\s*<a href="\/menu">/', $single);
    }

    public function test_customizer_and_theme_toggle_follow_prop_then_config(): void
    {
        config(['acun-ui.layout.customizer' => true, 'acun-ui.layout.theme_toggle' => false]);

        $fromConfig = $this->renderSidebar();
        self::assertStringContainsString('Görünüm ayarları', $fromConfig);
        self::assertStringNotContainsString('data-test="theme-toggle"', $fromConfig);

        $fromProps = $this->renderSidebar(':customizer="false" :theme-toggle="true"');
        self::assertStringNotContainsString('Görünüm ayarları', $fromProps, 'An explicit prop wins over the config.');
        self::assertStringContainsString('data-test="theme-toggle"', $fromProps);
    }

    /** The hidden top bar holds the menu button, the user menu and the theme button: below lg it stays. */
    public function test_a_hidden_top_bar_stays_on_phones_and_tablets(): void
    {
        $html = Blade::render('<x-ui.layout.sidebar navbar-mode="hidden"><x-slot:userMenu><button>Çıkış Yap</button></x-slot:userMenu> Sayfa</x-ui.layout.sidebar>');

        self::assertSame(1, preg_match('/<div\s+class="(panel-topbar [^"]*)"\s+:class="([^"]*)"\s*>(.*?)<\/header>/s', $html, $topbar));
        $classes = explode(' ', $topbar[1]);
        self::assertContains('lg:hidden', $classes, 'Hidden from lg up.');
        self::assertNotContains('hidden', $classes, 'Not hidden on every screen.');
        self::assertSame("{ 'lg:hidden': \$store.navbar.mode === 'hidden' }", html_entity_decode($topbar[2], ENT_QUOTES));
        // What it holds below lg: the drawer's button, the theme button and the user menu.
        self::assertStringContainsString('aria-controls="panel-sidebar"', $topbar[3]);
        self::assertStringContainsString('lg:hidden', $topbar[3]);
        self::assertStringContainsString('data-test="theme-toggle"', $topbar[3]);
        self::assertStringContainsString('<button>Çıkış Yap</button>', $topbar[3]);

        self::assertDoesNotMatchRegularExpression('/class="panel-topbar [^"]*\blg:hidden\b/', $this->renderSidebar('navbar-mode="static"'));
    }

    public function test_without_config_the_package_defaults_apply(): void
    {
        $html = $this->renderSidebar();

        self::assertStringContainsString('data-navbar="fixed"', $html);
        self::assertStringContainsString('data-test="theme-toggle"', $html);
        self::assertStringNotContainsString('Görünüm ayarları', $html);
    }
}
