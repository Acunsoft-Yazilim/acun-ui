<?php

namespace Acun\Ui\Tests;

use Acun\Ui\AcunUiServiceProvider;
use Acun\Ui\Tests\Concerns\CompilesViewsFresh;
use Acun\Ui\Tests\Concerns\DecodesUnicodeEscapes;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use CompilesViewsFresh, DecodesUnicodeEscapes;

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

    /**
     * The settings acun:layout.head prints for the JS runtime (its config island).
     *
     * @return array<string, mixed>
     */
    protected function runtimeConfig(string $html): array
    {
        self::assertSame(1, preg_match('~<script type="application/json" data-acun-ui-config>(.*?)</script>~s', $html, $match), 'The page has one config island.');

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
