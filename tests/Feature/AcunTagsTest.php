<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\AcunTagCompiler;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

/**
 * The short tag syntax <acun:*>: the same components and output as <x-ui.*>.
 */
final class AcunTagsTest extends TestCase
{
    /** Each short template renders exactly what its <x-ui.*> twin renders. */
    public function test_short_tags_render_the_same_as_x_ui_tags(): void
    {
        $pairs = [
            'paired tag' => ['<acun:button variant="danger">Sil</acun:button>', '<x-ui.button variant="danger">Sil</x-ui.button>'],
            'self-closing tag' => ['<acun:icon name="home" size="4" />', '<x-ui.icon name="home" size="4" />'],
            'bound attribute with ">"' => ['<acun:button :disabled="$count > 3">Gönder</acun:button>', '<x-ui.button :disabled="$count > 3">Gönder</x-ui.button>'],
            'dashed name' => ['<acun:page-header title="Siparişler" />', '<x-ui.page-header title="Siparişler" />'],
            'dotted parts and nesting' => [
                '<acun:accordion open="a"><acun:accordion-item name="a"><acun:accordion.heading>Soru <acun:badge>Yeni</acun:badge></acun:accordion.heading><acun:accordion.content>Yanıt</acun:accordion.content></acun:accordion-item></acun:accordion>',
                '<x-ui.accordion open="a"><x-ui.accordion-item name="a"><x-ui.accordion.heading>Soru <x-ui.badge>Yeni</x-ui.badge></x-ui.accordion.heading><x-ui.accordion.content>Yanıt</x-ui.accordion.content></x-ui.accordion-item></x-ui.accordion>',
            ],
            'named slots' => [
                // A closing slot tag needs a space or line break after it (Blade reads "</x-slot>Gövde" as @endslotGövde).
                "<acun:card title=\"Özet\"><acun:slot:actions><acun:badge>3</acun:badge></acun:slot>\nGövde<acun:slot name=\"footer\">Alt</acun:slot>\n</acun:card>",
                "<x-ui.card title=\"Özet\"><x-slot:actions><x-ui.badge>3</x-ui.badge></x-slot>\nGövde<x-slot name=\"footer\">Alt</x-slot>\n</x-ui.card>",
            ],
        ];

        foreach ($pairs as $case => [$short, $long]) {
            self::assertSame(Blade::render($long, ['count' => 5]), Blade::render($short, ['count' => 5]), $case);
        }
    }

    /** Every component of the package has its short tag: <acun:NAME /> compiles exactly like <x-acun::NAME />. */
    public function test_every_component_has_a_short_tag(): void
    {
        $names = self::componentNames(__DIR__.'/../../resources/views/components/ui');

        self::assertContains('button', $names);
        self::assertContains('accordion.heading', $names);
        self::assertContains('layout.head', $names);
        self::assertNotContains('layout.sidebar', $names, "The panel layouts are acunsoft/acun-pro's.");

        foreach ($names as $name) {
            self::assertSame(Blade::compileString("<x-acun::{$name} />"), Blade::compileString("<acun:{$name} />"), $name);
        }
    }

    /**
     * An application component with the same name (resources/views/components/ui/badge.blade.php)
     * never replaces a kit component: <acun:badge> stays the kit's; only the older <x-ui.badge>
     * name, which shares Laravel's default namespace, resolves to the application's.
     */
    public function test_an_application_component_with_the_same_name_does_not_replace_the_kit(): void
    {
        $views = sys_get_temp_dir().'/acun-app-views-'.uniqid();
        mkdir($views.'/components/ui', 0777, true);
        file_put_contents($views.'/components/ui/badge.blade.php', '<b>uygulamanın rozeti</b>');
        View::getFinder()->prependLocation($views);

        try {
            self::assertStringNotContainsString('uygulamanın rozeti', Blade::render('<acun:badge>Yeni</acun:badge>'));
            self::assertStringNotContainsString('uygulamanın rozeti', Blade::render('<x-acun::badge>Yeni</x-acun::badge>'));
            self::assertStringContainsString('uygulamanın rozeti', Blade::render('<x-ui.badge>Yeni</x-ui.badge>'));
        } finally {
            unlink($views.'/components/ui/badge.blade.php');
            rmdir($views.'/components/ui');
            rmdir($views.'/components');
            rmdir($views);
        }
    }

    /** PHP in @php blocks and <?php ?> keeps a literal "<acun:" string; only real tags are rewritten. */
    public function test_php_blocks_are_not_rewritten(): void
    {
        $compiled = AcunTagCompiler::compile("@php\n    \$hint = '<acun:button>';\n@endphp\n<?php \$other = '<acun:badge>'; ?>\n@php(\$x = 1)\n<acun:badge>Yeni</acun:badge>");

        self::assertStringContainsString("\$hint = '<acun:button>';", $compiled);
        self::assertStringContainsString("\$other = '<acun:badge>';", $compiled);
        self::assertStringContainsString('<x-acun::badge>Yeni</x-acun::badge>', $compiled);
    }

    /**
     * Component names under a components/ui folder, e.g. 'button', 'accordion.heading', 'layout.head'.
     *
     * @return array<int, string>
     */
    public static function componentNames(string $directory): array
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        $names = [];

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $relative = substr($file->getPathname(), strlen($directory) + 1, -strlen('.blade.php'));
                $names[] = str_replace(DIRECTORY_SEPARATOR, '.', $relative);
            }
        }

        sort($names);

        return $names;
    }

    /** Code shown in @verbatim stays as written. */
    public function test_verbatim_blocks_keep_the_short_tags(): void
    {
        $html = Blade::render('@verbatim<acun:button>Kaydet</acun:button>@endverbatim <acun:badge>Yeni</acun:badge>');

        self::assertStringContainsString('<acun:button>Kaydet</acun:button>', $html);
        self::assertStringNotContainsString('<acun:badge>', $html);
        self::assertMatchesRegularExpression('/<span[^>]*>\s*Yeni\s*<\/span>/', $html);
    }
}
