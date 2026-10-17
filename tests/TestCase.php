<?php

namespace Acun\Ui\Tests;

use Acun\Ui\AcunUiServiceProvider;
use Acun\Ui\Tests\Concerns\CompilesViewsFresh;
use Acun\Ui\Tests\Concerns\DecodesUnicodeEscapes;
use Acun\Ui\Tests\Concerns\ReadsRuntimeConfig;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use CompilesViewsFresh, DecodesUnicodeEscapes, ReadsRuntimeConfig;

    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, AcunUiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('view.compiled', self::freshCompiledViews());
        // The tests read the Turkish interface (lang/tr.json).
        $app['config']->set('app.locale', 'tr');
    }
}
