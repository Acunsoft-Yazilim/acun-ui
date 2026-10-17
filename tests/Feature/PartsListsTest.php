<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

/**
 * List-like containers and their items: accordion.item, list-group.item,
 * description-list.item (the flat names are aliases), and the breadcrumb and
 * carousel parts.
 */
final class PartsListsTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function aliases(): array
    {
        return [
            'accordion item with title' => [
                '<acun:accordion open="a"><acun:accordion-item name="a" title="Teslimat saatleri" x-show="visible" class="text-xs">09.00–18.00</acun:accordion-item></acun:accordion>',
                '<acun:accordion open="a"><acun:accordion.item name="a" title="Teslimat saatleri" x-show="visible" class="text-xs">09.00–18.00</acun:accordion.item></acun:accordion>',
            ],
            'accordion item in parts' => [
                '<acun:accordion open="siparis"><acun:accordion-item name="siparis" wire:key="siparis"><acun:accordion.heading class="py-3">Sipariş #1042 <acun:badge>Yeni</acun:badge></acun:accordion.heading><acun:accordion.content class="text-xs">14 ürün</acun:accordion.content></acun:accordion-item></acun:accordion>',
                '<acun:accordion open="siparis"><acun:accordion.item name="siparis" wire:key="siparis"><acun:accordion.heading class="py-3">Sipariş #1042 <acun:badge>Yeni</acun:badge></acun:accordion.heading><acun:accordion.content class="text-xs">14 ürün</acun:accordion.content></acun:accordion.item></acun:accordion>',
            ],
            'accordion item with a title slot' => [
                '<acun:accordion><acun:accordion-item name="b"><acun:slot:title>Başlık <b>kalın</b></acun:slot>'."\n".'Gövde</acun:accordion-item></acun:accordion>',
                '<acun:accordion><acun:accordion.item name="b"><acun:slot:title>Başlık <b>kalın</b></acun:slot>'."\n".'Gövde</acun:accordion.item></acun:accordion>',
            ],
            'list group items' => [
                '<acun:list-group><acun:list-group-item href="/siparisler/1042" active>Sipariş #1042</acun:list-group-item><acun:list-group-item disabled>Sipariş #1043</acun:list-group-item><acun:list-group-item href="/siparisler/1044" class="font-bold" wire:key="s1044">Sipariş #1044</acun:list-group-item><acun:list-group-item>Sipariş #1045</acun:list-group-item></acun:list-group>',
                '<acun:list-group><acun:list-group.item href="/siparisler/1042" active>Sipariş #1042</acun:list-group.item><acun:list-group.item disabled>Sipariş #1043</acun:list-group.item><acun:list-group.item href="/siparisler/1044" class="font-bold" wire:key="s1044">Sipariş #1044</acun:list-group.item><acun:list-group.item>Sipariş #1045</acun:list-group.item></acun:list-group>',
            ],
            'description items' => [
                '<acun:description-list :items="[\'Sipariş\' => \'<b>S-1042</b>\']"><acun:description-item label="Durum" class="py-2"><span data-badge>Yolda</span></acun:description-item></acun:description-list>',
                '<acun:description-list :items="[\'Sipariş\' => \'<b>S-1042</b>\']"><acun:description-list.item label="Durum" class="py-2"><span data-badge>Yolda</span></acun:description-list.item></acun:description-list>',
            ],
        ];
    }

    /** The flat name is an alias of the dotted part: the same input renders the same bytes. */
    #[\PHPUnit\Framework\Attributes\DataProvider('aliases')]
    public function test_the_flat_name_renders_exactly_what_the_dotted_part_renders(string $flat, string $dotted): void
    {
        self::assertSame(Blade::render($dotted), Blade::render($flat));
    }

    /** Each alias file only includes its dotted part. */
    public function test_alias_files_include_the_dotted_part(): void
    {
        $dir = __DIR__.'/../../resources/views/components/ui';

        foreach (['accordion-item' => 'accordion.item', 'list-group-item' => 'list-group.item', 'description-item' => 'description-list.item'] as $old => $new) {
            $source = (string) file_get_contents("{$dir}/{$old}.blade.php");

            self::assertStringContainsString("@include('acun-ui::components.ui.{$new}')", $source, $old);
            self::assertSame(2, substr_count(trim($source), "\n") + 1, "{$old}: a comment line and the include.");
        }
    }

    /** The heading and content parts read the name of the dotted item (with @aware) as they do the flat one. */
    public function test_accordion_parts_take_the_name_of_the_dotted_item(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:accordion open="teslimat">
                <acun:accordion.item name="teslimat" x-show="visible">
                    <acun:accordion.heading class="py-3">Teslimat penceresi</acun:accordion.heading>
                    <acun:accordion.content class="text-xs">Müşteriler 09.00–12.00 arası aranır.</acun:accordion.content>
                </acun:accordion.item>
            </acun:accordion>
        BLADE);

        self::assertMatchesRegularExpression('/<div class="border-b[^"]*" x-show="visible">\s*<h3>/', $html);
        self::assertMatchesRegularExpression('/<button\s+type="button"\s+:id="\$id\(\'accordion\', \'teslimat\'\) \+ \'-heading\'"\s+x-on:click="toggle\(\'teslimat\'\)"[^>]*:aria-controls="\$id\(\'accordion\', \'teslimat\'\)"[^>]*class="[^"]*py-3/', $html);
        self::assertMatchesRegularExpression('/<div :id="\$id\(\'accordion\', \'teslimat\'\)" role="region" :aria-labelledby="\$id\(\'accordion\', \'teslimat\'\) \+ \'-heading\'" x-show="isOpen\(\'teslimat\'\)"/', $html);
        self::assertMatchesRegularExpression('/<div class="px-5 pb-5[^"]*text-xs[^"]*">\s*Müşteriler/', $html);
    }

    /** An accordion inside an item's content: each heading takes the name of its own item. */
    public function test_nested_accordions_wire_each_heading_to_its_own_item(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:accordion>
                <acun:accordion.item name="dis">
                    <acun:accordion.heading>Satış</acun:accordion.heading>
                    <acun:accordion.content>
                        <acun:accordion>
                            <acun:accordion.item name="ic">
                                <acun:accordion.heading>Siparişler</acun:accordion.heading>
                                <acun:accordion.content>Bekleyenler</acun:accordion.content>
                            </acun:accordion.item>
                            <acun:accordion.item name="baslikli" title="Destek ekibi">Ayşe, Mehmet</acun:accordion.item>
                        </acun:accordion>
                    </acun:accordion.content>
                </acun:accordion.item>
            </acun:accordion>
        BLADE);

        self::assertMatchesRegularExpression('/:id="\$id\(\'accordion\', \'dis\'\) \+ \'-heading\'"[^>]*>\s*<span[^>]*>Satış<\/span>/', $html);
        self::assertMatchesRegularExpression('/:id="\$id\(\'accordion\', \'ic\'\) \+ \'-heading\'"[^>]*>\s*<span[^>]*>Siparişler<\/span>/', $html);
        self::assertMatchesRegularExpression('/:id="\$id\(\'accordion\', \'baslikli\'\) \+ \'-heading\'"[^>]*>\s*<span[^>]*>Destek ekibi<\/span>/', $html);
        self::assertStringContainsString(':aria-labelledby="$id(\'accordion\', \'ic\') + \'-heading\'" x-show="isOpen(\'ic\')"', $html);
    }

    /** The content part outside an item says where it belongs. */
    public function test_accordion_content_outside_an_item_throws(): void
    {
        try {
            Blade::render('<acun:accordion><acun:accordion.content>Gövde</acun:accordion.content></acun:accordion>');
            self::fail('acun:accordion.content outside an item must throw.');
        } catch (ViewException $e) {
            $cause = $e;

            while ($cause instanceof ViewException && $cause->getPrevious() !== null) {
                $cause = $cause->getPrevious();
            }

            self::assertInstanceOf(\InvalidArgumentException::class, $cause);
            self::assertSame('acun:accordion.content must be inside an acun:accordion-item with a name.', $cause->getMessage());
        }
    }

    /** description-list renders its :items with the dotted item, so both rows look the same. */
    public function test_description_list_items_and_dotted_rows_share_the_markup(): void
    {
        $fromItems = Blade::render('<acun:description-list :items="[\'Müşteri\' => \'Ayşe Yılmaz\']" />');
        $written = Blade::render('<acun:description-list><acun:description-list.item label="Müşteri">Ayşe Yılmaz</acun:description-list.item></acun:description-list>');

        self::assertSame(self::normalize($fromItems), self::normalize($written));
    }

    /** Written in parts, a breadcrumb looks exactly like its :items twin; the parts only add data-slot. */
    public function test_breadcrumb_parts_render_like_the_items(): void
    {
        $items = Blade::render('<acun:breadcrumb home-url="/panel" :items="[[\'label\' => \'Siparişler\', \'url\' => \'/siparisler\'], [\'label\' => \'Sipariş #1042\']]" />');
        $parts = Blade::render(<<<'BLADE'
            <acun:breadcrumb home-url="/panel">
                <acun:breadcrumb.item href="/siparisler">Siparişler</acun:breadcrumb.item>
                <acun:breadcrumb.item>Sipariş #1042</acun:breadcrumb.item>
            </acun:breadcrumb>
        BLADE);

        self::assertSame(self::normalize($items), self::normalize(preg_replace('/ data-slot="breadcrumb-[\w-]+"/', '', $parts)));
        self::assertSame(2, substr_count($parts, 'data-slot="breadcrumb-item"'));
        self::assertSame(2, substr_count($parts, 'data-slot="breadcrumb-separator"'));
    }

    public function test_breadcrumb_parts_keep_the_home_link_and_mark_the_current_page(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:breadcrumb home-url="/panel" home-label="Panel" class="mb-4">
                <acun:breadcrumb.item href="/siparisler" class="font-semibold" wire:key="siparisler">Siparişler</acun:breadcrumb.item>
                <acun:breadcrumb.item :current="false">Bekleyenler</acun:breadcrumb.item>
                <acun:breadcrumb.item href="/siparisler/1042" current>Sipariş #1042</acun:breadcrumb.item>
            </acun:breadcrumb>
        BLADE);

        // The nav and the home link stay as they are with items.
        self::assertMatchesRegularExpression('/<nav aria-label="Konum" class="[^"]*flex-wrap[^"]*mb-4">\s*<a href="\/panel" wire:navigate class="flex-none[^"]*" aria-label="Panel" title="Panel">/', $html);
        // Attributes go on the link.
        self::assertMatchesRegularExpression('/<a data-slot="breadcrumb-item" href="\/siparisler" wire:navigate class="whitespace-nowrap [^"]*font-semibold" wire:key="siparisler">Siparişler<\/a>/', $html);
        // Not the current page, no link: plain text without aria-current.
        self::assertStringContainsString('<span data-slot="breadcrumb-item" class="whitespace-nowrap">Bekleyenler</span>', $html);
        // current wins over href: plain text with aria-current.
        self::assertMatchesRegularExpression('/<span data-slot="breadcrumb-item" class="whitespace-nowrap font-medium[^"]*" aria-current="page">Sipariş #1042<\/span>/', $html);
        self::assertStringNotContainsString('href="/siparisler/1042"', $html);
        self::assertSame(1, substr_count($html, 'aria-current'));
        self::assertSame(3, substr_count($html, 'data-slot="breadcrumb-separator"'));
    }

    /** An item without its own separator, after a separator part with custom content. */
    public function test_breadcrumb_separator_part_takes_custom_content(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:breadcrumb home-url="/">
                <acun:breadcrumb.separator class="px-1">/</acun:breadcrumb.separator>
                <acun:breadcrumb.item :separator="false">Kargolar</acun:breadcrumb.item>
            </acun:breadcrumb>
        BLADE);

        self::assertStringContainsString('<span data-slot="breadcrumb-separator" aria-hidden="true" class="flex-none text-line-strong px-1">/</span>', $html);
        self::assertSame(1, substr_count($html, 'data-slot="breadcrumb-separator"'));
        self::assertStringNotContainsString('<svg data-slot', $html);
    }

    /** With items, the slot is ignored and the output has no parts. */
    public function test_breadcrumb_items_win_over_the_slot(): void
    {
        $html = Blade::render('<acun:breadcrumb home-url="/" :items="[[\'label\' => \'Siparişler\']]"><acun:breadcrumb.item>Görünmez</acun:breadcrumb.item></acun:breadcrumb>');

        self::assertMatchesRegularExpression('/aria-current="page"\s*>Siparişler<\/span>/', $html);
        self::assertStringNotContainsString('Görünmez', $html);
        self::assertStringNotContainsString('data-slot', $html);
        // An empty slot is the classic home-only breadcrumb.
        self::assertSame(Blade::render('<acun:breadcrumb home-url="/" />'), Blade::render("<acun:breadcrumb home-url=\"/\">\n</acun:breadcrumb>"));
    }

    /** A slide part: a named group for screen readers, inert while another slide is shown. */
    public function test_carousel_item_is_a_named_slide(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:carousel label="Depo fotoğrafları">
                <acun:carousel.item label="Depo girişi" class="h-40" wire:key="giris"><img src="/giris.jpg" alt="Depo girişi"></acun:carousel.item>
                <acun:carousel.item><img src="/rampa.jpg" alt="Yükleme rampası"></acun:carousel.item>
                <div>Düz slayt</div>
            </acun:carousel>
        BLADE);

        // The carousel itself is unchanged: every direct child of the track is a slide.
        self::assertStringContainsString('this.count = this.$refs.track.children.length;', $html);
        self::assertMatchesRegularExpression('/<div\s+data-slot="carousel-item"\s+role="group"\s+aria-roledescription="slide"\s+aria-label="Depo girişi"\s+x-bind:inert="index !== \[\.\.\.\$el\.parentElement\.children\]\.indexOf\(\$el\)"\s+class="h-40" wire:key="giris"\s*>/', $html);
        // Without a label, it names itself by its position.
        self::assertStringContainsString('x-bind:aria-label="`${[...$el.parentElement.children].indexOf($el) + 1} / ${count}`"', $html);
        self::assertSame(2, substr_count($html, 'aria-roledescription="slide"'));
        self::assertStringContainsString('<div>Düz slayt</div>', $html);
    }

    /** Whitespace between tags and before ">" does not change the markup. */
    private static function normalize(string $html): string
    {
        return trim((string) preg_replace(['/>\s+</', '/\s+/', '/ >/'], ['><', ' ', '>'], $html));
    }
}
