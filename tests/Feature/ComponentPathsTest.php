<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\AcunUi;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

/**
 * AcunUi::addComponentPath(): the component folders of the acun prefix, published copies before
 * every package folder in whatever order they are added (a package that boots first cannot put
 * its folder ahead of a copy the application published).
 */
final class ComponentPathsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/acun-component-paths-'.uniqid();
        foreach (['package' => 'from the package', 'published' => 'published copy', 'other' => 'other folder'] as $folder => $text) {
            File::ensureDirectoryExists("{$this->directory}/{$folder}");
            File::put("{$this->directory}/{$folder}/rozet-test.blade.php", "<b>{$text}</b>");
        }
        File::put("{$this->directory}/other/yalniz-burada.blade.php", '<i>only here</i>');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_a_published_folder_added_last_still_comes_first(): void
    {
        AcunUi::addComponentPath($this->directory.'/package');
        AcunUi::addComponentPath($this->directory.'/published', published: true);

        self::assertSame('<b>published copy</b>', trim(Blade::render('<acun:rozet-test />')));
        self::assertSame($this->directory.'/published', AcunUi::componentPaths()[0]);
        self::assertContains($this->directory.'/package', AcunUi::componentPaths());
        // The kit's own components are still there, after the published copies.
        self::assertStringContainsString('<span', Blade::render('<acun:badge>Yeni</acun:badge>'));
    }

    public function test_package_folders_keep_their_order_after_the_kit(): void
    {
        AcunUi::addComponentPath($this->directory.'/package');
        AcunUi::addComponentPath($this->directory.'/other');

        self::assertSame('<b>from the package</b>', trim(Blade::render('<acun:rozet-test />')));
        self::assertSame(['kit', 'package', 'other'], array_map(
            fn (string $path): string => match (true) {
                str_ends_with($path, '/package') => 'package',
                str_ends_with($path, '/other') => 'other',
                default => 'kit',
            },
            AcunUi::componentPaths(),
        ));
    }

    /** A folder another package or the application added itself (Blade::anonymousComponentPath) only adds names. */
    public function test_a_folder_added_directly_stays_behind_the_registered_ones(): void
    {
        Blade::anonymousComponentPath($this->directory.'/other', 'acun');
        AcunUi::addComponentPath($this->directory.'/package');

        self::assertSame('<b>from the package</b>', trim(Blade::render('<acun:rozet-test />')));
        self::assertSame('<i>only here</i>', trim(Blade::render('<acun:yalniz-burada />')));
        self::assertNotContains($this->directory.'/other', AcunUi::componentPaths());
    }

    public function test_a_missing_folder_or_one_added_twice_changes_nothing(): void
    {
        $before = AcunUi::componentPaths();

        AcunUi::addComponentPath($this->directory.'/missing', published: true);
        AcunUi::addComponentPath($before[0]);

        self::assertSame($before, AcunUi::componentPaths());
    }
}
