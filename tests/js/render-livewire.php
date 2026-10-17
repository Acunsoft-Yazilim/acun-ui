<?php

/*
 * Renders real Livewire flows for the JS behaviour tests: reads a JSON object of
 * { key: component } from stdin (single-file components in tests/Fixtures/livewire) and
 * prints, for each page, what Livewire sends to the browser:
 *     { key: { first: html, updates: { component name: html } } }
 * first: the page's first render (every nested component drawn whole, with its snapshot);
 * updates: each component's own update ($refresh), from the snapshot of the first render.
 * A parent's update leaves an empty stub where a nested component is (Livewire morphs the
 * nested component's DOM back in, see livewire.mjs).
 */

use Acun\Ui\AcunUiServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../../../../vendor/autoload.php';

// Every run compiles into its own directory, so parallel test files do not share compiled views.
$compiled = sys_get_temp_dir().'/acun-js-livewire-views-'.getmypid();
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
Livewire::addLocation(viewPath: __DIR__.'/../Fixtures/livewire');

$pages = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);

echo json_encode(array_map(function (string $component) {
    $first = Livewire::mount($component);

    // The snapshots the browser keeps: the page's and those of its nested components.
    preg_match_all('/\swire:snapshot="([^"]*)"/', $first, $matches);
    $updates = [];

    foreach ($matches[1] as $encoded) {
        $snapshot = json_decode(htmlspecialchars_decode($encoded, ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);
        [, $effects] = app('livewire')->update($snapshot, [], [['method' => '$refresh', 'params' => [], 'path' => '']]);
        $updates[$snapshot['memo']['name']] = $effects['html'];
    }

    return ['first' => $first, 'updates' => $updates];
}, $pages), JSON_THROW_ON_ERROR);
