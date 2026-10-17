<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

/**
 * The contract of the small utility components: x-ui.scroll-area,
 * x-ui.copy-button and x-ui.kbd.
 */
final class UtilityComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    public function test_scroll_area_is_keyboard_scrollable_and_a_region_only_when_labelled(): void
    {
        $plain = Blade::render('<x-ui.scroll-area>İçerik</x-ui.scroll-area>');
        $labelled = Blade::render('<x-ui.scroll-area label="Son ziyaretler">İçerik</x-ui.scroll-area>');

        self::assertStringContainsString('tabindex="0"', $plain);
        self::assertStringNotContainsString('role="region"', $plain);
        self::assertStringContainsString('role="region" aria-label="Son ziyaretler"', $labelled);
        self::assertStringContainsString('role="region"', Blade::render('<x-ui.scroll-area aria-labelledby="baslik">İçerik</x-ui.scroll-area>'));
        self::assertStringContainsString('tabindex="-1"', Blade::render('<x-ui.scroll-area tabindex="-1">İçerik</x-ui.scroll-area>'));
    }

    public function test_scroll_area_direction_picks_the_overflow_axis(): void
    {
        self::assertStringContainsString('class="overflow-x-hidden overflow-y-auto ', Blade::render('<x-ui.scroll-area>A</x-ui.scroll-area>'));
        self::assertStringContainsString('class="overflow-x-auto overflow-y-hidden ', Blade::render('<x-ui.scroll-area direction="horizontal">A</x-ui.scroll-area>'));
        self::assertStringContainsString('class="overflow-auto ', Blade::render('<x-ui.scroll-area direction="both">A</x-ui.scroll-area>'));
    }

    public function test_scroll_area_themes_its_scrollbar_in_both_modes(): void
    {
        $html = Blade::render('<x-ui.scroll-area>A</x-ui.scroll-area>');

        self::assertStringContainsString('[scrollbar-width:thin]', $html);
        self::assertStringContainsString('[scrollbar-color:var(--color-gray-300)_transparent]', $html);
        self::assertStringContainsString('dark:[scrollbar-color:var(--color-zinc-700)_transparent]', $html);
        self::assertStringContainsString('[&amp;::-webkit-scrollbar-thumb]:bg-gray-300', $html);
        self::assertStringContainsString('dark:[&amp;::-webkit-scrollbar-thumb]:bg-zinc-700', $html);
    }

    public function test_scroll_area_sizes_and_attributes_merge_onto_the_root(): void
    {
        $html = Blade::render('<x-ui.scroll-area max-height="16rem" :height="200" class="p-2" style="color: red" data-test="alan">A</x-ui.scroll-area>');

        self::assertStringContainsString('style="height: 200px; max-height: 16rem; color: red;"', $html);
        self::assertMatchesRegularExpression('/class="overflow-x-hidden [^"]* p-2"/', $html);
        self::assertStringContainsString('data-test="alan"', $html);
        self::assertStringNotContainsString('style=', Blade::render('<x-ui.scroll-area>A</x-ui.scroll-area>'));
    }

    public function test_scroll_area_shadows_are_opt_in(): void
    {
        $plain = Blade::render('<x-ui.scroll-area>A</x-ui.scroll-area>');
        $shadowed = Blade::render('<x-ui.scroll-area direction="horizontal" shadows>A</x-ui.scroll-area>');

        self::assertStringNotContainsString('x-data', $plain);
        self::assertStringContainsString('x-on:scroll.passive="measure()"', $shadowed);
        self::assertStringContainsString("x-bind:style=\"{ maskImage: mask, maskComposite: 'intersect' }\"", $shadowed);
        self::assertStringContainsString('vertical: false,', $shadowed);
        self::assertStringContainsString('horizontal: true,', $shadowed);
    }

}
