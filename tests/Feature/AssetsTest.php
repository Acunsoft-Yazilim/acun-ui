<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Assets;
use Acun\Ui\Support\Layout;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;

/**
 * The runtime's browser builds (dist/): the boot script acun:layout.head loads, the
 * runtime @acunUiScripts loads, and the route that serves both.
 */
final class AssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(public_path(Assets::PUBLISHED_DIRECTORY));

        parent::tearDown();
    }

    private static function hash(string $file): string
    {
        return substr(hash_file('xxh128', Assets::distPath($file)), 0, 12);
    }

    private function nextPage(): void
    {
        $this->app->forgetScopedInstances();
    }

    public function test_the_head_loads_the_boot_script_blocking_and_tracked(): void
    {
        $html = Blade::render('<acun:layout.head />');

        self::assertStringContainsString(
            '<script src="http://localhost/acun-ui/acun-ui-boot.js?id='.self::hash(Assets::BOOT).'" data-acun-ui-boot data-navigate-track></script>',
            $html,
        );
        // Settings first: the boot script reads them while the page is still being parsed.
        self::assertLessThan(strpos($html, 'data-acun-ui-boot'), strpos($html, 'data-acun-ui-config'));
    }

    public function test_the_boot_script_takes_the_vite_csp_nonce(): void
    {
        Vite::useCspNonce('n0nce');

        self::assertMatchesRegularExpression('~<script src="[^"]*acun-ui-boot\.js\?id=\w+" nonce="n0nce" data-acun-ui-boot~', Blade::render('<acun:layout.head />'));
    }

    public function test_a_page_that_forces_its_layout_says_so_to_the_runtime(): void
    {
        self::assertFalse($this->runtimeConfig(Blade::render('<acun:layout.head />'))['forced']);

        Layout::page(['skin' => 'bordered']);
        self::assertTrue($this->runtimeConfig(Blade::render('<acun:layout.head />'))['forced']);
    }

    public function test_the_directive_prints_the_runtime_deferred_once_per_page(): void
    {
        $tag = '<script src="http://localhost/acun-ui/acun-ui.js?id='.self::hash(Assets::RUNTIME).'" defer data-acun-ui data-navigate-track data-navigate-once></script>';

        self::assertSame($tag, Blade::render("@acunUiScripts\n@acunUiScripts"));

        $this->nextPage();
        self::assertStringContainsString(' nonce="r4nd0m" defer ', Blade::render("@acunUiScripts(['nonce' => 'r4nd0m'])"));
    }

    public function test_the_prefix_and_a_cdn_folder_come_from_the_config(): void
    {
        config()->set('acun-ui.assets.prefix', '/assets/ui/');
        self::assertStringContainsString('src="http://localhost/assets/ui/acun-ui-boot.js?id=', Blade::render('<acun:layout.head />'));

        config()->set('acun-ui.assets.url', 'https://cdn.example.com/acun-ui/');
        self::assertSame('https://cdn.example.com/acun-ui/acun-ui.js?id='.self::hash(Assets::RUNTIME), Assets::url(Assets::RUNTIME));
    }

    public function test_a_published_copy_is_used_only_while_it_is_the_same_as_the_package_one(): void
    {
        $published = public_path(Assets::PUBLISHED_DIRECTORY.'/'.Assets::BOOT);
        File::ensureDirectoryExists(dirname($published));
        File::copy(Assets::distPath(Assets::BOOT), $published);

        self::assertSame('http://localhost/vendor/acun-ui/acun-ui-boot.js?id='.self::hash(Assets::BOOT), Assets::url(Assets::BOOT));

        File::put($published, '/* an older build */');
        clearstatcache();
        self::assertSame('http://localhost/acun-ui/acun-ui-boot.js?id='.self::hash(Assets::BOOT), Assets::url(Assets::BOOT), 'An outdated copy is skipped.');
    }

    public function test_the_builds_can_be_published(): void
    {
        $this->artisan('vendor:publish', ['--tag' => Assets::PUBLISH_TAG, '--force' => true])->assertSuccessful();

        foreach (Assets::FILES as $file) {
            self::assertFileEquals(Assets::distPath($file), public_path(Assets::PUBLISHED_DIRECTORY.'/'.$file));
        }
    }

    public function test_the_route_serves_both_files_with_long_cache_headers_and_no_session(): void
    {
        Livewire::flushState();

        foreach (Assets::FILES as $file) {
            $response = $this->get('/acun-ui/'.$file.'?id='.self::hash($file));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('ETag', '"'.self::hash($file).'"');
            self::assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
            self::assertSame([], $response->headers->getCookies());
        }

        $this->get('/acun-ui/acun-ui.js', ['If-None-Match' => '"'.self::hash(Assets::RUNTIME).'"'])->assertStatus(304);
    }

    public function test_the_route_serves_nothing_else(): void
    {
        $this->get('/acun-ui/boot.js')->assertNotFound();
        $this->get('/acun-ui/../composer.json')->assertNotFound();
    }

    public function test_the_builds_are_in_the_package(): void
    {
        foreach (Assets::FILES as $file) {
            self::assertFileExists(Assets::distPath($file));
            self::assertStringStartsWith('/*! Acun UI ', File::get(Assets::distPath($file)));
        }
    }
}
