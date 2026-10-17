<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;

/** acun:layout.print: a standalone page to print or save as PDF. */
final class PrintLayoutTest extends TestCase
{
    public function test_the_page_holds_the_title_filters_content_totals_and_signatures(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:layout.print
                title="Sipariş raporu"
                subtitle="14.10.2026 · 3 sipariş"
                notice="Yalnız ilk 3 sipariş gösterilir."
                :filters="['Durum' => 'Teslim edildi', ['label' => 'Kanal', 'value' => 'Web']]"
                :totals="['Ara toplam' => '₺1.000', 'KDV' => '₺200', 'Genel toplam' => '₺1.200']"
                :signatures="['Hazırlayan', ['label' => 'Onaylayan', 'name' => 'Ayşe Yılmaz']]"
                class="report"
            >
                <acun:slot:aside>No. 2026-0142</acun:slot:aside>
                <table><tr><td data-align="end">₺400</td></tr></table>
                <acun:slot:footer>Teşekkür ederiz.</acun:slot:footer>
            </acun:layout.print>
            BLADE);

        self::assertStringStartsWith('<!DOCTYPE html>', ltrim($html));
        self::assertStringContainsString('<title>Sipariş raporu</title>', $html);
        self::assertStringContainsString('<body class="report">', $html);
        self::assertStringContainsString('<h1 class="ac-print-title">Sipariş raporu</h1>', $html);
        self::assertStringContainsString('<p class="ac-print-subtitle">14.10.2026 · 3 sipariş</p>', $html);
        self::assertStringContainsString('<div><dt>Durum</dt><dd>Teslim edildi</dd></div>', $html);
        self::assertStringContainsString('<div><dt>Kanal</dt><dd>Web</dd></div>', $html);
        self::assertStringContainsString('<p class="ac-print-notice">Yalnız ilk 3 sipariş gösterilir.</p>', $html);
        self::assertStringContainsString('<div class="ac-print-aside">No. 2026-0142</div>', $html);
        self::assertStringContainsString('<main class="ac-print-content">', $html);
        self::assertStringContainsString('<td data-align="end">₺400</td>', $html);
        // The last total is the grand total.
        self::assertStringContainsString('<div><dt>KDV</dt><dd>₺200</dd></div>', $html);
        self::assertStringContainsString('<div class="ac-print-grand"><dt>Genel toplam</dt><dd>₺1.200</dd></div>', $html);
        self::assertStringContainsString('<div class="ac-print-signature-label">Hazırlayan</div>', $html);
        self::assertStringContainsString('<div class="ac-print-signature-name">Ayşe Yılmaz</div>', $html);
        self::assertStringContainsString('<footer class="ac-print-footer">Teşekkür ederiz.</footer>', $html);
        self::assertStringContainsString('@page { size: A4 portrait; margin: 12mm;', $html);
        self::assertStringContainsString('content: "Sayfa " counter(page) " / " counter(pages);', $html);
        self::assertStringContainsString('html { color-scheme: light;', $html);
        self::assertStringContainsString('<button type="button" data-ac-print="print">Yazdır</button>', $html);
        self::assertStringNotContainsString('window.addEventListener(\'load\'', $html);
    }

    public function test_page_options_auto_print_and_the_nonce(): void
    {
        Vite::useCspNonce('n0nce');

        $html = Blade::render('<acun:layout.print title="Fatura" size="A5" orientation="landscape" margin="8mm" :page-numbers="false" :toolbar="false" auto-print>İçerik</acun:layout.print>');

        self::assertStringContainsString('@page { size: A5 landscape; margin: 8mm; }', $html);
        self::assertStringNotContainsString('counter(page)', $html);
        self::assertStringNotContainsString('data-ac-print="print"', $html);
        self::assertStringContainsString("window.addEventListener('load', function () { window.print(); });", $html);
        self::assertStringContainsString('<style nonce="n0nce">', $html);
        self::assertStringContainsString('<script nonce="n0nce">', $html);
        // Nothing empty is printed.
        self::assertStringNotContainsString('ac-print-filters', substr($html, strpos($html, '<body')));
        self::assertStringNotContainsString('ac-print-totals', substr($html, strpos($html, '<body')));
        self::assertStringNotContainsString('ac-print-signatures', substr($html, strpos($html, '<body')));
    }

    public function test_page_options_that_are_not_a_size_or_a_length_fall_back_to_the_defaults(): void
    {
        $html = Blade::render('<acun:layout.print size="A4; } body { display: none" orientation="sideways" margin="1cm</style><script>x</script>">İçerik</acun:layout.print>');

        self::assertStringContainsString('@page { size: A4 portrait; margin: 12mm;', $html);
        self::assertStringNotContainsString('<script>x</script>', $html);
    }
}
