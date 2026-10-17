<?php

namespace Acun\Ui;

use Acun\Ui\Support\AcunTagCompiler;
use Acun\Ui\Support\Assets;
use Acun\Ui\Support\BladeComments;
use Acun\Ui\Support\ComponentPaths;
use Acun\Ui\Support\Fonts;
use Acun\Ui\Support\Layout;
use Acun\Ui\Support\PaidComponents;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

final class AcunUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Layout defaults and the asset settings under config('acun-ui').
        $this->mergeConfigFrom(__DIR__.'/../config/acun-ui.php', 'acun-ui');

        // Per-page layout settings (Layout::page()); scoped, so they reset on every request (Octane too).
        $this->app->scoped(Layout::class);

        // @acunUiScripts prints once per request.
        $this->app->scoped(Assets::class);

        // The component folders of the acun prefix (AcunUi::addComponentPath()), published copies first.
        $this->app->singletonIf(ComponentPaths::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'acun-ui');
        // The application's own lang/tr.json overrides the same keys.
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        // The kit's own component namespace: <acun:button> is <x-acun::button>, so a component of the
        // application (resources/views/components/ui/button.blade.php) never replaces a kit component.
        // Views copied with vendor:publish (resources/views/vendor/acun-ui/components/ui) come first,
        // before acunsoft/acun-pro's folders too: AcunUi::addComponentPath() keeps every published copy
        // ahead of every package. "acun" is reserved for the kit: see warnAboutNamespaceTakeovers().
        AcunUi::addComponentPath(resource_path('views/vendor/acun-ui/components/ui'), published: true);
        AcunUi::addComponentPath(__DIR__.'/../resources/views/components/ui');
        // The older <x-ui.*> names keep working; there an application component with the same name wins
        // (resources/views/components/ui/*), by design: only the acun namespace is the kit's alone.
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components');
        // The short tags: <acun:button> is <x-acun::button>, rewritten before Blade compiles the template;
        // lines holding only a Blade comment go too, so they leave no blank lines in the page source.
        // A tag of acunsoft/acun-pro (the panel layouts, the menu, the PRO components) stops with an
        // error that names the package when the application does not have it.
        Blade::prepareStringsForCompilationUsing(function (string $template): string {
            PaidComponents::ensureInstalled(AcunTagCompiler::names($template));

            return BladeComments::strip(AcunTagCompiler::compile($template));
        });

        $this->publishes([
            __DIR__.'/../config/acun-ui.php' => config_path('acun-ui.php'),
        ], 'acun-ui-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/acun-ui'),
        ], 'acun-ui-views');

        // The CSS and the JS runtime are this package's css/ and js/ (npm: @acunsoft/acun-ui). The JS
        // browser builds (dist/) are served from here: the boot script acun:layout.head loads, and the runtime for
        // applications without Vite (@acunUiScripts; acunsoft/acun-pro adds its own JS to it).
        $this->loadRoutesFrom(__DIR__.'/../routes/assets.php');
        Blade::directive('acunUiScripts', fn (string $expression): string => '<?php echo app(\\'.Assets::class.'::class)->scripts('.$expression.'); ?>');
        $this->publishes([
            __DIR__.'/../dist' => public_path(Assets::PUBLISHED_DIRECTORY),
        ], Assets::PUBLISH_TAG);

        // No preload tags on pages reached via wire:navigate: Livewire's <head> merge does not
        // count <link rel="preload"> as an asset, so it removed and re-added them on every visit
        // and the browser fetched fonts again that were in use ("preloaded but not used").
        // Registered once and asked per tag, so it covers the head's slot and holds under Octane.
        $this->callAfterResolving(Vite::class, function (Vite $vite): void {
            $vite->usePreloadTagAttributes(fn () => request()->hasHeader('X-Livewire-Navigate') ? false : []);
        });

        // Laravel's @fonts with the built fonts CSS as a cached file instead of an inline style block.
        Blade::directive('acunFonts', fn (string $expression): string => '<?php echo \\'.Fonts::class.'::tags('.$expression.'); ?>');

        // The paid components (acunsoft/acun-pro: the panel, PRO and shop) add their own folders to the
        // same acun namespace with AcunUi::addComponentPath().

        // Once every provider (the application's too) has registered its views and components.
        $this->app->booted(fn () => self::warnAboutNamespaceTakeovers());
    }

    /**
     * "acun" is reserved for the kit's components. Laravel resolves <x-acun::button> through a view
     * namespace, an anonymous component namespace, a class component namespace or an alias named
     * acun BEFORE the kit's component folders, so View::addNamespace('acun', …),
     * loadViewsFrom(…, 'acun'), Blade::anonymousComponentNamespace(…, 'acun'),
     * Blade::componentNamespace(…, 'acun') or Blade::component('acun::…', …) by the application or
     * another package would silently replace kit components. Such a registration is logged as a
     * warning. To change a kit component, publish its views (acun-ui-views); give your own
     * components another prefix. AcunUi::addComponentPath() is how the paid package
     * (acunsoft/acun-pro) joins the namespace; a folder added with Blade::anonymousComponentPath(…, 'acun')
     * comes after the kit's and only adds new names.
     */
    public static function warnAboutNamespaceTakeovers(): void
    {
        $conflicts = self::namespaceConflicts();

        if ($conflicts !== []) {
            Log::warning('[Acun UI] The "'.AcunTagCompiler::PREFIX.'" component namespace is reserved for the kit, but other code registered '
                .implode('; ', $conflicts).'. It can replace <'.AcunTagCompiler::PREFIX.':*> components. To change a kit component, publish its views'
                .' (php artisan vendor:publish --tag=acun-ui-views); give your own components another prefix.');
        }
    }

    /**
     * The registrations that take over the reserved acun namespace (see warnAboutNamespaceTakeovers()).
     *
     * @return list<string>
     */
    public static function namespaceConflicts(): array
    {
        $prefix = AcunTagCompiler::PREFIX;
        $conflicts = [];

        $finder = app('view')->getFinder();
        $hints = method_exists($finder, 'getHints') ? $finder->getHints() : [];
        if (isset($hints[$prefix])) {
            $conflicts[] = "the view namespace [{$prefix}] (".implode(', ', (array) $hints[$prefix]).')';
        }

        $anonymous = Blade::getAnonymousComponentNamespaces();
        if (isset($anonymous[$prefix])) {
            $conflicts[] = "the anonymous component namespace [{$prefix}] ({$anonymous[$prefix]})";
        }

        $classes = Blade::getClassComponentNamespaces();
        if (isset($classes[$prefix])) {
            $conflicts[] = "the class component namespace [{$prefix}] ({$classes[$prefix]})";
        }

        $aliases = array_values(array_filter(array_keys(Blade::getClassComponentAliases()), fn ($alias) => str_starts_with((string) $alias, $prefix.'::')));
        if ($aliases !== []) {
            $conflicts[] = 'the component aliases ['.implode(', ', $aliases).']';
        }

        return $conflicts;
    }
}
