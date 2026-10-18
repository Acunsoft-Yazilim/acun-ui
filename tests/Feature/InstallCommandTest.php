<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * php artisan acun:install in a temporary application folder for every test (package.json,
 * resources/css/app.css, resources/js/app.js, vendor/acunsoft/acun-ui): the base path points there
 * while the test runs, so the repository's own files are never touched.
 */
final class InstallCommandTest extends TestCase
{
    /** A Laravel 13 skeleton's package.json: no dependencies section yet. */
    private const PACKAGE = <<<'JSON'
        {
            "$schema": "https://www.schemastore.org/package.json",
            "private": true,
            "type": "module",
            "scripts": {
                "build": "vite build",
                "dev": "vite"
            },
            "devDependencies": {
                "@tailwindcss/vite": "^4.1.0",
                "concurrently": "^10.0.3",
                "laravel-vite-plugin": "^3.1",
                "tailwindcss": "^4.1.0",
                "vite": "^8.0.0"
            }
        }

        JSON;

    private const SOURCES = <<<'CSS'
        @import 'tailwindcss';

        @source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
        @source '../../storage/framework/views/*.php';
        @source '../**/*.blade.php';
        @source '../**/*.js';

        CSS;

    /** The skeleton's own font: Acun UI's fonts lose to it. */
    private const THEME = <<<'CSS'

        @theme {
            --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
                'Segoe UI Symbol', 'Noto Color Emoji';
        }

        CSS;

    private const SOURCE_LINE = "@source '../../vendor/acunsoft/acun-ui/resources/views';";

    private string $root;

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Long output lines stay on one line (Termwind wraps at the terminal's width).
        putenv('COLUMNS=300');
        $this->root = sys_get_temp_dir().'/acun-install-'.getmypid().'-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->root.'/vendor/acunsoft/acun-ui');
        $this->basePath = $this->app->basePath();
        // Blade reads the application's namespace from composer.json: read (and cached) before the switch.
        $this->app->getNamespace();
        $this->app->setBasePath($this->root);
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->basePath);
        File::deleteDirectory($this->root);
        putenv('COLUMNS');

        parent::tearDown();
    }

    public function test_a_fresh_application_gets_every_line(): void
    {
        $this->skeleton();

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        self::assertSame(<<<'JSON'
            {
                "$schema": "https://www.schemastore.org/package.json",
                "private": true,
                "type": "module",
                "scripts": {
                    "build": "vite build",
                    "dev": "vite"
                },
                "devDependencies": {
                    "@tailwindcss/forms": "^0.5.11",
                    "@tailwindcss/vite": "^4.1.0",
                    "concurrently": "^10.0.3",
                    "laravel-vite-plugin": "^3.1",
                    "tailwindcss": "^4.1.0",
                    "vite": "^8.0.0"
                },
                "dependencies": {
                    "@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui"
                }
            }

            JSON, $this->read('package.json'));
        self::assertSame(<<<'CSS'
            @import 'tailwindcss';
            @plugin '@tailwindcss/forms';
            @import '@acunsoft/acun-ui';

            @source '../../vendor/acunsoft/acun-ui/resources/views';
            @source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
            @source '../../storage/framework/views/*.php';
            @source '../**/*.blade.php';
            @source '../**/*.js';

            CSS, $this->read('resources/css/app.css'));
        self::assertSame("import './bootstrap';\nimport '@acunsoft/acun-ui';\n", $this->read('resources/js/app.js'));

        self::assertMatchesRegularExpression('~package\.json \.+ updated~', $output);
        self::assertStringContainsString('+ "@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui" (dependencies)', $output);
        self::assertStringContainsString('+ "@tailwindcss/forms": "^0.5.11" (devDependencies)', $output);
        self::assertStringContainsString("+ @plugin '@tailwindcss/forms';", $output);
        self::assertStringContainsString('+ '.self::SOURCE_LINE, $output);
        self::assertStringContainsString("+ import '@acunsoft/acun-ui';", $output);
        self::assertStringNotContainsString("+ @import 'tailwindcss';", $output, 'Present lines are not added again.');
        self::assertStringContainsString('Changed: package.json, resources/css/app.css, resources/js/app.js.', $output);
        self::assertStringContainsString("Use <acun:layout.blank> on a page (or <acun:layout.head> in your own layout), with @vite(['resources/css/app.css', 'resources/js/app.js'])", $output);
        self::assertStringContainsString('Run npm install, then npm run dev', $output);
        self::assertStringContainsString('https://ui.acunsoft.com/installation.html#free', $output);
        self::assertStringNotContainsString('Acun Pro', $output);
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->skeleton(theme: true);
        $this->acun();
        $files = $this->snapshot();

        foreach (array_keys($files) as $file) {
            touch($this->root.'/'.$file, time() - 3600);
        }
        clearstatcache();
        $times = array_map(fn (string $file): int => filemtime($this->root.'/'.$file), array_combine(array_keys($files), array_keys($files)));

        [$exit, $output] = $this->acun();
        clearstatcache();

        self::assertSame(0, $exit, $output);
        self::assertSame($files, $this->snapshot());
        self::assertSame($times, array_map(fn (string $file): int => filemtime($this->root.'/'.$file), array_combine(array_keys($files), array_keys($files))), 'No file is written.');
        self::assertStringContainsString('Already set up. Nothing changed.', $output);
        self::assertMatchesRegularExpression('~resources/css/app\.css \.+ unchanged~', $output);
        self::assertStringNotContainsString('updated', $output);
        self::assertStringNotContainsString(' + ', $output);
        self::assertStringNotContainsString('Next', $output);
        self::assertStringContainsString('an @theme block sets --font-sans', $output, 'The font warning stays while the line does.');
    }

    public function test_a_partial_setup_gets_only_the_missing_lines(): void
    {
        // Two-space JSON, Windows line endings in the CSS, a JS entry that imports the kit its own way.
        $this->write('package.json', "{\n  \"name\": \"shop\",\n  \"dependencies\": {\n    \"@acunsoft/acun-ui\": \"file:./vendor/acunsoft/acun-ui\",\n    \"apexcharts\": \"^3.54.1\"\n  },\n  \"devDependencies\": {\n    \"vite\": \"^8.0.0\"\n  }\n}");
        $this->write('resources/css/app.css', implode("\r\n", [
            '/* Shop styles */',
            '@import "tailwindcss";',
            "@import '@acunsoft/acun-ui';",
            '',
            "@source '../**/*.blade.php';",
            '',
            '.price { font-variant-numeric: tabular-nums; }',
            '',
        ]));
        $js = "import { modal } from '@acunsoft/acun-ui';\n\ndocument.querySelector('[data-cart]')?.addEventListener('click', () => modal.open('cart'));\n";
        $this->write('resources/js/app.js', $js);

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        self::assertSame("{\n  \"name\": \"shop\",\n  \"dependencies\": {\n    \"@acunsoft/acun-ui\": \"file:./vendor/acunsoft/acun-ui\",\n    \"apexcharts\": \"^3.54.1\"\n  },\n  \"devDependencies\": {\n    \"@tailwindcss/forms\": \"^0.5.11\",\n    \"vite\": \"^8.0.0\"\n  }\n}", $this->read('package.json'), 'The link written with ./ counts; the file keeps its indentation and its missing final newline.');
        self::assertSame(implode("\r\n", [
            '/* Shop styles */',
            '@import "tailwindcss";',
            "@plugin '@tailwindcss/forms';",
            "@import '@acunsoft/acun-ui';",
            '',
            self::SOURCE_LINE,
            "@source '../**/*.blade.php';",
            '',
            '.price { font-variant-numeric: tabular-nums; }',
            '',
        ]), $this->read('resources/css/app.css'));
        self::assertSame($js, $this->read('resources/js/app.js'));

        self::assertMatchesRegularExpression('~resources/js/app\.js \.+ unchanged~', $output);
        self::assertStringNotContainsString('+ "@acunsoft/acun-ui"', $output);
        self::assertStringNotContainsString("+ @import '@acunsoft/acun-ui';", $output);
        self::assertStringContainsString('Changed: package.json, resources/css/app.css.', $output);
    }

    public function test_a_line_in_a_comment_does_not_count(): void
    {
        $this->skeleton();
        $this->write('resources/css/app.css', "@import 'tailwindcss';\n/* @import '@acunsoft/acun-ui'; */\n");
        $this->write('resources/js/app.js', "// import '@acunsoft/acun-ui';\nimport './bootstrap';\n");

        $this->acun();

        self::assertSame("@import 'tailwindcss';\n@plugin '@tailwindcss/forms';\n@import '@acunsoft/acun-ui';\n\n".self::SOURCE_LINE."\n\n/* @import '@acunsoft/acun-ui'; */\n", $this->read('resources/css/app.css'));
        self::assertSame("// import '@acunsoft/acun-ui';\nimport './bootstrap';\nimport '@acunsoft/acun-ui';\n", $this->read('resources/js/app.js'));
    }

    public function test_a_theme_block_with_a_sans_font_is_left_alone_with_a_warning(): void
    {
        $this->skeleton(theme: true);

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        $css = $this->read('resources/css/app.css');
        self::assertStringEndsWith(self::THEME, $css, 'The @theme block is not changed.');
        self::assertStringStartsWith("@import 'tailwindcss';\n@plugin '@tailwindcss/forms';\n@import '@acunsoft/acun-ui';\n\n".self::SOURCE_LINE."\n", $css);
        self::assertStringContainsString("resources/css/app.css line 12: an @theme block sets --font-sans, which replaces Acun UI's fonts (Public Sans, DM Mono). Remove that line (the whole block if nothing else is in it) unless you want your own font. acun:install leaves it as it is.", $output);
    }

    public function test_check_changes_nothing_and_lists_what_is_missing(): void
    {
        $this->skeleton(theme: true);
        $files = $this->snapshot();

        [$exit, $output] = $this->acun(['--check' => true]);

        self::assertSame(1, $exit, $output);
        self::assertSame($files, $this->snapshot(), '--check writes nothing.');
        self::assertStringContainsString('✓ vendor/acunsoft/acun-ui is installed', $output);
        self::assertStringContainsString('✗ package.json: "@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui" (dependencies)', $output);
        self::assertStringContainsString('→ Missing: php artisan acun:install adds it, then run npm install.', $output);
        self::assertStringContainsString("✓ resources/css/app.css: @import 'tailwindcss';", $output);
        self::assertStringContainsString('✗ resources/css/app.css: '.self::SOURCE_LINE, $output);
        self::assertStringContainsString("✗ resources/js/app.js: import '@acunsoft/acun-ui';", $output);
        self::assertStringContainsString('! resources/css/app.css line 9: an @theme block sets --font-sans', $output);
        self::assertStringContainsString('✗ node_modules/@acunsoft/acun-ui does not point to vendor/acunsoft/acun-ui', $output);
        self::assertStringContainsString('→ Run npm install (after composer install: npm takes the package from vendor/).', $output);
        self::assertStringContainsString('✗ No build: public/build/manifest.json and public/hot are missing', $output);
        self::assertStringContainsString('8 things are missing: fix them as shown above.', $output);
        self::assertStringContainsString('Guide: https://ui.acunsoft.com/installation.html#free', $output);
    }

    public function test_check_passes_on_a_complete_setup_and_names_a_missing_source(): void
    {
        $this->skeleton(theme: true);
        $this->acun();
        $this->linkNodeModules('acun-ui');
        $this->write('public/build/manifest.json', '{}');

        [$exit, $output] = $this->acun(['--check' => true]);

        self::assertSame(0, $exit, $output);
        self::assertStringContainsString('✓ resources/css/app.css: '.self::SOURCE_LINE, $output);
        self::assertStringContainsString('✓ node_modules/@acunsoft/acun-ui points to vendor/acunsoft/acun-ui', $output);
        self::assertStringContainsString('✓ public/build/manifest.json: the assets are built', $output);
        self::assertStringContainsString('! resources/css/app.css line 12: an @theme block sets --font-sans', $output, 'A note, not a failure.');
        self::assertStringNotContainsString('✗', $output);
        self::assertStringContainsString('Acun UI is set up.', $output);

        $this->write('resources/css/app.css', str_replace(self::SOURCE_LINE."\n", '', $this->read('resources/css/app.css')));
        File::delete($this->root.'/public/build/manifest.json');
        $this->write('public/hot', 'http://localhost:5173');

        [$exit, $output] = $this->acun(['--check' => true]);

        self::assertSame(1, $exit, $output);
        self::assertStringContainsString('✗ resources/css/app.css: '.self::SOURCE_LINE."\n    → Missing: php artisan acun:install adds it.", $output);
        self::assertStringContainsString('✓ public/hot: the Vite dev server is running', $output);
        self::assertStringContainsString('1 thing is missing: fix it as shown above.', $output);
    }

    public function test_without_interaction_there_is_no_example_page_and_no_package_manager(): void
    {
        Process::fake();
        $this->skeleton();

        [$exit] = $this->acun();

        self::assertSame(0, $exit);
        Process::assertNothingRan();
        self::assertFileDoesNotExist($this->root.'/resources/views/acun/example.blade.php');
    }

    public function test_npm_runs_the_install_and_the_build_of_the_lock_files_manager(): void
    {
        Process::fake();
        $this->skeleton();

        [$exit, $output] = $this->acun(['--npm' => true]);

        self::assertSame(0, $exit, $output);
        Process::assertRan(fn ($process) => $process->command === 'npm install' && $process->path === $this->root);
        Process::assertRan('npm run build');
        self::assertStringContainsString('$ npm install', $output);
        self::assertStringContainsString('Run npm run dev while you work', $output);

        $this->write('pnpm-lock.yaml', "lockfileVersion: '9.0'\n");
        $this->acun(['--npm' => true]);

        Process::assertRan('pnpm install');
        Process::assertRan('pnpm run build');

        $this->acun(['--no-npm' => true]);
        Process::assertRanTimes('pnpm install', 1);
    }

    public function test_a_failing_package_manager_is_reported(): void
    {
        Process::fake(['npm install' => Process::result(errorOutput: 'npm ERR! code ENOENT', exitCode: 1)]);
        $this->skeleton();

        [$exit, $output] = $this->acun(['--npm' => true]);

        self::assertSame(1, $exit);
        self::assertStringContainsString('npm install failed (exit code 1). Fix the error above, then run it again.', $output);
        Process::assertDidntRun('npm run build');
        self::assertStringContainsString('Changed: package.json, resources/css/app.css, resources/js/app.js.', $output, 'The files are wired before npm runs.');
    }

    public function test_interactive_runs_ask_for_the_example_page_and_the_package_manager(): void
    {
        Process::fake();
        $this->skeleton();

        $this->artisan('acun:install')
            ->expectsConfirmation('Copy an example page to resources/views/acun/example.blade.php?', 'yes')
            ->expectsConfirmation('Run npm install and npm run build now?', 'no')
            ->expectsOutputToContain("Route::view('/acun-example', 'acun.example');")
            ->assertExitCode(0);

        self::assertFileExists($this->root.'/resources/views/acun/example.blade.php');
        Process::assertNothingRan();
    }

    public function test_the_example_page_renders_in_the_applications_language(): void
    {
        $this->withoutVite();
        $this->skeleton();
        $page = $this->root.'/resources/views/acun/example.blade.php';

        [$exit, $output] = $this->acun(['--example' => true]);

        self::assertSame(0, $exit, $output);
        self::assertStringContainsString('Changed: package.json, resources/css/app.css, resources/js/app.js, resources/views/acun/example.blade.php.', $output);
        self::assertStringContainsString("@vite(['resources/css/app.css', 'resources/js/app.js'])", File::get($page));
        $html = Blade::render(File::get($page));
        self::assertStringContainsString('<title>Siparişler</title>', $html);
        self::assertStringContainsString('Ödendi', $html);
        self::assertStringContainsString('data-test="theme-toggle"', $html);

        File::put($page, 'kept');
        [, $output] = $this->acun(['--example' => true]);
        self::assertSame('kept', File::get($page), 'An existing page is never overwritten.');
        self::assertMatchesRegularExpression('~resources/views/acun/example\.blade\.php \.+ exists, kept~', $output);

        File::delete($page);
        $this->app->setLocale('en');
        $this->acun(['--example' => true]);
        $html = Blade::render(File::get($page));
        self::assertStringContainsString('<title>Orders</title>', $html);
        self::assertStringContainsString('Ship order', $html);
    }

    public function test_with_acun_pro_installed_its_lines_are_added_and_the_rest_is_listed(): void
    {
        File::ensureDirectoryExists($this->root.'/vendor/acunsoft/acun-pro');
        $this->skeleton();

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        self::assertStringContainsString("\"dependencies\": {\n        \"@acunsoft/acun-pro\": \"file:vendor/acunsoft/acun-pro\",\n        \"@acunsoft/acun-ui\": \"file:vendor/acunsoft/acun-ui\"\n    }", $this->read('package.json'));
        self::assertStringStartsWith(implode("\n", [
            "@import 'tailwindcss';",
            "@plugin '@tailwindcss/forms';",
            "@import '@acunsoft/acun-ui';",
            "@import '@acunsoft/acun-pro';",
            '',
            self::SOURCE_LINE,
            "@source '../../vendor/acunsoft/acun-pro/resources/views/components/ui';",
            "@source '../../vendor/laravel/",
        ]), $this->read('resources/css/app.css'));
        self::assertSame("import './bootstrap';\nimport '@acunsoft/acun-ui';\nimport '@acunsoft/acun-pro';\n", $this->read('resources/js/app.js'));

        self::assertStringContainsString('Acun Pro: the rest is by hand, see https://ui.acunsoft.com/installation.html#existing', $output);
        self::assertStringContainsString('vite.config.js: the fonts (Public Sans, DM Mono) and woff2Only()', $output);
        self::assertStringContainsString('The layout: resources/views/layouts/admin/vertical.blade.php', $output);
        self::assertStringContainsString('The menu: resources/menu/main.json', $output);
        self::assertStringContainsString('The first page and its route in routes/web.php', $output);
        self::assertStringNotContainsString('<acun:layout.blank>', $output, 'The panel layout comes instead.');

        [, $output] = $this->acun();
        self::assertStringContainsString('Already set up. Nothing changed.', $output);
        self::assertStringContainsString('Acun Pro: Vite, the layout, the menu and the first page are set by hand: https://ui.acunsoft.com/installation.html#existing', $output);

        $this->linkNodeModules('acun-ui');
        $this->write('public/build/manifest.json', '{}');
        [$exit, $output] = $this->acun(['--check' => true]);
        self::assertSame(1, $exit, $output);
        self::assertStringContainsString('✓ vendor/acunsoft/acun-pro is installed (its lines are checked too)', $output);
        self::assertStringContainsString("✓ resources/js/app.js: import '@acunsoft/acun-pro';", $output);
        self::assertStringContainsString('✗ node_modules/@acunsoft/acun-pro does not point to vendor/acunsoft/acun-pro', $output);

        $this->linkNodeModules('acun-pro');
        self::assertSame(0, $this->acun(['--check' => true])[0]);
    }

    public function test_the_starter_kits_entries_are_used_and_any_import_of_pro_counts(): void
    {
        File::ensureDirectoryExists($this->root.'/vendor/acunsoft/acun-pro');
        $this->write('package.json', "{\n    \"dependencies\": {\n        \"@acunsoft/acun-pro\": \"file:vendor/acunsoft/acun-pro\",\n        \"@acunsoft/acun-ui\": \"file:vendor/acunsoft/acun-ui\"\n    },\n    \"devDependencies\": {\n        \"@tailwindcss/forms\": \"^0.5.11\"\n    }\n}\n");
        $css = "@import 'tailwindcss';\n@plugin '@tailwindcss/forms';\n@import '@acunsoft/acun-ui';\n@import '@acunsoft/acun-pro';\n\n@source '../../vendor/acunsoft/acun-ui/resources/views';\n@source '../../vendor/acunsoft/acun-pro/resources/views/components/ui';\n";
        $this->write('resources/css/acun-ui.css', $css);
        $this->write('resources/js/acun-ui.js', "import '@acunsoft/acun-ui';\n// PRO: the chart.\nimport { registerChart } from '@acunsoft/acun-pro';\n");
        $this->write('resources/js/app.js', "// The application's own code.\n");
        $this->write('resources/css/app.css', '');
        $files = $this->snapshot();

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        self::assertSame($files, $this->snapshot(), 'app.css and app.js are not the entries here.');
        self::assertMatchesRegularExpression('~resources/css/acun-ui\.css \.+ unchanged~', $output);
        self::assertMatchesRegularExpression('~resources/js/acun-ui\.js \.+ unchanged~', $output);
        self::assertStringContainsString('Already set up. Nothing changed.', $output);
    }

    public function test_a_missing_package_json_is_not_created(): void
    {
        $this->skeleton();
        File::delete($this->root.'/package.json');

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        self::assertFileDoesNotExist($this->root.'/package.json');
        self::assertStringContainsString('package.json was not found: nothing was created. Create it (npm init -y), then run php artisan acun:install again.', $output);
        self::assertStringContainsString('Changed: resources/css/app.css, resources/js/app.js.', $output);

        [$exit, $output] = $this->acun(['--npm' => true]);
        self::assertSame(1, $exit);
        self::assertStringContainsString('package.json was not found: npm did not run.', $output);
        self::assertStringContainsString('Nothing changed.', $output);
        self::assertStringNotContainsString('Already set up', $output);

        [$exit, $output] = $this->acun(['--check' => true]);
        self::assertSame(1, $exit);
        self::assertStringContainsString('✗ package.json is missing', $output);
    }

    public function test_another_npm_version_and_broken_json_are_reported_not_changed(): void
    {
        $this->skeleton();
        $json = "{\n    \"dependencies\": {\n        \"@acunsoft/acun-ui\": \"^2.0.1\"\n    },\n    \"devDependencies\": {\n        \"@tailwindcss/forms\": \"^0.5.11\"\n    }\n}\n";
        $this->write('package.json', $json);

        [$exit, $output] = $this->acun();

        self::assertSame(0, $exit, $output);
        self::assertSame($json, $this->read('package.json'));
        self::assertStringContainsString('package.json: "@acunsoft/acun-ui" is "^2.0.1", left as it is. In a Laravel application it comes from the Composer copy, "file:vendor/acunsoft/acun-ui", so the CSS and JS match the Blade components: change it, then run npm install.', $output);

        [$exit, $output] = $this->acun(['--check' => true]);
        self::assertSame(1, $exit);
        self::assertStringContainsString('✗ package.json: "@acunsoft/acun-ui" is "^2.0.1"', $output);
        self::assertStringContainsString('→ Set it to "file:vendor/acunsoft/acun-ui" so the CSS and JS match the Blade components, then run npm install.', $output);

        $this->write('package.json', '{ "name": ');
        [$exit, $output] = $this->acun();
        self::assertSame(0, $exit);
        self::assertSame('{ "name": ', $this->read('package.json'));
        self::assertStringContainsString('package.json: not valid JSON (Syntax error) Nothing was changed in it.', $output);
    }

    public function test_npm_and_no_npm_together_stop(): void
    {
        $this->skeleton();
        $files = $this->snapshot();

        [$exit, $output] = $this->acun(['--npm' => true, '--no-npm' => true]);

        self::assertSame(1, $exit);
        self::assertStringContainsString('Pass --npm or --no-npm, not both.', $output);
        self::assertSame($files, $this->snapshot());
    }

    /** @return array{0: int, 1: string} the exit code and the output, without prompts */
    private function acun(array $parameters = []): array
    {
        $exit = Artisan::call('acun:install', $parameters + ['--no-interaction' => true]);

        return [$exit, Artisan::output()];
    }

    private function skeleton(bool $theme = false): void
    {
        $this->write('package.json', self::PACKAGE);
        $this->write('resources/css/app.css', self::SOURCES.($theme ? self::THEME : ''));
        $this->write('resources/js/app.js', "import './bootstrap';\n");
    }

    private function write(string $file, string $content): void
    {
        File::ensureDirectoryExists(dirname($this->root.'/'.$file));
        File::put($this->root.'/'.$file, $content);
    }

    private function read(string $file): string
    {
        return File::get($this->root.'/'.$file);
    }

    /** npm's link of a file: dependency. */
    private function linkNodeModules(string $package): void
    {
        File::ensureDirectoryExists($this->root.'/vendor/acunsoft/'.$package);
        File::ensureDirectoryExists($this->root.'/node_modules/@acunsoft');
        symlink('../../vendor/acunsoft/'.$package, $this->root.'/node_modules/@acunsoft/'.$package);
    }

    /** @return array<string, string> every file of the application folder by its path */
    private function snapshot(): array
    {
        $files = [];

        foreach (File::allFiles($this->root) as $file) {
            $files[str_replace('\\', '/', $file->getRelativePathname())] = $file->getContents();
        }

        ksort($files);

        return $files;
    }
}
