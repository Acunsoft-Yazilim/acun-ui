<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

/** Smaller options of existing components: the button's count, the stat card's value colour and hint, card tabs. */
final class ComponentOptionsTest extends TestCase
{
    public function test_a_button_shows_a_count_after_its_text(): void
    {
        $html = Blade::render('<acun:button badge="3">Siparişler</acun:button>');
        // Framed with the text colour, no fill of its own: the number keeps the button's text contrast
        // (a bg-current/15 tint gave 3.51-3.79:1; tests/js/contrast.test.mjs measures it in Chrome).
        self::assertMatchesRegularExpression('/Siparişler\s*<span class="inline-flex min-w-5[^"]*ring-current\/50[^"]*">3<\/span>/', $html);
        self::assertStringNotContainsString('bg-current', $html);

        self::assertStringContainsString('>12</span>', Blade::render('<acun:button href="/bildirimler" badge="12">Bildirimler</acun:button>'));
        self::assertStringNotContainsString('ring-current/50', Blade::render('<acun:button>Kaydet</acun:button>'));
    }

    public function test_a_stat_card_colours_its_value_and_adds_a_hint(): void
    {
        $html = Blade::render('<acun:stat-card label="Kâr" value="₺48.210" value-color="success" hint="Hedef: ₺45.000" />');
        self::assertMatchesRegularExpression('/<p class="mt-1 text-2xl font-semibold tabular-nums text-success-ink">₺48.210<\/p>/', $html);
        self::assertStringContainsString('Hedef: ₺45.000', $html);

        $plain = Blade::render('<acun:stat-card label="Sipariş" value="35" />');
        self::assertStringContainsString('text-ink">35</p>', $plain);
    }

    public function test_card_tabs_join_the_open_tab_to_a_framed_panel(): void
    {
        $html = Blade::render('<acun:tabs variant="cards" :tabs="[\'genel\' => \'Genel\', \'kargo\' => \'Kargo\']"><acun:tab-panel value="genel">G</acun:tab-panel></acun:tabs>');

        self::assertStringContainsString('-mb-px rounded-t-sm border', $html);
        self::assertStringContainsString("border-line bg-surface text-ink", $html);
        self::assertStringContainsString('rounded-b-sm border border-t-0 border-line bg-surface p-4', $html);
        self::assertStringNotContainsString('border-b-2', $html);
    }
}
