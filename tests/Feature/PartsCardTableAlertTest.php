<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

/**
 * acun:card, acun:table and acun:alert written in parts (card.header / card.body /
 * card.footer, table.columns / table.column / table.rows / table.row / table.cell,
 * alert.title / alert.description) next to their classic props.
 *
 * Note: a named slot's closing tag is followed by a newline in these templates; Blade
 * reads "</x-slot>Text" as an unknown "@endslotText" directive.
 */
final class PartsCardTableAlertTest extends TestCase
{
    // ── card ─────────────────────────────────────────────────────────────

    public function test_a_card_written_in_parts_draws_only_its_box_around_them(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card class="kart" id="siparisler">
                <acun:card.header title="Siparişler" class="baslik" />
                <acun:card.body class="govde">12 sipariş</acun:card.body>
                <acun:card.footer class="alt">Tümünü gör</acun:card.footer>
            </acun:card>
        BLADE);

        self::assertMatchesRegularExpression('/^\s*<div\s+class="flex flex-col rounded-sm bg-white shadow-sm dark:bg-zinc-900 kart" id="siparisler"\s*>\s*<div data-slot="card-header" class="flex items-start justify-between gap-3 px-5 py-4 baslik">/', $html, 'The slot is rendered as it is, right inside the card box.');
        self::assertStringContainsString('<div data-slot="card-body"  class="flex-1 p-5 govde">', $html);
        self::assertStringContainsString('<div data-slot="card-footer" class="flex items-center gap-2 border-t border-gray-100 px-5 py-3 dark:border-zinc-800 alt">Tümünü gör</div>', $html);
        self::assertSame(1, substr_count($html, 'p-5'), 'No classic padded body around the parts.');
        self::assertStringNotContainsString('x-data', $html);
    }

    /** Parts and classic props draw the same card: only the data-slot markers differ. */
    public function test_card_parts_look_exactly_like_the_classic_card(): void
    {
        $classic = Blade::render(<<<'BLADE'
            <acun:card title="Siparişler" subtitle="Bugün 12 sipariş" collapsible closable :padding="false" class="mt-4">
                <table></table>
                <acun:slot:actions><button>Yeni sipariş</button></acun:slot>
                <acun:slot:footer><button>Kaydet</button></acun:slot>
            </acun:card>
        BLADE);

        $parts = Blade::render(<<<'BLADE'
            <acun:card collapsible closable class="mt-4">
                <acun:card.header title="Siparişler" subtitle="Bugün 12 sipariş">
                    <acun:slot:actions><button>Yeni sipariş</button></acun:slot>
                </acun:card.header>
                <acun:card.body :padding="false"><table></table></acun:card.body>
                <acun:card.footer><button>Kaydet</button></acun:card.footer>
            </acun:card>
        BLADE);

        self::assertStringContainsString('data-slot="card-header"', $parts);
        self::assertSame(self::normalize($classic), self::normalize(preg_replace('/ data-slot="card-\w+"/', '', $parts)));
    }

    public function test_card_header_slot_is_the_heading_row(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card.header subtitle="Bugün 12 sipariş">
                <svg data-icon></svg> Siparişler <acun:badge variant="success">3 yeni</acun:badge>
                <acun:slot:actions><button>Yeni sipariş</button></acun:slot>
            </acun:card.header>
        BLADE);

        self::assertMatchesRegularExpression('/<h3 class="text-sm font-semibold text-gray-900 dark:text-zinc-100 flex items-center gap-2"><svg data-icon><\/svg> Siparişler <span[^>]*bg-emerald-50[^>]*>\s*3 yeni\s*<\/span><\/h3>/', $html);
        self::assertStringContainsString('<p class="mt-0.5 text-xs text-gray-500 dark:text-zinc-400">Bugün 12 sipariş</p>', $html);
        self::assertMatchesRegularExpression('/<div class="flex flex-none items-center gap-1">\s*<button>Yeni sipariş<\/button>/', $html);

        // With a title the slot follows it in the same row.
        $titled = Blade::render('<acun:card.header title="Kargolar"><acun:badge>8</acun:badge></acun:card.header>');
        self::assertMatchesRegularExpression('/<h3 class="[^"]*flex items-center gap-2">Kargolar<span[^>]*>\s*8\s*<\/span><\/h3>/', $titled);

        // Only actions: no empty heading.
        $actionsOnly = Blade::render(<<<'BLADE'
            <acun:card.header>
                <acun:slot:actions><button>Filtre</button></acun:slot>
            </acun:card.header>
        BLADE);
        self::assertStringNotContainsString('<h3', $actionsOnly);
    }

    public function test_a_collapsible_closable_card_wires_its_header_and_body(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card collapsible closable>
                <acun:card.header title="Destek ekibi" />
                <acun:card.body>8 kişi çevrimiçi</acun:card.body>
            </acun:card>
        BLADE);

        self::assertMatchesRegularExpression('/^\s*<div\s+x-data="\{ open: true, visible: true \}" x-show="visible"/', $html);
        self::assertStringContainsString('x-on:click="open = ! open" :aria-expanded="open.toString()" aria-label="Daralt / genişlet"', $html);
        self::assertStringContainsString('x-on:click="visible = false" aria-label="Kapat"', $html);
        self::assertStringContainsString('<div data-slot="card-body"  x-show="open" x-collapse.duration.200ms  class="flex-1 p-5">', $html);

        $plain = Blade::render(<<<'BLADE'
            <acun:card>
                <acun:card.header title="Destek ekibi" />
                <acun:card.body>8 kişi çevrimiçi</acun:card.body>
            </acun:card>
        BLADE);

        self::assertStringNotContainsString('<button', $plain);
        self::assertStringNotContainsString('x-show', $plain);
    }

    public function test_card_parts_work_on_their_own(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card.header title="Müşteri özeti" />
            <acun:card.body :padding="false" class="divide-y" wire:key="b">…</acun:card.body>
            <acun:card.footer>Alt</acun:card.footer>
        BLADE);

        self::assertStringContainsString('<h3 class="text-sm font-semibold text-gray-900 dark:text-zinc-100">Müşteri özeti</h3>', $html);
        self::assertStringNotContainsString('<button', $html, 'No collapse / close buttons without a card.');
        self::assertStringContainsString('<div data-slot="card-body"  class="flex-1 divide-y" wire:key="b">', $html);
    }

    public function test_a_card_that_does_not_start_with_a_part_stays_classic(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card title="Teslimat notu">
                <p>Kapı kodu 1234</p>
                <acun:card.footer>Alt</acun:card.footer>
            </acun:card>
        BLADE);

        self::assertMatchesRegularExpression('/<div\s+class="flex-1 p-5">\s*<p>Kapı kodu 1234<\/p>\s*<div data-slot="card-footer"/', $html);
        self::assertStringContainsString('<h3 class="text-sm font-semibold text-gray-900 dark:text-zinc-100">Teslimat notu</h3>', $html);
    }

    public function test_nested_cards_each_keep_their_own_mode(): void
    {
        // Outer in parts, inner classic inside its body.
        $html = Blade::render(<<<'BLADE'
            <acun:card>
                <acun:card.header title="Dış" />
                <acun:card.body>
                    <acun:card title="İç">İç gövde</acun:card>
                </acun:card.body>
            </acun:card>
        BLADE);

        self::assertSame(1, substr_count($html, 'data-slot="card-header"'));
        self::assertSame(1, substr_count($html, 'data-slot="card-body"'));
        self::assertMatchesRegularExpression('/<div class="flex items-start justify-between gap-3 px-5 py-4">\s*<div class="min-w-0">\s*<h3[^>]*>İç<\/h3>/', $html, 'The inner card keeps its classic header.');
        self::assertMatchesRegularExpression('/<div\s+class="flex-1 p-5">\s*İç gövde/', $html);

        // Outer classic, inner in parts: the outer slot starts with the inner card's box, not a part.
        $html = Blade::render(<<<'BLADE'
            <acun:card title="Dış">
                <acun:card>
                    <acun:card.body>İç gövde</acun:card.body>
                </acun:card>
            </acun:card>
        BLADE);

        self::assertMatchesRegularExpression('/<h3[^>]*>Dış<\/h3>.*<div\s+class="flex-1 p-5">\s*<div\s+class="flex flex-col[^"]*"\s*>\s*<div data-slot="card-body"/s', $html);
    }

    /** @aware reaches past a card that leaves collapsible out; the inner card can say false. */
    public function test_a_nested_card_can_turn_off_the_outer_cards_buttons(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card collapsible closable>
                <acun:card.header title="Siparişler" />
                <acun:card.body>
                    <acun:card :collapsible="false" :closable="false">
                        <acun:card.header title="Ayşe Yılmaz" />
                        <acun:card.body>42 sipariş</acun:card.body>
                    </acun:card>
                </acun:card.body>
            </acun:card>
        BLADE);

        self::assertSame(1, substr_count($html, 'aria-label="Daralt / genişlet"'));
        self::assertSame(1, substr_count($html, 'aria-label="Kapat"'));
        self::assertSame(1, substr_count($html, 'x-show="open"'));
    }

    // ── table ────────────────────────────────────────────────────────────

    public function test_a_table_written_in_parts_renders_the_table_element(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:table class="mt-2" id="siparisler">
                <!-- Livewire markers before the first part are skipped -->
                <acun:table.columns class="sticky top-0">
                    <acun:table.column>Müşteri</acun:table.column>
                    <acun:table.column align="center">Durum</acun:table.column>
                    <acun:table.column align="end" class="w-24">Sipariş</acun:table.column>
                </acun:table.columns>
                <acun:table.rows wire:sort="sirala">
                    <acun:table.row wire:key="musteri-1" class="font-medium">
                        <acun:table.cell>Ayşe Yılmaz</acun:table.cell>
                        <acun:table.cell align="center">Aktif</acun:table.cell>
                        <acun:table.cell align="end" class="tabular-nums">42</acun:table.cell>
                    </acun:table.row>
                    <acun:table.row>
                        <acun:table.cell colspan="3" align="start">Kayıt yok</acun:table.cell>
                    </acun:table.row>
                </acun:table.rows>
            </acun:table>
        BLADE);

        self::assertMatchesRegularExpression('/^\s*<div class="ui-table-box relative overflow-x-auto overflow-y-hidden rounded-sm bg-white shadow-sm dark:bg-zinc-900 mt-2" id="siparisler">\s*<table class="ui-table">\s*<!--[^>]*-->\s*<thead data-slot="table-columns" class="sticky top-0">\s*<tr>\s*<th data-slot="table-column" scope="col"\s*>Müşteri<\/th>/', $html);
        self::assertStringContainsString('<th data-slot="table-column" scope="col" class="text-center">Durum</th>', $html);
        self::assertStringContainsString('<th data-slot="table-column" scope="col" class="text-end w-24">Sipariş</th>', $html);
        self::assertStringContainsString('<tbody data-slot="table-rows" wire:sort="sirala">', $html);
        self::assertMatchesRegularExpression('/<tr data-slot="table-row" wire:key="musteri-1" class="font-medium">\s*<td data-slot="table-cell"\s*>Ayşe Yılmaz<\/td>/', $html);
        self::assertStringContainsString('<td data-slot="table-cell" class="text-end tabular-nums">42</td>', $html);
        self::assertStringContainsString('<td data-slot="table-cell" class="text-start" colspan="3">Kayıt yok</td>', $html);
        self::assertSame(1, substr_count($html, '<table'));
        self::assertStringNotContainsString('class=""', $html, 'No empty class without align.');
    }

    public function test_a_classic_table_is_not_wrapped_again(): void
    {
        $html = Blade::render('<acun:table><table class="ui-table"><tbody><acun:table.row><acun:table.cell>A</acun:table.cell></acun:table.row></tbody></table></acun:table>');

        self::assertMatchesRegularExpression('/<div class="ui-table-box relative overflow-x-auto[^"]*">\s*<table class="ui-table"><tbody><tr data-slot="table-row"\s*>/', $html);
        self::assertSame(1, substr_count($html, '<table'));
    }

    /** A sortable column is an acun:sortable-th: same markup, sort state from the table. */
    public function test_a_stacked_table_marks_its_box_and_its_labelled_cells(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:table stack>
                <acun:table.columns><acun:table.column>Müşteri</acun:table.column><acun:table.column>Şehir</acun:table.column></acun:table.columns>
                <acun:table.rows>
                    <acun:table.row><acun:table.cell>Ayşe Yılmaz</acun:table.cell><acun:table.cell label="Şehir">İzmir</acun:table.cell></acun:table.row>
                </acun:table.rows>
            </acun:table>
            BLADE);

        // panel.css turns the rows into cards below the breakpoint of the box's own width.
        self::assertMatchesRegularExpression('/<div class="ui-table-box [^"]*" data-table-stack="sm">/', $html);
        self::assertStringContainsString('<td data-slot="table-cell" data-label="Şehir">İzmir</td>', $html);

        self::assertStringContainsString('data-table-stack="md"', Blade::render('<acun:table stack="md"><table class="ui-table"></table></acun:table>'));
        self::assertStringNotContainsString('data-table-stack', Blade::render('<acun:table><table class="ui-table"></table></acun:table>'), 'Off by default: the table scrolls sideways.');
        self::assertStringNotContainsString('data-table-stack', Blade::render('<acun:table stack="xl"><table class="ui-table"></table></acun:table>'), 'Only sm and md.');
    }

    public function test_a_sortable_column_is_a_sortable_th(): void
    {
        $column = Blade::render(
            '<acun:table.column sortable field="orders" :sort-field="$f" sort-direction="desc" align="end" class="w-24">Sipariş</acun:table.column>',
            ['f' => 'orders'],
        );
        $th = Blade::render(
            '<acun:sortable-th field="orders" :sort-field="$f" sort-direction="desc" data-slot="table-column" scope="col" class="text-end w-24">Sipariş</acun:sortable-th>',
            ['f' => 'orders'],
        );

        self::assertSame($th, $column);
        self::assertStringContainsString('aria-sort="descending"', $column);
        self::assertStringContainsString("wire:click=\"sortBy('orders')\"", $column);

        $table = Blade::render(<<<'BLADE'
            <acun:table :sort-field="$f" sort-direction="asc">
                <acun:table.columns>
                    <acun:table.column sortable field="customer">Müşteri</acun:table.column>
                    <acun:table.column sortable field="orders">Sipariş</acun:table.column>
                    <acun:table.column sortable field="agent" sort-field="agent" sort-direction="desc">Temsilci</acun:table.column>
                    <acun:table.column>İşlemler</acun:table.column>
                </acun:table.columns>
            </acun:table>
        BLADE, ['f' => 'orders']);

        self::assertMatchesRegularExpression('/<th\s+aria-sort="ascending"\s+class="select-none" data-slot="table-column" scope="col"\s*>\s*<button[^>]*wire:click="sortBy\(\'orders\'\)"/', $table, 'The table passes its sort state to the columns.');
        self::assertMatchesRegularExpression('/<th\s+aria-sort="descending"[^>]*>\s*<button[^>]*wire:click="sortBy\(\'agent\'\)"/', $table, 'A column\'s own sort state wins.');
        self::assertSame(2, substr_count($table, 'aria-sort'));
        self::assertSame(3, substr_count($table, 'wire:click="sortBy('));

        // The component's own sort method, given once on the table.
        $own = Blade::render('<acun:table sort-field="orders" sort-direction="asc" sort-method="sirala"><acun:table.columns><acun:table.column sortable field="orders">Sipariş</acun:table.column></acun:table.columns></acun:table>');
        self::assertStringContainsString("wire:click=\"sirala('orders')\"", $own);
        self::assertStringNotContainsString('sort-field', $table, 'The sort state is not rendered as an attribute.');
    }

    public function test_a_sortable_column_needs_a_field(): void
    {
        $this->expectExceptionMessage('acun:table.column sortable needs a field: the name passed to the sort method.');

        Blade::render('<acun:table><acun:table.columns><acun:table.column sortable>Müşteri</acun:table.column></acun:table.columns></acun:table>');
    }

    public function test_a_table_in_parts_inside_a_card_in_parts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:card>
                <acun:card.header title="Siparişler" />
                <acun:card.body :padding="false">
                    <acun:table class="rounded-none shadow-none">
                        <acun:table.rows>
                            <acun:table.row><acun:table.cell>Ayşe Yılmaz</acun:table.cell></acun:table.row>
                        </acun:table.rows>
                    </acun:table>
                </acun:card.body>
            </acun:card>
        BLADE);

        self::assertMatchesRegularExpression('/<div data-slot="card-body"  class="flex-1">\s*<div class="ui-table-box relative[^"]*rounded-none shadow-none">\s*<table class="ui-table"><tbody data-slot="table-rows"/', $html);
    }

    // ── alert ────────────────────────────────────────────────────────────

    public function test_an_alert_written_in_parts_keeps_its_icon_colour_and_dismiss_button(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:alert variant="warning" dismissible class="mb-4">
                <acun:alert.title class="text-base">3 sipariş gecikmede</acun:alert.title>
                <acun:alert.description>
                    <ul class="list-disc ps-4"><li>Kadıköy deposu: 2 sipariş</li><li>Üsküdar deposu: 1 sipariş</li></ul>
                </acun:alert.description>
            </acun:alert>
        BLADE);

        self::assertMatchesRegularExpression('/x-data="\{ shown: true \}" x-show="shown"\s+class="flex items-start gap-3 rounded-sm p-4 text-sm bg-amber-50 text-amber-800[^"]* mb-4"/', $html);
        self::assertStringContainsString('<svg class="size-5 flex-none text-amber-500"', $html);
        self::assertMatchesRegularExpression('/<div class="flex-1">\s*<p data-slot="alert-title" class="font-medium text-base">3 sipariş gecikmede<\/p>\s*<div data-slot="alert-description" class="not-first:mt-1">\s*<ul/', $html, 'The parts sit next to the icon, without the classic text box.');
        self::assertStringContainsString('aria-label="Kapat"', $html);
        self::assertStringNotContainsString('<div class="">', $html);
    }

    public function test_alert_title_part_matches_the_classic_title(): void
    {
        $classic = Blade::render('<acun:alert variant="success" title="Sipariş kaydedildi">3 ürün eklendi.</acun:alert>');
        $parts = Blade::render(<<<'BLADE'
            <acun:alert variant="success">
                <acun:alert.title>Sipariş kaydedildi</acun:alert.title>
                <acun:alert.description>3 ürün eklendi.</acun:alert.description>
            </acun:alert>
        BLADE);

        self::assertStringContainsString('<p class="font-medium">Sipariş kaydedildi</p>', $classic);
        self::assertStringContainsString('<div class="mt-1">3 ürün eklendi.</div>', $classic);
        self::assertStringContainsString('<p data-slot="alert-title" class="font-medium">Sipariş kaydedildi</p>', $parts);
        self::assertStringContainsString('<div data-slot="alert-description" class="not-first:mt-1">3 ürün eklendi.</div>', $parts);

        // A description alone starts at the top, level with the icon (not-first: adds no gap).
        $alone = Blade::render('<acun:alert><acun:alert.description>Bilgi</acun:alert.description></acun:alert>');
        self::assertMatchesRegularExpression('/<div class="flex-1">\s*<div data-slot="alert-description" class="not-first:mt-1">Bilgi<\/div>/', $alone);
    }

    public function test_a_classic_alert_is_unchanged(): void
    {
        $html = Blade::render('<acun:alert>Bilgi <strong>notu</strong></acun:alert>');

        self::assertMatchesRegularExpression('/<div class="flex-1">\s*<div class="">Bilgi <strong>notu<\/strong><\/div>\s*<\/div>/', $html);
        self::assertStringNotContainsString('data-slot', $html);
    }

    /** Collapses whitespace so markup from different templates compares by content. */
    private static function normalize(string $html): string
    {
        return trim((string) preg_replace(['/\s+/', '/>\s+</', '/\s+>/', '/<(\w+)\s+/'], [' ', '><', '>', '<$1 '], $html));
    }
}
