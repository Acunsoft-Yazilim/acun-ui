<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Assets;
use Acun\Ui\Support\FontLibrary;
use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;

/**
 * The font library: 20 Google Fonts families with Turkish letters, served from the package (no font host), and the
 * text and heading fonts a page takes from it (only the chosen family's files, the first paint's weights preloaded).
 */
final class FontLibraryTest extends TestCase
{
    protected function tearDown(): void
    {
        FontLibrary::using(null);
        File::deleteDirectory(public_path(FontLibrary::PUBLISHED_DIRECTORY));

        parent::tearDown();
    }

    private function pageHead(): string
    {
        return Blade::render('<acun:layout.head title="Mağaza" />');
    }

    /** Every unicode-range code point (U+0100-017F, U+00E7) as integers. */
    private static function covers(string $ranges, int $codePoint): bool
    {
        foreach (explode(',', $ranges) as $range) {
            [$from, $to] = array_pad(explode('-', trim(str_replace('U+', '', $range))), 2, null);
            if ($codePoint >= hexdec($from) && $codePoint <= hexdec($to ?? $from)) {
                return true;
            }
        }

        return false;
    }

    public function test_the_library_has_twenty_open_font_licence_families_with_turkish_letters_as_woff2(): void
    {
        $library = FontLibrary::all();

        self::assertCount(20, $library);
        foreach ($library as $id => $font) {
            self::assertSame('OFL-1.1', $font['license'], $id);
            self::assertStringContainsString('SIL Open Font License', File::get(FontLibrary::path($id.'/LICENSE.txt')), $id.': its licence ships with it.');
            self::assertSame($id === 'lato' ? [400, 700] : FontLibrary::WEIGHTS, $font['weights'], $id.': the kit\'s weights (Lato has no 500 or 600).');
            self::assertSame(['latin', 'latin-ext'], array_keys($font['subsets']), $id);

            foreach (['ç' => 'latin', 'ö' => 'latin', 'ü' => 'latin', 'ğ' => 'latin-ext', 'Ğ' => 'latin-ext', 'ş' => 'latin-ext', 'Ş' => 'latin-ext', 'ı' => 'latin-ext', 'İ' => 'latin-ext'] as $letter => $subset) {
                self::assertTrue(self::covers($font['subsets'][$subset], mb_ord($letter)), $id.': '.$letter.' in '.$subset);
            }

            foreach ($font['weights'] as $weight) {
                foreach (array_keys($font['subsets']) as $subset) {
                    $file = FontLibrary::path($id.'/'.FontLibrary::file($id, $subset, $weight));
                    self::assertFileExists($file);
                    self::assertSame('wOF2', substr(File::get($file), 0, 4), $id.': WOFF2');
                }
            }
        }

        self::assertCount(19 * 8 + 4, File::glob(FontLibrary::path('*/*.woff2')), 'Nothing else: four weights (Lato two), two subsets each.');
        self::assertSame(
            ['public-sans', 'inter', 'manrope', 'dm-sans', 'plus-jakarta-sans', 'roboto', 'open-sans', 'source-sans-3', 'nunito-sans', 'lato', 'montserrat', 'poppins', 'work-sans', 'ibm-plex-sans', 'noto-sans', 'outfit'],
            array_keys(FontLibrary::options()['sans-serif']),
        );
        self::assertSame(['lora', 'merriweather', 'playfair-display', 'source-serif-4'], array_keys(FontLibrary::options()['serif']));
    }

    public function test_a_family_without_some_weights_preloads_the_nearest_a_browser_draws(): void
    {
        self::assertSame([400, 400, 700, 700], array_map(fn (int $weight): int => FontLibrary::nearest('lato', $weight), [400, 500, 600, 700]));
        self::assertSame(400, FontLibrary::nearest('lato', 300), 'Under 400: down first, else up (Lato has no 300: 400).');
        self::assertSame(700, FontLibrary::nearest('lato', 800), 'Over 500: up first, else down.');

        config(['acun-ui.layout.font' => 'lato']);
        preg_match_all('~<link rel="preload" as="font" type="font/woff2" href="[^"]*/(lato-[a-z-]+-\d+-normal\.woff2)~', $this->pageHead(), $preloads);
        self::assertSame(['lato-latin-400-normal.woff2', 'lato-latin-ext-400-normal.woff2', 'lato-latin-700-normal.woff2', 'lato-latin-ext-700-normal.woff2'], $preloads[1], 'Semibold is drawn bold: 700 is preloaded, never a file that is not there.');
        self::assertSame(4, substr_count($this->pageHead(), "font-family:'Lato'"));
    }

    public function test_without_a_chosen_font_the_head_prints_nothing_of_the_library(): void
    {
        $html = $this->pageHead();

        self::assertStringNotContainsString('data-acun-fonts', $html);
        self::assertStringNotContainsString('/acun-ui/fonts/', $html);
    }

    public function test_a_chosen_text_font_loads_only_its_own_files_with_the_first_paints_weights_preloaded(): void
    {
        config(['acun-ui.layout.font' => 'lora']);
        $html = $this->pageHead();

        preg_match_all('~<link rel="preload" as="font" type="font/woff2" href="([^"]+)" crossorigin />~', $html, $preloads);
        self::assertSame([
            'lora-latin-400-normal.woff2', 'lora-latin-ext-400-normal.woff2', 'lora-latin-600-normal.woff2', 'lora-latin-ext-600-normal.woff2',
        ], array_map(fn (string $url): string => basename((string) parse_url($url, PHP_URL_PATH)), $preloads[1]), 'Regular and semibold, both subsets: the first paint.');
        self::assertStringContainsString('http://localhost/acun-ui/fonts/lora/lora-latin-400-normal.woff2?id='.Assets::hash(FontLibrary::path('lora/lora-latin-400-normal.woff2')), $html);

        self::assertSame(8, substr_count($html, '@font-face'), 'Four weights in two subsets: a browser downloads only those the page uses.');
        self::assertSame(8, substr_count($html, "font-family:'Lora'"));
        self::assertMatchesRegularExpression("~:root,\[data-theme\]\{--ac-font-sans:'Lora', ui-serif[^;]*;--ac-font-brand:'Lora', ui-serif[^}]*\}~", $html, 'The text and the logo take it, over the theme.');
        self::assertStringNotContainsString('--ac-font-display', $html, 'The headings keep the theme\'s (the text font unless it has its own).');
        foreach (array_keys(FontLibrary::all()) as $id) {
            if ($id !== 'lora') {
                self::assertStringNotContainsString('/fonts/'.$id.'/', $html, $id.' is not loaded.');
            }
        }
        self::assertDoesNotMatchRegularExpression('~googleapis|gstatic|bunny\.net~', $html, 'No font host.');
    }

    public function test_a_chosen_heading_font_sets_the_headings_without_a_preload(): void
    {
        config(['acun-ui.layout.heading_font' => 'playfair-display']);
        $html = $this->pageHead();

        self::assertStringNotContainsString('rel="preload" as="font"', $html);
        self::assertSame(8, substr_count($html, "font-family:'Playfair Display'"));
        self::assertStringContainsString(":root,[data-theme]{--ac-font-display:'Playfair Display', ui-serif", $html);
        self::assertStringNotContainsString('--ac-font-sans', $html);
    }

    public function test_the_page_then_the_application_then_config_choose_and_an_unknown_id_is_ignored(): void
    {
        config(['acun-ui.layout.font' => 'inter', 'acun-ui.layout.heading_font' => 'yok-boyle-font']);
        self::assertSame(['text' => 'inter', 'heading' => null], FontLibrary::chosen());

        $asked = 0;
        FontLibrary::using(function () use (&$asked): array {
            $asked++;

            return ['text' => 'roboto', 'heading' => 'lora'];
        });
        self::assertSame(['text' => 'roboto', 'heading' => 'lora'], FontLibrary::chosen(), 'The application\'s choice (a database setting) over config.');
        FontLibrary::chosen();
        self::assertSame(1, $asked, 'Asked once per request.');

        Layout::page(['font' => 'manrope']);
        self::assertSame(['text' => 'manrope', 'heading' => 'lora'], FontLibrary::chosen(), 'A page\'s own over both.');

        FontLibrary::using(fn (): string => '../../etc/passwd');
        Layout::page(['font' => null]);
        self::assertSame('inter', FontLibrary::chosen()['text'], 'Only the library\'s ids.');
    }

    public function test_the_tags_take_the_nonce_and_a_wire_navigate_visit_has_no_preloads(): void
    {
        config(['acun-ui.layout.font' => 'inter']);
        Vite::useCspNonce('abc123');
        self::assertStringContainsString('<style data-acun-fonts nonce="abc123">', $this->pageHead());

        $this->app->instance('request', Request::create('/', 'GET', server: ['HTTP_X_LIVEWIRE_NAVIGATE' => '1']));
        $html = FontLibrary::tags();
        self::assertStringNotContainsString('rel="preload"', $html, 'Livewire\'s head merge would fetch them again.');
        self::assertStringContainsString("font-family:'Inter'", $html);
    }

    public function test_the_theme_fonts_a_chosen_one_replaces_are_not_preloaded(): void
    {
        self::assertSame(['public-sans', 'dm-mono'], FontLibrary::withoutReplaced(['public-sans', 'dm-mono']), 'Nothing chosen.');

        config(['acun-ui.layout.font' => 'inter']);
        self::assertSame(['dm-mono'], FontLibrary::withoutReplaced(['public-sans', 'dm-mono']), 'The panel\'s text font; the monospace one stays.');
        self::assertSame(['fraunces', 'dm-mono'], FontLibrary::withoutReplaced(['public-sans', 'fraunces', 'dm-mono']), 'A theme\'s text font (its first); its heading font (the next) stays.');

        config(['acun-ui.layout.font' => null, 'acun-ui.layout.heading_font' => 'lora']);
        self::assertSame(['public-sans', 'dm-mono'], FontLibrary::withoutReplaced(['public-sans', 'fraunces', 'dm-mono']));

        config(['acun-ui.layout.font' => 'public-sans', 'acun-ui.layout.heading_font' => null]);
        self::assertSame(['public-sans', 'dm-mono'], FontLibrary::withoutReplaced(['public-sans', 'dm-mono']), 'The same font chosen: still the theme\'s.');
    }

    public function test_the_route_serves_the_librarys_files_only_with_long_cache_headers(): void
    {
        $path = FontLibrary::path('lora/lora-latin-ext-400-normal.woff2');
        $response = $this->get('/acun-ui/fonts/lora/lora-latin-ext-400-normal.woff2?id='.Assets::hash($path));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'font/woff2');
        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $response->assertHeader('ETag', '"'.Assets::hash($path).'"');
        self::assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
        self::assertSame([], $response->headers->getCookies());
        $this->get('/acun-ui/fonts/lora/lora-latin-ext-400-normal.woff2', ['If-None-Match' => '"'.Assets::hash($path).'"'])->assertStatus(304);

        $this->get('/acun-ui/fonts/lora/LICENSE.txt')->assertNotFound();
        $this->get('/acun-ui/fonts/lora/inter-latin-400-normal.woff2')->assertNotFound();
        $this->get('/acun-ui/fonts/yok/yok-latin-400-normal.woff2')->assertNotFound();
        $this->get('/acun-ui/fonts/lora/lora-latin-400-italic.woff2')->assertNotFound();
        $this->get('/acun-ui/fonts/..%2F..%2Fcomposer.json/x.woff2')->assertNotFound();
    }

    public function test_a_published_copy_or_a_cdn_folder_serves_them_instead(): void
    {
        $this->artisan('vendor:publish', ['--tag' => FontLibrary::PUBLISH_TAG, '--force' => true])->assertSuccessful();
        self::assertFileExists(public_path(FontLibrary::PUBLISHED_DIRECTORY.'/lora/LICENSE.txt'), 'The licences are published with the files.');
        self::assertStringStartsWith('http://localhost/vendor/acun-ui/fonts/lora/lora-latin-400-normal.woff2?id=', FontLibrary::url('lora', 'lora-latin-400-normal.woff2'));

        File::put(public_path(FontLibrary::PUBLISHED_DIRECTORY.'/lora/lora-latin-400-normal.woff2'), 'eski');
        self::assertStringStartsWith('http://localhost/acun-ui/fonts/lora/', FontLibrary::url('lora', 'lora-latin-400-normal.woff2'), 'An outdated copy is skipped.');

        config(['acun-ui.assets.url' => 'https://cdn.example.com/acun-ui/']);
        self::assertStringStartsWith('https://cdn.example.com/acun-ui/fonts/lora/lora-latin-400-normal.woff2?id=', FontLibrary::url('lora', 'lora-latin-400-normal.woff2'));
    }

    public function test_the_font_select_lists_the_families_by_group_and_previews_only_the_chosen_one(): void
    {
        $html = Blade::render('<acun:font-select name="font" label="Metin yazı tipi" value="lora" /><acun:font-select name="heading_font" />');

        self::assertSame(2, preg_match_all('~<option value=""\s*(selected)?\s*>Tema yazı tipi</option>~', $html), 'The theme\'s own font is the empty choice.');
        self::assertStringContainsString('<optgroup label="Tırnaksız">', $html);
        self::assertStringContainsString('<optgroup label="Tırnaklı">', $html);
        self::assertSame(20 * 2, preg_match_all('~<option value="[a-z0-9-]+"~', $html));
        self::assertMatchesRegularExpression('~<option value="lora"\s+selected\s*>Lora</option>~', $html);
        self::assertStringContainsString('Pijamalı hasta yağız şoföre çabucak güvendi.', $html, 'A preview with every Turkish letter.');

        self::assertSame(1, substr_count($html, 'data-acun-font-select-faces'), 'The preview\'s faces once per page.');
        self::assertSame(40, substr_count($html, '@font-face'), 'Only the regular weight of each family (declared; downloaded when shown).');
        self::assertStringContainsString('[data-acun-font-select]:has(option[value="lora"]:checked) [data-slot="font-preview"]{font-family:\'Lora\'', $html);
    }

    public function test_the_font_select_takes_wire_model_on_its_select(): void
    {
        $html = Blade::render('<acun:font-select wire:model.live="font" class="mt-4" :preview="false" />');

        self::assertMatchesRegularExpression('~<select[^>]*wire:model\.live="font"~', $html);
        self::assertMatchesRegularExpression('~<div data-acun-font-select class="[^"]*mt-4~', $html, 'Layout attributes on the field.');
        self::assertStringNotContainsString('font-preview', $html);
        self::assertDoesNotMatchRegularExpression('~<option value="[a-z0-9-]+"\s+selected~', $html, 'Bound: the model picks the family.');
    }
}
