<?php

/*
 * Renders Blade for the JS behaviour tests: reads a JSON object of
 * { key: template } from stdin and prints { key: html }. The tests then run
 * the markup with Livewire's own Alpine (see browser.mjs).
 */

use Acun\Ui\AcunUiServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../../../../vendor/autoload.php';

// Every run compiles into its own directory, so parallel test files do not share compiled views.
$compiled = sys_get_temp_dir().'/acun-js-views-'.getmypid();
(new Filesystem)->ensureDirectoryExists($compiled);
putenv('VIEW_COMPILED_PATH='.$compiled);
$_ENV['VIEW_COMPILED_PATH'] = $_SERVER['VIEW_COMPILED_PATH'] = $compiled;
register_shutdown_function(fn () => (new Filesystem)->deleteDirectory($compiled));

$app = Application::create(options: ['load_environment_variables' => false]);
$app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
// The browser tests read the Turkish interface, like the PHP tests (lang/tr.json).
$app->setLocale('tr');
$app->register(LivewireServiceProvider::class);
$app->register(AcunUiServiceProvider::class);
View::share('errors', new ViewErrorBag);

$templates = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);

echo json_encode(array_map(fn (string $template) => Blade::render($template), $templates), JSON_THROW_ON_ERROR);
