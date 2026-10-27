<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Throwable;

/**
 * Accordion indicator, disabled items, lazy content and the free trigger.
 */
final class AccordionOptionsTest extends TestCase
{
    private function heading(string $html): string
    {
        preg_match('/<button\b.*?<\/button>/s', $html, $match);

        return $match[0] ?? '';
    }

    public function test_the_default_sign_is_a_chevron_after_the_text(): void
    {
        $button = $this->heading(Blade::render('<acun:accordion><acun:accordion.item name="a" title="Soru">Yanıt</acun:accordion.item></acun:accordion>'));

        self::assertMatchesRegularExpression('/Soru<\/span>\s*<svg[^>]*group-aria-expanded:rotate-180/', $button);
    }

    public function test_the_accordion_chooses_the_sign_and_its_side_for_every_heading(): void
    {
        $plus = $this->heading(Blade::render('<acun:accordion indicator="plus" indicator-position="start"><acun:accordion.item name="a" title="Soru">Yanıt</acun:accordion.item></acun:accordion>'));
        // Plus before the text; its vertical bar folds away when open (plus becomes minus).
        self::assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?d="M12 5v14" class="[^"]*group-aria-expanded:scale-y-0[^"]*".*?<\/svg>\s*<span[^>]*>Soru/s', $plus);

        $none = $this->heading(Blade::render('<acun:accordion indicator="none"><acun:accordion.item name="a"><acun:accordion.heading>Soru</acun:accordion.heading><acun:accordion.content>Yanıt</acun:accordion.content></acun:accordion.item></acun:accordion>'));
        self::assertStringNotContainsString('<svg', $none);
    }

    public function test_a_heading_can_bring_its_own_sign(): void
    {
        $button = $this->heading(Blade::render(<<<'BLADE'
            <acun:accordion indicator="plus">
                <acun:accordion.item name="a">
                    <acun:accordion.heading>
                        Soru
                        <acun:slot:indicator><i data-sign class="group-aria-expanded:rotate-90"></i></acun:slot:indicator>
                    </acun:accordion.heading>
                    <acun:accordion.content>Yanıt</acun:accordion.content>
                </acun:accordion.item>
            </acun:accordion>
        BLADE));

        self::assertMatchesRegularExpression('/Soru\s*<\/span>\s*<span[^>]*aria-hidden="true"><i data-sign class="group-aria-expanded:rotate-90"><\/i><\/span>/', $button);
        self::assertStringNotContainsString('<svg', $button, 'The custom sign replaces the accordion\'s plus.');
    }

    public function test_a_disabled_item_cannot_be_opened(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:accordion>
                <acun:accordion.item name="a" title="Açık">Yanıt</acun:accordion.item>
                <acun:accordion.item name="b" title="Kapalı" disabled>Yanıt</acun:accordion.item>
                <acun:accordion.item name="c" disabled>
                    <acun:accordion.trigger>Serbest</acun:accordion.trigger>
                    <acun:accordion.content>Yanıt</acun:accordion.content>
                </acun:accordion.item>
            </acun:accordion>
        BLADE);

        // The trigger of each item, in order: a enabled, b (heading) and c (free trigger) disabled.
        preg_match_all('/<button\s+type="button"\s+data-slot="accordion-trigger"[^>]*>/', $html, $triggers);
        self::assertCount(3, $triggers[0]);
        self::assertDoesNotMatchRegularExpression('/\sdisabled[\s>]/', $triggers[0][0]);
        self::assertMatchesRegularExpression('/\sdisabled[\s>]/', $triggers[0][1]);
        self::assertMatchesRegularExpression('/\sdisabled[\s>]/', $triggers[0][2]);
    }

    public function test_lazy_content_waits_for_the_first_opening(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:accordion open="a">
                <acun:accordion.item name="a"><acun:accordion.heading>A</acun:accordion.heading><acun:accordion.content lazy class="text-xs">Ağır içerik</acun:accordion.content></acun:accordion.item>
                <acun:accordion.item name="b" title="B">Hafif içerik</acun:accordion.item>
            </acun:accordion>
        BLADE);

        // A section the server draws open renders its lazy content at once; a closed one keeps it in a <template>
        // until its first opening (Alpine's x-if, or the runtime itself without Alpine).
        self::assertMatchesRegularExpression('/<div class="[^"]*text-xs[^"]*">\s*Ağır içerik/', $html);
        self::assertStringNotContainsString('<template', $html);

        $closed = Blade::render('<acun:accordion><acun:accordion.item name="a"><acun:accordion.heading>A</acun:accordion.heading><acun:accordion.content lazy class="text-xs">Ağır içerik</acun:accordion.content></acun:accordion.item></acun:accordion>');
        self::assertMatchesRegularExpression('/<template data-acun-lazy x-if="wasOpened\(\'a\'\)">\s*<div class="[^"]*text-xs[^"]*">\s*Ağır içerik/', $closed);
        self::assertDoesNotMatchRegularExpression('/<template[^>]*>\s*<div[^>]*>\s*Hafif içerik/', $html);
    }

    public function test_the_free_trigger_is_a_plain_button_wired_to_its_item(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:accordion>
                <acun:accordion.item name="siparis-1042">
                    <div class="flex items-center gap-3">
                        <acun:accordion.trigger class="flex-1 text-left">Sipariş #1042</acun:accordion.trigger>
                        <a href="/kargo-takip">Kargoyu izle</a>
                    </div>
                    <acun:accordion.content>Ürünler</acun:accordion.content>
                </acun:accordion.item>
            </acun:accordion>
        BLADE);

        // The ids, aria-controls and aria-labelledby are the runtime's (js/plugins/accordion.js).
        self::assertMatchesRegularExpression('/<button\s+type="button"\s+data-slot="accordion-trigger"\s+aria-expanded="false"[^>]*class="[^"]*flex-1 text-left[^"]*"\s*>Sipariş #1042<\/button>/', $html);
        self::assertStringNotContainsString('px-5 py-4', $html, 'No heading styles.');
        self::assertMatchesRegularExpression('/data-slot="accordion-item" data-name="siparis-1042"/', $html);
    }

    public function test_the_free_trigger_outside_an_item_says_where_it_belongs(): void
    {
        try {
            Blade::render('<acun:accordion><acun:accordion.trigger>Aç</acun:accordion.trigger></acun:accordion>');
            self::fail('Expected an exception.');
        } catch (Throwable $error) {
            $cause = $error->getPrevious() ?? $error;
            self::assertStringContainsString('acun:accordion.trigger must be inside an acun:accordion-item with a name.', $cause->getMessage());
        }
    }
}
