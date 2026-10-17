<?php

namespace Acun\Ui;

use Acun\Ui\Support\ComponentPaths;
use Illuminate\Container\Container;

/**
 * What the packages built on the kit (acunsoft/acun-pro) and applications register with it.
 */
final class AcunUi
{
    /**
     * Adds a folder of components to the kit's tag prefix: <acun:x> (<x-acun::x>) is then looked up
     * there too. A published folder ($published true: copies made with vendor:publish, such as
     * resources/views/vendor/acun-ui/components/ui) is searched before every package folder,
     * whichever provider registers first; package folders keep the order they were added in. So a
     * published copy keeps replacing its component after the component has moved to another
     * package. A missing folder is skipped.
     *
     *     AcunUi::addComponentPath(resource_path('views/vendor/my-package/components'), published: true);
     *     AcunUi::addComponentPath(__DIR__.'/../resources/views/components');
     */
    public static function addComponentPath(string $path, bool $published = false): void
    {
        self::registry()->add($path, $published);
    }

    /**
     * The folders of the tag prefix, in lookup order (published copies first).
     *
     * @return list<string>
     */
    public static function componentPaths(): array
    {
        return self::registry()->all();
    }

    /** The free package's resources/views folder (view namespace acun-ui). */
    public static function viewPath(): string
    {
        return dirname(__DIR__).'/resources/views';
    }

    private static function registry(): ComponentPaths
    {
        $app = Container::getInstance();

        // Bound by AcunUiServiceProvider; a package that registers before it gets the same instance.
        $app->singletonIf(ComponentPaths::class);

        return $app->make(ComponentPaths::class);
    }
}
