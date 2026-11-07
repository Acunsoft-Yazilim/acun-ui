<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\ContentWidth;
use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The page container's width (2026-11-07): the named widths, a CSS length, boxed as wide, anything else default;
 * acun:container (the ac-container class) and the width the layouts write on <html> in the order of precedence
 * (a forced value, the layout's prop, the page's default, config). css/core.css holds the class and the widths.
 */
final class ContentWidthTest extends TestCase
{
    /** @return array<string, array{0: mixed, 1: ?string}> */
    public static function values(): array
    {
        return [
            'default' => ['default', 'default'],
            'wide' => ['wide', 'wide'],
            'wider' => ['wider', 'wider'],
            'full' => ['full', 'full'],
            'boxed is wide' => ['boxed', 'wide'],
            'a name in capitals' => [' Wide ', 'wide'],
            'pixels' => ['1520px', '1520px'],
            'rem' => ['90rem', '90rem'],
            'a fraction of rem' => ['87.5rem', '87.5rem'],
            'vw' => ['92vw', '92vw'],
            'percent' => ['95%', '95%'],
            'nothing' => [null, null],
            'an empty string' => ['  ', null],
            'an unknown name' => ['xl', 'default'],
            'a number without a unit' => ['1280', 'default'],
            'zero' => ['0px', 'default'],
            'a negative length' => ['-10px', 'default'],
            'an expression' => ['calc(100% - 2rem)', 'default'],
            'markup' => ['1px" onload="x', 'default'],
            'a declaration' => ['1px; color: red', 'default'],
            'not a string' => [1280, 'default'],
        ];
    }

    #[DataProvider('values')]
    public function test_a_value_is_a_name_a_length_or_default(mixed $value, ?string $expected): void
    {
        self::assertSame($expected, ContentWidth::normalize($value));
    }

    public function test_the_css_value_and_the_attributes_of_html(): void
    {
        self::assertSame('var(--ac-container-default)', ContentWidth::css('default'));
        self::assertSame('var(--ac-container-wide)', ContentWidth::css('boxed'));
        self::assertSame('var(--ac-container-wider)', ContentWidth::css('wider'));
        self::assertSame('none', ContentWidth::css('full'));
        self::assertSame('none', ContentWidth::css(null));
        self::assertSame('1520px', ContentWidth::css('1520px'));
        self::assertSame('var(--ac-container-default)', ContentWidth::css('nonsense'));

        self::assertSame(' data-content-width="default"', ContentWidth::rootAttributes('default'));
        self::assertSame(' data-content-width="wide"', ContentWidth::rootAttributes('boxed'), 'boxed is printed as wide.');
        self::assertSame(' data-content-width="wider"', ContentWidth::rootAttributes('wider'));
        self::assertSame(' data-content-width="custom" style="--ac-container-width: 90rem"', ContentWidth::rootAttributes('90rem'));
        self::assertSame('', ContentWidth::rootAttributes('full'), 'Full: no attribute (the whole width).');
        self::assertSame('', ContentWidth::rootAttributes(null));
        self::assertSame(' data-content-width="default"', ContentWidth::rootAttributes('"><script>'));
    }

    public function test_the_first_value_given_wins_else_full(): void
    {
        self::assertSame('wider', ContentWidth::resolve(null, 'wider', 'wide', 'default'));
        self::assertSame('wide', ContentWidth::resolve(null, '', null, 'boxed'));
        self::assertSame('default', ContentWidth::resolve('nonsense', 'wide'), 'An invalid value given is default, not the next one.');
        self::assertSame('full', ContentWidth::resolve());
        self::assertSame('full', ContentWidth::resolve(null, null));
    }

    public function test_the_container_component_is_the_class_with_its_own_width(): void
    {
        self::assertSame('<div class="ac-container">Sayfa</div>', trim(Blade::render('<acun:container>Sayfa</acun:container>')), 'Without a width: the page\'s.');
        self::assertSame('<section class="ac-container py-8" style="--ac-container-width: var(--ac-container-wide)">x</section>', trim(Blade::render('<acun:container as="section" width="boxed" class="py-8">x</acun:container>')));
        self::assertSame('<div class="ac-container" style="--ac-container-width: var(--ac-container-wider)">x</div>', trim(Blade::render('<acun:container width="wider">x</acun:container>')));
        self::assertSame('<div class="ac-container" style="--ac-container-width: none">x</div>', trim(Blade::render('<acun:container width="full">x</acun:container>')));
        self::assertSame('<header class="ac-container" style="--ac-container-width: 1520px">x</header>', trim(Blade::render('<acun:container as="header" width="1520px">x</acun:container>')));
        self::assertSame('<div class="ac-container" style="--ac-container-width: var(--ac-container-default)">x</div>', trim(Blade::render('<acun:container as="script" width="huge">x</acun:container>')), 'An unknown element is a div, an unknown width default.');
        // The page's own style and attributes stay.
        self::assertSame('<nav class="ac-container" style="--ac-container-width: var(--ac-container-wide); color: inherit;" aria-label="Menü">x</nav>', trim(Blade::render('<acun:container as="nav" width="wide" style="color: inherit" aria-label="Menü">x</acun:container>')));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function blankWidths(): array
    {
        return [
            'default' => ['default', ' data-content-width="default"'],
            'wide' => ['wide', ' data-content-width="wide"'],
            'wider' => ['wider', ' data-content-width="wider"'],
            'full' => ['full', ''],
            'boxed' => ['boxed', ' data-content-width="wide"'],
            'a length' => ['1520px', ' data-content-width="custom" style="--ac-container-width: 1520px"'],
            'nonsense' => ['3xl', ' data-content-width="default"'],
        ];
    }

    #[DataProvider('blankWidths')]
    public function test_the_blank_layout_writes_the_width_on_html(string $width, string $attributes): void
    {
        $html = Blade::render("<acun:layout.blank content-width=\"{$width}\">x</acun:layout.blank>");

        self::assertStringContainsString('<html lang="tr" data-theme="light"'.$attributes.'>', $html);
        self::assertSame(ContentWidth::normalize($width), $this->runtimeConfig($html)['contentWidth'], 'The boot script gets the same value.');
    }

    public function test_the_blank_layouts_width_follows_the_forced_value_the_prop_the_pages_default_then_config(): void
    {
        $html = fn (string $props = '') => Blade::render("<acun:layout.blank {$props}>x</acun:layout.blank>");
        $root = fn (string $page) => preg_match('~<html[^>]*>~', $page, $tag) ? $tag[0] : '';

        // Nothing given: the whole width (no attribute), as before.
        self::assertSame('<html lang="tr" data-theme="light">', $root($html()));
        self::assertNull($this->runtimeConfig($html())['defaults']['content_width']);

        config(['acun-ui.layout.content_width' => 'wider']);
        self::assertSame('<html lang="tr" data-theme="light" data-content-width="wider">', $root($html()), 'Config.');
        self::assertSame('wider', $this->runtimeConfig($html())['defaults']['content_width']);
        self::assertNull($this->runtimeConfig($html())['contentWidth'], 'A default, not a saved preference: the browser\'s choice still wins.');

        $page = $html('default-content-width="default"');
        self::assertSame('<html lang="tr" data-theme="light" data-content-width="default">', $root($page), 'The page\'s (a package\'s) default beats config.');
        self::assertSame('default', $this->runtimeConfig($page)['defaults']['content_width']);
        self::assertNull($this->runtimeConfig($page)['contentWidth']);

        $page = $html('content-width="wide" default-content-width="default"');
        self::assertSame('<html lang="tr" data-theme="light" data-content-width="wide">', $root($page), 'The prop beats the default.');
        self::assertSame('wide', $this->runtimeConfig($page)['contentWidth']);

        Layout::page(['content_width' => '96rem']);
        $page = $html('content-width="wide" default-content-width="default"');
        self::assertSame('<html lang="tr" data-theme="light" data-content-width="custom" style="--ac-container-width: 96rem">', $root($page), 'A forced value beats them all.');
        self::assertSame('96rem', $this->runtimeConfig($page)['contentWidth']);
    }

    public function test_the_head_alone_hands_the_width_to_the_boot_script(): void
    {
        config(['acun-ui.layout.content_width' => 'boxed']);

        $config = $this->runtimeConfig(Blade::render('<acun:layout.head content-width="1600px" default-content-width="nonsense" />'));
        self::assertSame('1600px', $config['contentWidth']);
        self::assertSame('default', $config['defaults']['content_width'], 'An invalid default is default.');

        self::assertSame('wide', $this->runtimeConfig(Blade::render('<acun:layout.head />'))['defaults']['content_width'], 'Config, boxed as wide.');
    }

    /** css/core.css: the class, the widths, boxed and the panel's container as the same width. */
    public function test_the_stylesheet_defines_the_container_and_its_widths(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2).'/css/core.css');
        $rule = fn (string $selector) => preg_match('~'.preg_quote($selector, '~').'\s*\{([^}]*)\}~', $css, $match) ? preg_replace('/\s+/', ' ', trim($match[1])) : null;

        self::assertSame('box-sizing: border-box; width: 100%; max-width: var(--ac-container-width); margin-inline: auto; padding-inline: var(--ac-container-gutter);', $rule('.ac-container'));
        self::assertSame('width: 100%; max-width: var(--panel-container-width); margin-inline: auto;', $rule('.panel-container'), 'The 2.x class keeps working.');
        self::assertMatchesRegularExpression('~:root \{\s*--ac-container-default: 1280px;\s*--ac-container-wide: 1440px;\s*--ac-container-wider: 1600px;\s*--ac-container-width: none;\s*--ac-container-gutter: clamp\(1rem, 3vw, 2rem\);\s*--panel-gutter: 1rem;\s*--panel-container-width: var\(--ac-container-width\);~', $css);
        self::assertSame('--ac-container-width: var(--ac-container-default);', $rule(":root[data-content-width='default']"));
        self::assertSame('--ac-container-width: var(--ac-container-wide);', $rule(":root:is([data-content-width='wide'], [data-content-width='boxed'])"), 'boxed (2.x) is wide.');
        self::assertSame('--ac-container-width: var(--ac-container-wider);', $rule(":root[data-content-width='wider']"));
        self::assertStringNotContainsString("data-content-width='full'", $css, 'Full is the default: no attribute.');
    }
}
