<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\AcunUiServiceProvider;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\Attributes\DefineEnvironment;

/**
 * "acun" is reserved for the kit's components: a view, anonymous component or class component
 * namespace (or an alias) under that name would win over the kit's folders and silently replace
 * <acun:*> components, so the provider logs a warning about it once everything has booted.
 */
final class ReservedNamespaceTest extends TestCase
{
    public function test_the_kit_alone_takes_nothing_over(): void
    {
        self::assertSame([], AcunUiServiceProvider::namespaceConflicts());
    }

    public function test_a_takeover_does_replace_a_kit_component_and_is_reported(): void
    {
        // What the check guards against: the application's view namespace wins over the kit's folder.
        $directory = sys_get_temp_dir().'/acun-ui-takeover-'.getmypid();
        @mkdir($directory.'/components', recursive: true);
        file_put_contents($directory.'/components/badge.blade.php', '<span>sahte rozet</span>');
        View::addNamespace('acun', $directory);

        try {
            self::assertStringContainsString('sahte rozet', Blade::render('<acun:badge>Ele geçirildi</acun:badge>', deleteCachedView: true));
            self::assertSame(["the view namespace [acun] ({$directory})"], AcunUiServiceProvider::namespaceConflicts());
        } finally {
            @unlink($directory.'/components/badge.blade.php');
            @rmdir($directory.'/components');
            @rmdir($directory);
        }
    }

    public function test_every_kind_of_takeover_is_listed_and_logged(): void
    {
        View::addNamespace('acun', __DIR__);
        Blade::anonymousComponentNamespace('benim.bilesenler', 'acun');
        Blade::componentNamespace('App\\View\\Components\\Acun', 'acun');
        Blade::component('App\\View\\Components\\Rozet', 'acun::badge');

        self::assertSame([
            'the view namespace [acun] ('.__DIR__.')',
            'the anonymous component namespace [acun] (benim.bilesenler)',
            'the class component namespace [acun] (App\\View\\Components\\Acun)',
            'the component aliases [acun::badge]',
        ], AcunUiServiceProvider::namespaceConflicts());

        Log::spy();
        AcunUiServiceProvider::warnAboutNamespaceTakeovers();

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_starts_with($message, '[Acun UI] The "acun" component namespace is reserved for the kit')
            && str_contains($message, 'the view namespace [acun]')
            && str_contains($message, 'vendor:publish --tag=acun-ui-views'));
    }

    public function test_a_component_folder_added_to_the_namespace_is_not_a_takeover(): void
    {
        // How the kit's packages (PRO, e-commerce) join it; a later folder only adds names.
        Blade::anonymousComponentPath(__DIR__, 'acun');
        // Other names are not the kit's business.
        View::addNamespace('acun-panel', __DIR__);
        Blade::componentNamespace('App\\View\\Components\\Panel', 'panel');

        self::assertSame([], AcunUiServiceProvider::namespaceConflicts());

        Log::spy();
        AcunUiServiceProvider::warnAboutNamespaceTakeovers();
        Log::shouldNotHaveReceived('warning');
    }

    protected function takeOverTheNamespace($app): void
    {
        Log::spy();
        $app['view']->addNamespace('acun', __DIR__);
    }

    #[DefineEnvironment('takeOverTheNamespace')]
    public function test_the_check_runs_once_the_application_has_booted(): void
    {
        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'the view namespace [acun]'));
    }
}
