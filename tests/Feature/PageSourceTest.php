<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\BladeComments;
use Acun\Ui\Support\Markup;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

/**
 * A tidy page source: the head with one tag per line and no blank lines, fonts as a cached
 * stylesheet instead of an inline style block, no blank lines left by Blade comments.
 */
final class PageSourceTest extends TestCase
{
    private string $build;

    protected function setUp(): void
    {
        parent::setUp();

        // A new name per test: Laravel keeps a font manifest it has read for the rest of the process.
        $this->build = public_path('build-test-'.uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->build);

        parent::tearDown();
    }

    public function test_the_head_prints_one_tag_per_line_with_one_indentation(): void
    {
        $html = Blade::render(<<<'BLADE'
            <head>
                <acun:layout.head title="Pano">
                    <link rel="icon" href="/favicon.svg">

                    <meta name="robots" content="noindex"><meta name="theme-color" content="#fff">
                </acun:layout.head>
            </head>
            BLADE);

        preg_match('~<head>\n(.*?)\n</head>~s', $html, $head);
        $lines = explode("\n", $head[1]);

        self::assertNotContains('', array_map('trim', $lines), 'No blank lines.');
        foreach ($lines as $line) {
            self::assertMatchesRegularExpression('/^    <(?!\s)/', $line, 'Four spaces, then a tag: '.$line);
        }
        self::assertContains('    <meta name="theme-color" content="#fff">', $lines, 'Tags printed side by side get a line each.');
    }

    public function test_markup_lines_leaves_script_and_style_contents_alone(): void
    {
        $html = "<meta a>\n\n   <script>\n  if (a > b) { x = '<i>'; }\n</script><style>\n  p > a { color: red }\n</style>";

        self::assertSame(
            "<meta a>\n  <script>\n  if (a > b) { x = '<i>'; }\n</script>\n  <style>\n  p > a { color: red }\n</style>",
            Markup::lines($html, '  '),
        );
    }

    public function test_lines_holding_only_a_blade_comment_leave_nothing_behind(): void
    {
        $template = "<div>\n    {{-- one line --}}\n    <span>a</span>\n    {{--\n        several\n        lines\n    --}}\n    <span>b</span> {{-- after a tag: stays --}}\n</div>\n@verbatim\n{{-- shown --}}\n@endverbatim";

        self::assertSame(
            "<div>\n    <span>a</span>\n    <span>b</span> {{-- after a tag: stays --}}\n</div>\n@verbatim\n{{-- shown --}}\n@endverbatim",
            BladeComments::strip($template),
        );
        self::assertSame("<div>\n<span>a</span>\n</div>", trim(Blade::render("<div>\n{{-- yorum --}}\n<span>a</span>\n</div>")));
    }

    public function test_a_comment_followed_by_content_on_its_line_keeps_everything(): void
    {
        $template = "{{-- header --}} <header>Title</header>\n<main>Body</main>\n{{-- footer --}}\n<footer>F</footer>";

        self::assertSame("{{-- header --}} <header>Title</header>\n<main>Body</main>\n<footer>F</footer>", BladeComments::strip($template));
        self::assertSame("{{-- a --}} @if(\$x)\n<p>keep</p>\n@endif {{-- b --}}\n", BladeComments::strip("{{-- a --}} @if(\$x)\n<p>keep</p>\n@endif {{-- b --}}\n"));
    }

    public function test_a_page_script_is_printed_only_on_the_page_that_pushes_it(): void
    {
        $withScript = Blade::render("@push('page-scripts')<script src=\"/build/rapor.js\" type=\"module\"></script>@endpush\n<acun:layout.sidebar brand-name=\"Panel\">Rapor</acun:layout.sidebar>");
        self::assertMatchesRegularExpression('~<script src="/build/rapor.js" type="module"></script>\s*</body>~', $withScript, 'Last in the body, after Livewire.');

        app('view')->flushStacks();
        $without = Blade::render('<acun:layout.sidebar brand-name="Panel">Pano</acun:layout.sidebar>');
        self::assertStringNotContainsString('rapor.js', $without);
    }

    /** A font build with the given families (alias => family name), the first preloaded. */
    private function fontBuild(array $families = ['public-sans' => 'Public Sans']): string
    {
        if (! method_exists(\Illuminate\Foundation\Vite::class, 'fonts')) {
            self::markTestSkipped('Vite fonts (@fonts) arrived in Laravel 13.');
        }

        $directory = basename($this->build);
        $styles = array_map(fn (string $family): string => '@font-face { font-family: "'.$family.'"; }', $families);
        $first = array_key_first($families);
        File::ensureDirectoryExists($this->build.'/assets');
        File::put($this->build.'/assets/fonts-abc123.css', implode("\n", $styles));
        File::put($this->build.'/fonts-manifest.json', json_encode([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => $styles,
                'variables' => array_map(fn (string $family): string => ':root { --font: "'.$family.'"; }', $families),
            ],
            'preloads' => [
                ['alias' => $first, 'family' => $families[$first], 'weight' => 400, 'style' => 'normal', 'file' => 'assets/'.$first.'-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => array_map(fn (string $family): array => ['family' => $family, 'variable' => '--font', 'variants' => []], $families),
        ]));
        app(\Illuminate\Foundation\Vite::class)->useBuildDirectory($directory);

        return $directory;
    }

    public function test_acun_fonts_links_the_built_fonts_css_instead_of_an_inline_style(): void
    {
        $directory = $this->fontBuild();

        $html = Blade::render("@acunFonts(['public-sans'])");

        self::assertStringContainsString('<link rel="preload" as="font" href="http://localhost/'.$directory.'/assets/public-sans-400.woff2"', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="http://localhost/'.$directory.'/assets/fonts-abc123.css" />', $html);
        self::assertStringNotContainsString('<style', $html);
        self::assertStringNotContainsString('@font-face', $html);
    }

    /** Some of the families: only their preloads, and the same cached file (a browser downloads only the fonts a page uses). */
    public function test_acun_fonts_for_some_of_the_families_preloads_theirs_and_links_the_built_file(): void
    {
        $directory = $this->fontBuild(['dm-mono' => 'DM Mono', 'public-sans' => 'Public Sans']);

        $html = Blade::render("@acunFonts('public-sans')");

        self::assertStringNotContainsString('dm-mono-400.woff2', $html, 'Only the preloads of the families asked for.');
        self::assertStringContainsString('<link rel="stylesheet" href="http://localhost/'.$directory.'/assets/fonts-abc123.css" />', $html);
        self::assertStringNotContainsString('<style', $html);
        self::assertStringNotContainsString('@font-face', $html);
    }

    public function test_a_wire_navigate_request_prints_no_preload_tags(): void
    {
        $this->fontBuild();
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', server: ['HTTP_X_LIVEWIRE_NAVIGATE' => '1']));

        $html = Blade::render('@acunFonts');

        self::assertStringNotContainsString('rel="preload"', $html);
        self::assertStringContainsString('fonts-abc123.css', $html);
    }

    public function test_acun_fonts_prints_nothing_without_a_font_build(): void
    {
        app(\Illuminate\Foundation\Vite::class)->useBuildDirectory(basename($this->build));

        self::assertSame('', trim(Blade::render('@acunFonts')));
    }

    public function test_acun_fonts_prints_nothing_before_laravel_has_a_font_plugin(): void
    {
        // Laravel 11/12: the Vite helper has no fonts().
        $this->app->instance(\Illuminate\Foundation\Vite::class, new class
        {
            public function __invoke(): string
            {
                return '';
            }
        });

        self::assertSame('', trim(Blade::render('@acunFonts')));
    }

    /**
     * Blade does not compile @js or @json inside a component tag (<acun:button x-on:click="…@js(…)">):
     * the directive reaches the browser as text and the Alpine expression breaks. Inside component
     * tags the value goes through {{ Js::from(…) }} or is built in @php first.
     */
    public function test_no_view_puts_a_blade_directive_inside_a_component_tag(): void
    {
        $tag = '~<(?:acun:|x-)[\w.:-]+(?:\s+(?:[^\s"\'>=]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?))*\s*/?>~s';
        $found = [];

        foreach ([__DIR__.'/../../resources/views', __DIR__.'/../../../acun-ui-pro/resources/views'] as $root) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $source = file_get_contents($file->getPathname());
                preg_match_all($tag, $source, $tags);

                foreach ($tags[0] as $component) {
                    if (preg_match('~@(js|json)\(~', $component)) {
                        $found[] = basename($file->getPathname()).': '.substr($component, 0, 80);
                    }
                }
            }
        }

        self::assertSame([], $found);
    }
}
