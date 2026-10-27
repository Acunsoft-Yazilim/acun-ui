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
 * x-ui.copy-button, x-ui.kbd and x-ui.avatar.
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
        // One token for both modes: the strong line, the line-hover colour under the pointer.
        self::assertStringContainsString('[scrollbar-color:var(--ac-line-strong)_transparent]', $html);
        self::assertStringContainsString('hover:[scrollbar-color:var(--ac-line-hover)_transparent]', $html);
        self::assertStringContainsString('[&amp;::-webkit-scrollbar-thumb]:bg-line-strong', $html);
        self::assertStringNotContainsString('dark:', $html);
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

        // The fading edges are the runtime's (js/plugins/scroll-shadows.js), only with shadows.
        self::assertStringNotContainsString('data-acun-scroll-shadows', $plain);
        self::assertStringContainsString('data-acun-scroll-shadows data-direction="horizontal"', $shadowed);
    }

    public function test_copy_button_reads_its_text_or_target_from_the_button(): void
    {
        $text = Blade::render('<x-ui.copy-button text="BŞV-2026-004817" />');
        $target = Blade::render('<x-ui.copy-button target="#adres" />');

        self::assertStringContainsString('data-copy-text="BŞV-2026-004817"', $text);
        self::assertStringNotContainsString('data-copy-target', $text);
        self::assertStringContainsString('data-copy-target="#adres"', $target);
        self::assertStringNotContainsString('data-copy-text', $target);
        self::assertStringContainsString('data-acun-copy', $target);
    }

    public function test_copy_button_announces_the_result_politely(): void
    {
        $html = self::decodeUnicodeEscapes(Blade::render('<x-ui.copy-button text="a" />'));

        // The plugin (js/plugins/copy.js) writes the result into the polite status line and data-state.
        self::assertMatchesRegularExpression('/data-copied-label="Kopyalandı"\s+data-failed-label="Kopyalanamadı"/', $html);
        self::assertStringContainsString('<span class="sr-only" aria-live="polite" data-slot="copy-status"></span>', $html);
        self::assertStringContainsString('<span class="group-data-[state=copied]/copy:hidden group-data-[state=failed]/copy:hidden">Kopyala</span>', $html);
        self::assertStringContainsString('<span class="hidden group-data-[state=copied]/copy:inline">Kopyalandı</span>', $html);
    }

    public function test_copy_button_labels_are_translatable(): void
    {
        // English is the keys themselves; an application's lang/en.json can still reword them.
        Lang::addLines(['*.Could not copy' => 'Copy failed'], 'en');
        App::setLocale('en');

        $html = Blade::render('<x-ui.copy-button text="a" />');

        self::assertStringContainsString('>Copy</span>', $html);
        self::assertStringContainsString('>Copied</span>', $html);
        self::assertStringContainsString('>Copy failed</span>', $html);
        self::assertStringNotContainsString('Kopyala', $html);
    }

    public function test_copy_button_passes_attributes_and_props_to_the_button(): void
    {
        $html = Blade::render('<x-ui.copy-button text="a" variant="ghost" size="sm" class="w-full" disabled x-on:click="sayac++" wire:click="kaydet" />');

        self::assertMatchesRegularExpression('/<button type="button" disabled\s+class="[^"]*px-2\.5 py-\[5px\] text-xs[^"]*text-dim hover:not-disabled:text-ink[^"]* w-full"/', $html);
        self::assertStringContainsString('x-on:click="sayac++"', $html);
        self::assertStringContainsString('wire:click="kaydet"', $html);
    }

    public function test_icon_only_copy_button_is_named_by_its_label(): void
    {
        $html = Blade::render('<x-ui.copy-button target="#link" size="icon" label="Bağlantıyı kopyala" />');

        self::assertStringContainsString('aria-label="Bağlantıyı kopyala" title="Bağlantıyı kopyala"', $html);
        self::assertStringNotContainsString('<span x-show', $html);
        self::assertStringContainsString('aria-live="polite"', $html);
        self::assertStringNotContainsString('aria-label=', Blade::render('<x-ui.copy-button text="a" />'));
    }

    public function test_kbd_renders_a_single_key_from_the_slot(): void
    {
        $html = Blade::render('<x-ui.kbd class="ms-1">Esc</x-ui.kbd>');

        self::assertMatchesRegularExpression('/^<kbd class="[^"]*rounded-sm border border-line[^"]*bg-field[^"]* ms-1">Esc<\/kbd>$/', trim($html));
    }

    public function test_kbd_combination_nests_one_kbd_per_key(): void
    {
        $html = Blade::render('<x-ui.kbd :keys="[\'Ctrl\', \'Shift\', \'P\']" data-test="kisayol" />');

        self::assertMatchesRegularExpression('/^<kbd class="inline-flex items-center gap-1 font-sans" data-test="kisayol">/', trim($html));
        self::assertSame(4, substr_count($html, '<kbd'));
        self::assertSame(2, substr_count($html, '>+</span>'));
        self::assertMatchesRegularExpression('/>Ctrl<\/kbd>.*>Shift<\/kbd>.*>P<\/kbd>/s', $html);

        $bare = Blade::render('<x-ui.kbd :keys="[\'⌘\', \'K\']" separator="" size="sm" />');
        self::assertStringNotContainsString('>+</span>', $bare);
        self::assertStringContainsString('h-5 min-w-5', $bare);
    }

    public function test_kbd_symbols_get_a_spoken_name(): void
    {
        $html = Blade::render('<x-ui.kbd :keys="[\'⌘\', \'K\']" />');

        self::assertStringContainsString('<span class="sr-only">Command</span><span aria-hidden="true" class="font-sans">⌘</span>', $html);
        self::assertStringContainsString('<span class="sr-only">Enter</span><span aria-hidden="true" class="font-sans">↵</span>', Blade::render('<x-ui.kbd>↵</x-ui.kbd>'));
    }

    /** Avatar takes the palette colour names; the older names keep working. */
    public function test_avatar_takes_the_palette_names(): void
    {
        self::assertStringContainsString("bg-secondary-soft text-secondary-tint-ink", Blade::render("<x-ui.avatar name=\"Ayşe Demir\" color=\"secondary\" />"));
        self::assertStringContainsString("bg-secondary-soft text-secondary-tint-ink", Blade::render("<x-ui.avatar name=\"Ayşe Demir\" color=\"neutral\" />"));
        self::assertStringContainsString("bg-primary-soft text-primary-tint-ink", Blade::render("<x-ui.avatar name=\"Ayşe Demir\" />"));

        // The "+3" bubble of an avatar group follows the avatars' size.
        self::assertStringContainsString("flex size-8 text-xs items-center", Blade::render("<x-ui.avatar-group :more=\"3\" size=\"sm\"><x-ui.avatar name=\"Ayşe Demir\" size=\"sm\" /></x-ui.avatar-group>"));
    }
}
