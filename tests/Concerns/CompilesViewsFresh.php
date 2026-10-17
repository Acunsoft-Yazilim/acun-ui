<?php

namespace Acun\Ui\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;

/**
 * Every test run compiles Blade into its own directory. Testbench's shared one keeps the
 * compile of a Blade::render() string from an earlier run, which hid a changed directive
 * (@acunUiScripts) or tag compiler behind an old result.
 */
trait CompilesViewsFresh
{
    private static ?string $compiledViews = null;

    protected static function freshCompiledViews(): string
    {
        if (self::$compiledViews === null) {
            $directory = sys_get_temp_dir().'/acun-ui-tests-'.getmypid();
            (new Filesystem)->ensureDirectoryExists($directory);
            register_shutdown_function(fn () => (new Filesystem)->deleteDirectory($directory));
            self::$compiledViews = $directory;
        }

        return self::$compiledViews;
    }
}
