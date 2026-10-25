<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Appearance;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

/**
 * acun:theme-switcher, the one theme switcher: the navbar button, the drawer and the inline sections;
 * which sections show for which props; the sections and themes a package adds (Appearance::section(),
 * offerThemes()); radio-group semantics; Reset; and the 1.x tags that wrap it (theme-toggle,
 * brand-color-picker, theme-customizer). The panel's and the storefront's sections are tested in
 * acunsoft/acun-pro, the browser behaviour in tests/js/theme-switcher.test.mjs.
 */
final class ThemeSwitcherTest extends TestCase
{
    /** @return list<string> The sections a render shows, in order. */
    private static function sections(string $html): array
    {
        preg_match_all('~data-switcher-section="([a-z-]+)"~', $html, $matches);

        return $matches[1];
    }

    private static function dom(string $html): \DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($document);
    }

    public function test_the_button_switches_between_light_and_dark(): void
    {
        $html = Blade::render('<acun:theme-switcher class="ms-auto" />');

        self::assertStringContainsString('x-on:click="$store.appearance.toggle();', $html);
        self::assertStringContainsString('data-test="theme-toggle"', $html);
        self::assertMatchesRegularExpression('~class="flex size-9 [^"]*\bms-auto"~', $html, 'Attributes go on the button.');
        self::assertStringContainsString('Koyu moda geç', $html);
        self::assertMatchesRegularExpression('~</button><span class="sr-only" role="status" data-theme-switcher-status></span>~', $html, 'A status says the new mode.');
        self::assertStringNotContainsString('data-switcher-section', $html, 'No sections: the button only switches.');

        self::assertSame('', trim(Blade::render('<acun:theme-switcher :modes="false" />')), 'No modes: nothing to switch.');
        self::assertSame('', trim(Blade::render('<acun:theme-switcher :modes="[\'light\', \'system\']" />')), 'The button needs light and dark.');

        // A theme without a dark member has no mode to choose.
        Appearance::define('kagit', 'kagit');
        Appearance::use('kagit');
        self::assertSame('', trim(Blade::render('<acun:theme-switcher />')));
        self::assertSame(['color'], self::sections(Blade::render('<acun:theme-switcher as="inline" />')));
    }

    public function test_the_sections_follow_the_props(): void
    {
        // The free package alone: the mode and the colour; no themes are offered.
        self::assertSame(['mode', 'color'], self::sections(Blade::render('<acun:theme-switcher as="drawer" />')));
        self::assertSame(['mode', 'color'], self::sections(Blade::render('<acun:theme-switcher as="inline" />')));

        self::assertSame(['color'], self::sections(Blade::render('<acun:theme-switcher as="inline" :modes="false" />')));
        self::assertSame(['mode'], self::sections(Blade::render('<acun:theme-switcher as="inline" :colors="false" :custom="false" />')));

        $customOnly = Blade::render('<acun:theme-switcher as="inline" :modes="false" :colors="false" />');
        self::assertSame(['color'], self::sections($customOnly));
        self::assertStringContainsString('type="color"', $customOnly);
        self::assertStringNotContainsString('type="radio"', $customOnly);

        $presetsOnly = Blade::render('<acun:theme-switcher as="inline" :modes="false" :custom="false" />');
        self::assertSame(7, substr_count($presetsOnly, 'type="radio"'), 'The seven presets.');
        self::assertStringNotContainsString('type="color"', $presetsOnly);

        // The sections prop: which and in what order (an array or a comma-separated string).
        self::assertSame(['color', 'mode'], self::sections(Blade::render('<acun:theme-switcher as="inline" sections="color, mode" />')));
        self::assertSame(['color'], self::sections(Blade::render('<acun:theme-switcher as="inline" :sections="[\'color\']" />')));
        self::assertSame(['mode'], self::sections(Blade::render('<acun:theme-switcher as="inline" :sections="[\'mode\', \'skin\', \'header\']" />')), 'Sections nobody added are skipped (the panel\'s and the storefront\'s are acunsoft/acun-pro\'s).');

        // The modes prop: the choices, in the given order.
        $twoModes = Blade::render('<acun:theme-switcher as="inline" sections="mode" :modes="[\'dark\', \'light\']" />');
        preg_match_all('~type="radio" name="[^"]+" value="([a-z]+)"~', $twoModes, $modes);
        self::assertSame(['dark', 'light'], $modes[1]);

        // Themes given by hand; false hides what a package offers.
        $items = "[['key' => 'gece', 'name' => 'Gece', 'url' => '/tema/gece', 'active' => true], ['key' => 'gunduz', 'name' => 'Gündüz', 'url' => '/tema/gunduz', 'look' => 'modern']]";
        $themed = Blade::render('<acun:theme-switcher as="inline" :themes="'.$items.'" />');
        self::assertSame(['mode', 'color', 'theme'], self::sections($themed));
        self::assertMatchesRegularExpression('~<a href="/tema/gece" x-on:click="keepOpen\(\)"\s+aria-current="true"~', $themed);
        self::assertMatchesRegularExpression('~<span data-theme="gunduz" class="[^"]*\bbg-page\b[^"]*rounded-\[12px\]~', $themed, 'A miniature in the theme\'s own tokens and look.');
        self::assertStringContainsString('(geçerli tema)', $themed);
        self::assertSame(['mode', 'color'], self::sections(Blade::render('<acun:theme-switcher as="inline" :themes="false" />')));

        // Sections of your own in the slot, after the others.
        $slot = Blade::render('<acun:theme-switcher as="inline" sections="mode"><acun:theme-switcher.section name="ozel" title="Özel">x</acun:theme-switcher.section></acun:theme-switcher>');
        self::assertSame(['mode', 'ozel'], self::sections($slot));
    }

    public function test_a_package_adds_sections_and_offers_themes(): void
    {
        View::addNamespace('switcher-test', __DIR__.'/../Fixtures/views');
        Appearance::section('density', 'switcher-test::switcher-section');
        Appearance::offerThemes(fn (): array => [
            ['key' => 'gece', 'name' => 'Gece', 'style' => 'Koyu', 'url' => '/tema/gece', 'active' => true, 'look' => 'classic'],
            ['key' => 'eksik', 'name' => 'Bağlantısız'],
            ['key' => 'kotu', 'url' => 'javascript:alert(1)', 'look' => 'tuhaf'],
        ]);

        self::assertSame(['density'], array_keys(Appearance::sections()));
        self::assertSame(
            [
                ['key' => 'gece', 'name' => 'Gece', 'style' => 'Koyu', 'description' => '', 'light' => 'gece', 'url' => '/tema/gece', 'active' => true, 'look' => 'classic'],
                ['key' => 'kotu', 'name' => 'kotu', 'style' => '', 'description' => '', 'light' => 'kotu', 'url' => '#', 'active' => false, 'look' => 'default'],
            ],
            Appearance::switchable(),
            'An item needs a key and a link; an unsafe link becomes #, an unknown look the default.',
        );

        $html = Blade::render('<acun:theme-switcher as="drawer" sections="mode,theme,density" />');
        self::assertSame(['mode', 'theme', 'density'], self::sections($html));
        self::assertStringContainsString('Satır aralığı', $html);
        self::assertStringContainsString('x-on:change="($store.yogunluk.sec)($event.target.value)"', $html);
        self::assertStringContainsString('x-bind:checked="($store.yogunluk.deger) === \'sik\'"', $html);
        self::assertStringNotContainsString('Bağlantısız', $html);

        Appearance::offerThemes(null);
        self::assertSame([], Appearance::switchable());
    }

    public function test_the_switchers_own_section_names_are_reserved(): void
    {
        foreach (['mode', 'color', 'theme', 'Kötü ad'] as $name) {
            try {
                Appearance::section($name, 'x');
                self::fail("[{$name}] was accepted.");
            } catch (InvalidArgumentException) {
                self::assertArrayNotHasKey($name, Appearance::sections());
            }
        }
    }

    public function test_every_choice_is_a_native_radio_in_a_group_its_heading_names(): void
    {
        View::addNamespace('switcher-test', __DIR__.'/../Fixtures/views');
        Appearance::section('density', 'switcher-test::switcher-section');
        $html = Blade::render('<acun:theme-switcher as="drawer" sections="mode,color,density" />');
        $xpath = self::dom($html);

        $groups = $xpath->query('//*[@role="radiogroup"]');
        self::assertSame(3, $groups->length);

        foreach ($groups as $group) {
            $heading = $xpath->query('//*[@id="'.$group->getAttribute('aria-labelledby').'"]')->item(0);
            self::assertNotNull($heading, 'The group is named by its heading.');
            self::assertNotSame('', trim($heading->textContent));

            $radios = $xpath->query('.//input[@type="radio"]', $group);
            self::assertGreaterThan(1, $radios->length);
            self::assertCount(1, array_unique(array_map(fn (\DOMElement $radio) => $radio->getAttribute('name'), iterator_to_array($radios))), 'One name per group: Tab enters once, the arrow keys move.');
        }

        self::assertSame($xpath->query('//input[@type="radio"]')->length, $xpath->query('//*[@role="radiogroup"]//input[@type="radio"]')->length, 'No radio outside a group.');
        self::assertSame(0, $xpath->query('//*[@aria-pressed]')->length, 'Choices are radios, not toggle buttons.');
        self::assertSame(7, $xpath->query('//*[@data-switcher-section="color"]//input[@type="radio"][@aria-label]')->length, 'Every swatch is named.');
        self::assertSame(1, $xpath->query('//*[@data-switcher-section="color"]//label[contains(@class, "has-focus-visible:outline-2")]/input[@type="color"]')->length, 'The colour input shows a focus ring.');
        self::assertSame(1, $xpath->query('//*[@role="status"]')->length, 'A status for what Reset did.');
    }

    public function test_the_drawer_its_triggers_and_its_texts(): void
    {
        $edge = Blade::render('<acun:theme-switcher as="drawer" />');
        self::assertStringContainsString('data-theme-switcher="drawer"', $edge);
        self::assertMatchesRegularExpression('~<button\s+type="button"\s+x-on:click="\$dispatch\(\'acun:drawer:open\', \{ name: \'theme-switcher\' \}\)"\s+class="group fixed top-1/2 right-0 ~', $edge, 'A gear on the right edge.');
        self::assertStringContainsString('aria-label="Görünüm ayarları"', $edge);
        self::assertStringContainsString('aria-haspopup="dialog"', $edge);
        self::assertStringContainsString('aria-controls="theme-switcher"', $edge);
        self::assertStringContainsString('data-acun-drawer="theme-switcher"', $edge);
        self::assertStringContainsString('<h3 class="text-base font-semibold text-ink">Görünüm</h3>', $edge);
        self::assertStringContainsString('Seçimler anında uygulanır ve bu tarayıcıda saklanır.', $edge);
        self::assertStringContainsString('data-drawer-keep-open', $edge);

        $button = Blade::render('<acun:theme-switcher as="drawer" trigger="button" name="gorunum" label="Görünüm seçenekleri" title="Ayarlar" :description="false" />');
        self::assertDoesNotMatchRegularExpression('~class="group fixed~', $button);
        self::assertMatchesRegularExpression('~<button\s+type="button"\s+x-on:click="\$dispatch\(\'acun:drawer:open\', \{ name: \'gorunum\' \}\)"\s+class="flex size-9 ~', $button, 'An icon button in place.');
        self::assertStringContainsString('aria-label="Görünüm seçenekleri"', $button);
        self::assertStringContainsString('data-acun-drawer="gorunum"', $button);
        self::assertStringContainsString('>Ayarlar</h3>', $button);
        self::assertStringNotContainsString('Seçimler anında uygulanır', $button);

        $none = Blade::render('<acun:theme-switcher as="drawer" :trigger="false" />');
        self::assertStringNotContainsString('aria-haspopup', $none, 'Your own trigger opens it.');
        self::assertStringContainsString('data-acun-drawer="theme-switcher"', $none);

        $inline = Blade::render('<acun:theme-switcher as="inline" title="Görünümünüz" />');
        self::assertStringNotContainsString('data-acun-drawer="', $inline);
        self::assertStringContainsString('<p class="mb-3 text-sm font-medium text-ink">Görünümünüz</p>', $inline);
        self::assertStringNotContainsString('Seçimler anında uygulanır', $inline, 'Inline: no intro unless given.');
    }

    public function test_reset_forgets_the_scope_and_may_return_a_server_choice_too(): void
    {
        $drawer = Blade::render('<acun:theme-switcher as="drawer" />');
        self::assertStringContainsString('$store.customizer?.reset()', $drawer, 'The stores go back to the defaults and the scope\'s key is removed.');
        self::assertMatchesRegularExpression('~<button[^>]*x-on:click="reset\(\)"[^>]*>\s*<svg[^>]*>.*?</svg>\s*Varsayılana döndür~s', $drawer);
        self::assertMatchesRegularExpression('~<button[^>]*data-acun-close=""[^>]*>\s*Tamam~', $drawer, 'Done closes the drawer it is in.');

        self::assertStringNotContainsString('Varsayılana döndür', Blade::render('<acun:theme-switcher as="drawer" :reset="false" />'));

        $link = Blade::render('<acun:theme-switcher as="drawer" reset-url="/tema/gece?to=%2F" />');
        self::assertMatchesRegularExpression('~<a href="/tema/gece\?to=%2F"[^>]*x-on:click="reset\(\); keepOpen\(\)"[^>]*data-drawer-keep-open~', $link, 'A link that also returns the server\'s choice; the drawer opens again on the next page.');
        self::assertStringContainsString('<a href="#"', Blade::render('<acun:theme-switcher as="drawer" reset-url="javascript:alert(1)" />'));
    }

    public function test_the_1x_tags_are_the_switcher(): void
    {
        // theme-toggle: the button.
        self::assertSame(Blade::render('<acun:theme-switcher class="ms-auto" />'), Blade::render('<acun:theme-toggle class="ms-auto" />'));

        // brand-color-picker: the colour alone, inline, with its 1.x props.
        $presets = "['firma' => ['#0f766e', 'Firma'], 'gece' => ['#1e3a8a', 'Gece']]";
        self::assertSame(
            Blade::render('<acun:theme-switcher as="inline" :modes="false" :themes="false" :reset="false" :colors="'.$presets.'" :custom="false" title="Marka rengi" :headings="false" />'),
            Blade::render('<acun:brand-color-picker :presets="'.$presets.'" :custom="false" label="Marka rengi" />'),
        );
        $picker = Blade::render('<acun:brand-color-picker />');
        self::assertSame(['color'], self::sections($picker));
        self::assertMatchesRegularExpression('~<h4 id="[^"]+" class="[^"]*\bsr-only">Ana renk</h4>~', $picker, 'Its heading is for screen readers: the picker sits under a label of its own.');
        self::assertStringNotContainsString('Varsayılana döndür', $picker);

        // theme-customizer: the drawer the panel layouts use, under its 1.x name; sidebar=false leaves out the menu's sections.
        self::assertSame(
            Blade::render('<acun:theme-switcher as="drawer" name="theme-customizer" title="Tema" :sections="[\'mode\', \'color\', \'theme\', \'skin\', \'width\', \'sidebar\', \'navbar\']" />'),
            Blade::render('<acun:theme-customizer title="Tema" />'),
        );
        self::assertSame(
            Blade::render('<acun:theme-switcher as="drawer" name="theme-customizer" :sections="[\'mode\', \'color\', \'theme\', \'skin\', \'width\']" />'),
            Blade::render('<acun:theme-customizer :sidebar="false" />'),
        );
        self::assertStringContainsString('data-acun-drawer="theme-customizer"', Blade::render('<x-ui.theme-customizer />'), 'The 1.x name: <acun:drawer.trigger name="theme-customizer"> still opens it.');
    }
}
