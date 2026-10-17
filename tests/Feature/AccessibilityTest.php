<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

/** Smaller accessibility promises: heading levels, the confirmation alertdialog, the skip link. */
final class AccessibilityTest extends TestCase
{
    public function test_cards_and_accordions_take_a_heading_level(): void
    {
        self::assertMatchesRegularExpression('/<h3 class="text-sm font-semibold[^"]*">Siparişler<\/h3>/', Blade::render('<acun:card title="Siparişler">x</acun:card>'), 'h3 by default.');
        self::assertMatchesRegularExpression('/<h2 class="text-sm font-semibold[^"]*">Siparişler<\/h2>/', Blade::render('<acun:card title="Siparişler" heading-level="2">x</acun:card>'));

        $parts = Blade::render('<acun:card heading-level="4"><acun:card.header title="Kargo" /><acun:card.body>x</acun:card.body></acun:card>');
        self::assertMatchesRegularExpression('/<h4 class="text-sm font-semibold[^"]*">Kargo/', $parts, 'The header reads it from the card.');

        $accordion = Blade::render('<acun:accordion heading-level="2"><acun:accordion-item name="iade" title="İade nasıl yapılır?">Yanıt</acun:accordion-item></acun:accordion>');
        self::assertMatchesRegularExpression('/<h2>\s*<button/', $accordion);
        self::assertStringNotContainsString('<h3>', $accordion);
    }

    public function test_the_confirmation_dialog_is_an_alertdialog_described_by_its_question(): void
    {
        $html = Blade::render('<acun:confirmation-dialog name="urun-sil" title="Ürünü sil" description="Bu ürün kalıcı olarak silinecek." />');

        self::assertStringContainsString('role="alertdialog"', $html);
        self::assertStringContainsString('aria-labelledby="urun-sil-title" aria-describedby="urun-sil-description"', $html);
        self::assertStringContainsString('<p id="urun-sil-description"', $html);

        // A plain modal stays a dialog.
        self::assertStringContainsString('role="dialog"', Blade::render('<acun:modal name="m" title="Başlık">İçerik</acun:modal>'));
    }

    public function test_the_panel_layouts_start_with_a_skip_link_to_the_content(): void
    {
        foreach (['<acun:layout.sidebar brand-name="Panel">İçerik</acun:layout.sidebar>', '<acun:layout.horizontal brand-name="Panel">İçerik</acun:layout.horizontal>'] as $layout) {
            $html = Blade::render($layout);

            self::assertMatchesRegularExpression('/<body[^>]*>\s*(?:<!--.*?-->\s*)*<a href="#main-content" class="sr-only focus:not-sr-only/s', $html);
            self::assertStringContainsString('İçeriğe geç', $html);
            self::assertMatchesRegularExpression('/<main id="main-content" tabindex="-1"/', $html);
        }
    }
}
