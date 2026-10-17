<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Appearance;
use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The page's colours (Acun\Ui\Support\Appearance): the theme pairs, the hardest text backgrounds of the
 * enabled themes, the storage scope, and what acun:layout.head and acun:layout.blank print for the runtime.
 */
final class AppearanceTest extends TestCase
{
    public function test_the_head_island_carries_the_scope_the_theme_the_targets_and_the_colours(): void
    {
        $config = $this->runtimeConfig(Blade::render('<x-ui.layout.head />'));

        self::assertSame('app', $config['scope']);
        self::assertSame(['key' => 'default', 'light' => 'light', 'dark' => 'dark'], $config['theme']);
        self::assertSame(['light' => '#ffffff', 'dark' => '#1f1f1f'], $config['targets']);
        self::assertSame(Appearance::colors(), $config['colors']);
        self::assertNull($config['mode']);
        self::assertArrayNotHasKey('brandPresets', $config, 'The 1.x preset names are gone: presets are colours now.');
    }

    public function test_the_blank_layout_prints_data_theme_on_html_and_class_dark_with_a_dark_mode(): void
    {
        self::assertMatchesRegularExpression('/<html lang="tr" data-theme="light">/', Blade::render('<x-ui.layout.blank>x</x-ui.layout.blank>'));
        self::assertMatchesRegularExpression('/<html lang="tr" data-theme="dark" class="dark">/', Blade::render('<x-ui.layout.blank mode="dark">x</x-ui.layout.blank>'));

        // A page that forces the mode (Layout::page) gets it on the first frame too.
        Layout::page(['mode' => 'dark']);
        self::assertMatchesRegularExpression('/<html lang="tr" data-theme="dark" class="dark">/', Blade::render('<x-ui.layout.blank mode="light">x</x-ui.layout.blank>'));
    }

    public function test_a_defined_pair_is_the_page_theme_when_a_page_names_it(): void
    {
        Appearance::define('atelier', 'atelier', 'atelier-dark', ['light' => '#fffdf8', 'dark' => '#2e2a23']);

        self::assertSame(['key' => 'atelier', 'light' => 'atelier', 'dark' => 'atelier-dark'], Appearance::pair('atelier'));
        self::assertSame(['key' => 'default', 'light' => 'light', 'dark' => 'dark'], Appearance::pair('unknown'), 'An unknown key is the current theme.');

        $html = Blade::render('<x-ui.layout.blank theme="atelier" mode="dark">x</x-ui.layout.blank>');
        self::assertMatchesRegularExpression('/<html lang="tr" data-theme="atelier-dark" class="dark">/', $html);
        self::assertSame(['key' => 'atelier', 'light' => 'atelier', 'dark' => 'atelier-dark'], $this->runtimeConfig($html)['theme']);

        // config('acun-ui.layout.theme') names it for every page.
        config(['acun-ui.layout.theme' => 'atelier']);
        self::assertSame('atelier', Appearance::current());
        self::assertSame('atelier', Appearance::light());
        self::assertSame('atelier-dark', Appearance::dark());
    }

    /** The hardest pair across the enabled themes: the darkest light text background, the lightest dark one. */
    public function test_the_targets_are_the_hardest_across_the_enabled_themes(): void
    {
        self::assertSame(Appearance::TARGETS, Appearance::targets(), 'The built-in pair alone.');

        Appearance::define('atelier', 'atelier', 'atelier-dark', ['light' => '#FFFDF8', 'dark' => '#2e2a23']);
        Appearance::define('nova', dark: 'nova-dark');
        Appearance::define('paper', 'paper', null, ['light' => '#f7f3ea']);

        self::assertSame(['light' => '#f7f3ea', 'dark' => '#2e2a23'], Appearance::targets());
        self::assertSame(['key' => 'nova', 'light' => 'nova', 'dark' => 'nova-dark'], Appearance::pair('nova'), 'The light member defaults to the key.');
        self::assertSame('paper', Appearance::pair('paper')['light']);
        self::assertNull(Appearance::pair('paper')['dark'], 'A single member: no mode choice.');
        self::assertSame(['default', 'atelier', 'nova', 'paper'], array_keys(Appearance::themes()));
    }

    /** @return array<string, array{0: string, 1: ?string, 2: ?string}> */
    public static function invalidThemes(): array
    {
        return [
            'reserved key light' => ['light', null, null],
            'reserved key dark' => ['dark', null, null],
            'reserved key system' => ['system', null, null],
            'reserved key default' => ['default', null, null],
            'a reserved member' => ['brand', 'light', 'brand-dark'],
            'a dark member not ending in dark' => ['brand', 'brand', 'brand-night'],
            'a light member ending in dark' => ['brand', 'brand-dark', null],
            'not a name' => ['Brand Theme', null, null],
        ];
    }

    #[DataProvider('invalidThemes')]
    public function test_reserved_keys_and_wrong_member_names_are_refused(string $key, ?string $light, ?string $dark): void
    {
        $this->expectException(InvalidArgumentException::class);

        Appearance::define($key, $light, $dark);
    }

    public function test_two_themes_cannot_share_a_key_or_a_data_theme_name(): void
    {
        Appearance::define('nova', dark: 'nova-dark');
        Appearance::define('nova', dark: 'nova-dark');
        self::assertCount(2, Appearance::themes(), 'The same definition again is fine (a provider booted twice).');

        try {
            Appearance::define('nova', dark: 'other-dark');
            self::fail('A key with other members is refused.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        Appearance::define('vivid', 'vivid', 'nova-dark');
    }

    /** The scope: the head prop, else Appearance::use(…, scope:), else config('acun-ui.layout.scope'), else app. */
    public function test_the_scope_follows_the_prop_then_the_request_then_config(): void
    {
        $scope = fn (string $head = '<x-ui.layout.head />') => $this->runtimeConfig(Blade::render($head))['scope'];

        self::assertSame('app', $scope());
        config(['acun-ui.layout.scope' => 'site']);
        self::assertSame('site', $scope());

        Appearance::define('atelier', dark: 'atelier-dark');
        Appearance::use('atelier', scope: 'shop');
        self::assertSame('shop', Appearance::scope());
        self::assertSame('atelier', Appearance::current());
        self::assertSame('shop', $scope());
        self::assertSame('docs', $scope('<x-ui.layout.head scope="docs" />'));
        self::assertSame('atelier', $this->runtimeConfig(Blade::render('<x-ui.layout.head />'))['theme']['key']);

        // One request only.
        $this->app->instance('request', Request::create('/'));
        self::assertNull(Appearance::scope());
        self::assertSame('default', Appearance::current());
    }

    public function test_a_scope_name_is_a_storage_key_and_use_needs_a_defined_theme(): void
    {
        try {
            Blade::render('<x-ui.layout.head scope="my shop" />');
            self::fail('A scope with a space is refused.');
        } catch (\Throwable $exception) {
            self::assertStringContainsString('Appearance scope [my shop]', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        Appearance::use('nowhere');
    }

    public function test_the_presets_and_their_island_colours(): void
    {
        self::assertSame(['default', 'green', 'red', 'yellow', 'blue', 'gray', 'dark'], array_keys(Appearance::presets()));
        self::assertSame(['#3f404d', 'Koyu'], Appearance::presets()['dark'], 'A colour with its own dark fill shows its light one.');
        self::assertArrayNotHasKey('default', Appearance::colors());
        self::assertSame(['light' => '#3f404d', 'dark' => '#474747'], Appearance::colors()['dark']);
    }
}
