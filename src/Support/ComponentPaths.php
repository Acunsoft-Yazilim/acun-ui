<?php

namespace Acun\Ui\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * The component folders of the kit's tag prefix (<acun:x> is <x-acun::x>), in lookup order: every
 * published copy (vendor:publish) before every package, whichever provider registers its folders
 * first. One per application: Acun\Ui\AcunUi::addComponentPath() fills it.
 *
 * Laravel looks an anonymous component up in the folders of its prefix in the order they were
 * added, so without this a package that boots before another would put its own folder ahead of a
 * copy the application published for a component that has moved between the two packages (a 1.x
 * copy of layout/sidebar under resources/views/vendor/acun-ui, now in acunsoft/acun-pro).
 */
final class ComponentPaths
{
    /** @var list<string> */
    private array $published = [];

    /** @var list<string> */
    private array $packages = [];

    /**
     * Adds a folder (a missing one is skipped, one already added is kept where it is) and puts the
     * prefix's folders back in order.
     */
    public function add(string $path, bool $published = false): void
    {
        $path = rtrim($path, '/\\');

        if (! is_dir($path) || in_array($path, $this->all(), true)) {
            return;
        }

        if ($published) {
            $this->published[] = $path;
        } else {
            $this->packages[] = $path;
        }

        Blade::anonymousComponentPath($path, AcunTagCompiler::PREFIX);
        $this->order();
    }

    /**
     * The folders, in lookup order: the published copies, then the packages.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return [...$this->published, ...$this->packages];
    }

    /** Whether a component folder has <acun:{name}>: name, name/index or name/{last part}, as Laravel looks it up. */
    public function resolves(string $name): bool
    {
        $namespace = self::namespace();

        if ($namespace === null) {
            return false;
        }

        foreach ([$name, $name.'.index', $name.'.'.Str::afterLast($name, '.')] as $view) {
            if (View::exists($namespace.'::'.$view)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rewrites the prefix's view namespace: these folders first, in their order, then any folder
     * other code added itself with Blade::anonymousComponentPath(…, 'acun'), which only adds names.
     */
    private function order(): void
    {
        $namespace = self::namespace();
        $finder = View::getFinder();
        $ours = $this->all();
        $others = array_values(array_diff($finder->getHints()[$namespace] ?? [], $ours));

        View::replaceNamespace($namespace, [...$ours, ...$others]);
        // Lookups made before this point are cached by name; they may now find another file.
        $finder->flush();
    }

    /** The view namespace Blade keeps the prefix's folders under (a hash of the prefix). */
    private static function namespace(): ?string
    {
        foreach (Blade::getAnonymousComponentPaths() as $path) {
            if ($path['prefix'] === AcunTagCompiler::PREFIX) {
                return $path['prefixHash'];
            }
        }

        return null;
    }
}
