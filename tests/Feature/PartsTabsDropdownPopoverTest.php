<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

final class PartsTabsDropdownPopoverTest extends TestCase
{
    private const TABS = <<<'BLADE'
        <acun:tabs class="mt-6">
            <acun:tabs.list aria-label="Müşteri ayrıntısı">
                <acun:tabs.trigger value="overview">Genel Bakış</acun:tabs.trigger>
                <acun:tabs.trigger value="orders" badge="8" icon="truck" wire:click="say">Siparişler</acun:tabs.trigger>
                <acun:tabs.trigger value="settings" disabled>Ayarlar</acun:tabs.trigger>
            </acun:tabs.list>

            <acun:tabs.panel value="overview" class="space-y-2">Özet</acun:tabs.panel>
            <acun:tabs.panel value="orders">Siparişler</acun:tabs.panel>
            <acun:tabs.panel value="settings">Ayarlar</acun:tabs.panel>
        </acun:tabs>
        BLADE;

    // ---- tabs -------------------------------------------------------------

    public function test_tabs_in_parts_render_the_aria_tab_pattern(): void
    {
        $html = Blade::render(self::TABS);

        // The container: its own Alpine scope and id scope, attributes on the root.
        self::assertMatchesRegularExpression('/<div\s+x-data="\{\s*tab: null,/', $html);
        self::assertStringContainsString("x-id=\"['tab', 'tabpanel']\"", $html);
        self::assertMatchesRegularExpression('/data-slot="tabs"\s+class="mt-6"/', $html);
        self::assertStringNotContainsString('x-modelable', $html);
        self::assertStringNotContainsString('<div class="pt-4">', $html, 'No classic panel wrapper in parts mode.');

        // The list: tablist, arrow keys / Home / End, its own attributes.
        self::assertMatchesRegularExpression('/role="tablist"\s+data-slot="tabs-list"/', $html);
        self::assertStringContainsString('x-on:keydown.right.prevent="focusTab($el, 1)"', $html);
        self::assertStringContainsString('x-on:keydown.left.prevent="focusTab($el, -1)"', $html);
        self::assertStringContainsString("x-on:keydown.home.prevent=\"focusTab(\$el, 'first')\"", $html);
        self::assertStringContainsString("x-on:keydown.end.prevent=\"focusTab(\$el, 'last')\"", $html);
        self::assertMatchesRegularExpression('/class="flex gap-1 overflow-x-auto[^"]*" aria-label="Müşteri ayrıntısı"/', $html);

        // A trigger: role, ids from $id, aria-selected, roving tabindex.
        self::assertSame(3, substr_count($html, 'role="tab"'));
        self::assertSame(3, substr_count($html, 'data-slot="tabs-trigger"'));
        self::assertStringContainsString('data-value="orders"', $html);
        self::assertStringContainsString(":id=\"\$id('tab', 'orders')\"", $html);
        self::assertStringContainsString(":aria-controls=\"\$id('tabpanel', 'orders')\"", $html);
        self::assertStringContainsString(":aria-selected=\"selected('orders').toString()\"", $html);
        self::assertStringContainsString(":tabindex=\"selected('orders') ? 0 : -1\"", $html);
        self::assertStringContainsString("x-on:click=\"tab = 'orders'; \$el.scrollIntoView", $html);

        // The panel: labelled by its trigger, shown with x-show, the classic pt-4 spacing.
        self::assertSame(3, substr_count($html, 'role="tabpanel"'));
        self::assertStringContainsString("x-show=\"selected('orders')\"", $html);
        self::assertStringContainsString(":id=\"\$id('tabpanel', 'orders')\"", $html);
        self::assertStringContainsString(":aria-labelledby=\"\$id('tab', 'orders')\"", $html);
        self::assertStringContainsString('class="pt-4 space-y-2" tabindex="0"', $html);
    }

    public function test_tab_triggers_take_a_badge_an_icon_disabled_and_their_own_attributes(): void
    {
        $html = Blade::render(self::TABS);

        self::assertMatchesRegularExpression('/data-value="orders".*?class="[^"]*" wire:click="say"\s*>\s*<svg[^>]*class="size-4 flex-none"/s', $html);
        self::assertMatchesRegularExpression('/Siparişler\s*<span class="rounded-full[^"]*"[^>]*>8<\/span>/', $html);
        self::assertMatchesRegularExpression('/data-value="settings".*?\sdisabled\s/s', $html);
        self::assertStringNotContainsString('<span class="rounded-full', explode('data-value="orders"', $html)[0], 'Only the trigger with badge has one.');
    }

    public function test_the_first_enabled_trigger_opens_when_active_is_not_given(): void
    {
        $html = Blade::render(self::TABS);

        // Resolved in the browser: the active prop, else the first enabled trigger of this instance.
        self::assertStringContainsString('initial: null,', $html);
        self::assertStringContainsString("this.tab = this.initial ?? first?.dataset.value ?? null;", $html);
        self::assertStringContainsString("find(el => ! el.disabled && el.closest('[data-slot=tabs]') === this.\$root)", $html);
        self::assertStringContainsString("this.\$watch('tab', () => this.resolveTab());", $html);

        $active = Blade::render('<acun:tabs active="orders"><acun:tabs.list><acun:tabs.trigger value="orders">S</acun:tabs.trigger></acun:tabs.list></acun:tabs>');
        self::assertStringContainsString("tab: 'orders',", $active);
        self::assertStringContainsString("initial: 'orders',", $active);
    }

    public function test_variant_and_size_reach_the_parts(): void
    {
        $underline = Blade::render('<acun:tabs><acun:tabs.list><acun:tabs.trigger value="a">A</acun:tabs.trigger></acun:tabs.list></acun:tabs>');
        // md is the classic look.
        self::assertStringContainsString('shrink-0 border-b-2', $underline);
        self::assertStringContainsString('px-3 py-2 text-sm gap-2', $underline);
        self::assertStringContainsString("'border-primary-ink text-primary-tint-ink'", $underline);

        $pills = Blade::render('<acun:tabs variant="pills" size="sm"><acun:tabs.list><acun:tabs.trigger value="a" icon="map" badge="3">A</acun:tabs.trigger></acun:tabs.list></acun:tabs>');
        self::assertMatchesRegularExpression('/role="tablist"[^>]*class="flex gap-1 flex-wrap"/', $pills);
        self::assertStringNotContainsString('overflow-x-auto', $pills);
        self::assertStringContainsString('px-3 py-1.5 text-xs gap-1.5 rounded-sm', $pills);
        self::assertStringContainsString("'bg-primary text-on-primary'", $pills);
        self::assertStringContainsString('class="size-3.5 flex-none"', $pills);

        $large = Blade::render('<acun:tabs size="lg"><acun:tabs.list><acun:tabs.trigger value="a" icon="map">A</acun:tabs.trigger></acun:tabs.list></acun:tabs>');
        self::assertStringContainsString('px-4 py-2.5 text-base gap-2', $large);
        self::assertStringContainsString('class="size-5 flex-none"', $large);
    }

    public function test_tab_values_with_spaces_give_valid_ids(): void
    {
        $html = self::decodeUnicodeEscapes(Blade::render('<acun:tabs><acun:tabs.list><acun:tabs.trigger value="genel bakış">G</acun:tabs.trigger></acun:tabs.list><acun:tabs.panel value="genel bakış">x</acun:tabs.panel></acun:tabs>'));

        self::assertStringContainsString(":id=\"\$id('tab', 'genel-bakış')\"", $html);
        self::assertStringContainsString(":aria-labelledby=\"\$id('tab', 'genel-bakış')\"", $html);
        self::assertStringContainsString("x-show=\"selected('genel bakış')\"", $html);
    }

    public function test_tabs_bind_the_open_tab_with_wire_model(): void
    {
        Livewire::test(RouteTabs::class)
            ->assertSet('activeTab', 'orders')
            ->assertSeeHtml('x-modelable="tab"')
            ->assertSeeHtml('wire:model="activeTab"')
            ->assertSeeHtml('data-slot="tabs-panel"')
            ->set('activeTab', 'settings')
            ->assertSet('activeTab', 'settings');

        self::assertMatchesRegularExpression('/x-modelable="tab"\s+data-slot="tabs"\s+x-model="tab"/', Blade::render('<acun:tabs x-model="tab"><acun:tabs.list><acun:tabs.trigger value="a">A</acun:tabs.trigger></acun:tabs.list></acun:tabs>'));
    }

    public function test_classic_tabs_are_drawn_by_the_parts_with_their_aria_and_keys(): void
    {
        $classic = Blade::render('<acun:tabs :tabs="[\'a\' => \'A\', \'b\' => \'B\']" wire:model.live="tab"><acun:tab-panel value="a">x</acun:tab-panel><acun:tab-panel value="b">y</acun:tab-panel></acun:tabs>');
        self::assertMatchesRegularExpression('/x-modelable="tab"\s+data-slot="tabs" wire:model.live="tab"/', $classic);
        // Each tab names its panel and each panel its tab; only the open tab is in the Tab order.
        self::assertStringContainsString(':aria-controls="$id(\'tabpanel\', \'b\')"', $classic);
        self::assertStringContainsString(':aria-labelledby="$id(\'tab\', \'b\')"', $classic);
        self::assertStringContainsString(':tabindex="selected(\'a\') ? 0 : -1"', $classic);
        self::assertStringContainsString('x-on:keydown.right.prevent="focusTab($el, 1)"', $classic);

        $plain = '<acun:tabs :tabs="[\'a\' => \'A\']"><acun:tab-panel value="a">x</acun:tab-panel></acun:tabs>';
        self::assertSame(Blade::render($plain), Blade::render(str_replace('<acun:tabs ', '<acun:tabs size="md" ', $plain)), 'md is the default size.');

        $small = Blade::render(str_replace('<acun:tabs ', '<acun:tabs size="sm" variant="pills" ', $plain));
        self::assertStringContainsString('px-3 py-1.5 text-xs', $small);
        self::assertStringContainsString('flex-wrap', $small);
    }

    /** acun:tab-panel is acun:tabs.panel: labelled by its tab, its spacing and attributes kept. */
    public function test_the_classic_tab_panel_is_the_parts_panel(): void
    {
        $html = Blade::render('<acun:tab-panel value="1" class="p-2">A</acun:tab-panel>');

        self::assertStringContainsString('role="tabpanel"', $html);
        self::assertStringContainsString('x-show="selected(\'1\')"', $html);
        self::assertStringContainsString('class="pt-4 p-2"', $html);
    }

    public function test_nested_tabs_keep_their_own_scope_and_mode(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:tabs :tabs="['siparisler' => 'Siparişler']" variant="pills">
                <acun:tab-panel value="siparisler">
                    <acun:tabs variant="underline">
                        <acun:tabs.list><acun:tabs.trigger value="sabah">Sabah</acun:tabs.trigger></acun:tabs.list>
                        <acun:tabs.panel value="sabah">08:00</acun:tabs.panel>
                    </acun:tabs>
                </acun:tab-panel>
            </acun:tabs>
            BLADE);

        // Both are drawn by the parts, each with its own x-id scope and variant.
        self::assertSame(2, substr_count($html, "x-id=\"['tab', 'tabpanel']\""));
        self::assertStringContainsString("selected('siparisler') ? 'bg-primary text-on-primary'", $html, 'The outer keeps pills.');
        self::assertMatchesRegularExpression('/data-slot="tabs-trigger".*?shrink-0 border-b-2/s', $html);
        self::assertStringContainsString("x-show=\"selected('sabah')\"", $html);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function tabPartsWithoutValue(): array
    {
        return [
            'trigger' => ['<acun:tabs><acun:tabs.list><acun:tabs.trigger>A</acun:tabs.trigger></acun:tabs.list></acun:tabs>', 'acun:tabs.trigger needs a value'],
            'panel' => ['<acun:tabs><acun:tabs.panel>A</acun:tabs.panel></acun:tabs>', 'acun:tabs.panel needs a value'],
        ];
    }

    #[DataProvider('tabPartsWithoutValue')]
    public function test_tab_parts_need_a_value(string $blade, string $message): void
    {
        $this->assertRenderingFails($blade, $message);
    }

    // ---- dropdown ---------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function dropdownItems(): array
    {
        return [
            'button' => ['<acun:NAME>Yenile</acun:NAME>'],
            'button with attributes' => ['<acun:NAME type="submit" wire:click="sil" class="font-bold" disabled>Sil</acun:NAME>'],
            'link' => ['<acun:NAME href="/siparisler" wire:navigate>Siparişler</acun:NAME>'],
            'external link' => ['<acun:NAME href="https://docs.example.test" external>Doküman</acun:NAME>'],
            'icon' => ['<acun:NAME icon="pencil-square">Düzenle</acun:NAME>'],
            'danger' => ['<acun:NAME icon="trash" danger>Sil</acun:NAME>'],
        ];
    }

    #[DataProvider('dropdownItems')]
    public function test_dropdown_item_is_an_alias_of_dropdown_dot_item(string $blade): void
    {
        $canonical = Blade::render(str_replace('NAME', 'dropdown.item', $blade));

        self::assertSame($canonical, Blade::render(str_replace('NAME', 'dropdown-item', $blade)));
        self::assertNotSame('', trim($canonical));
    }

    public function test_dropdown_items_keep_their_classic_markup_and_take_an_icon_or_danger(): void
    {
        self::assertSame(
            '<button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-body hover:bg-hover text-left">Yenile</button>',
            trim(Blade::render('<acun:dropdown.item>Yenile</acun:dropdown.item>')),
        );

        $icon = Blade::render('<acun:dropdown.item href="/r" icon="truck">Kargo</acun:dropdown.item>');
        self::assertMatchesRegularExpression('/<a href="\/r"\s+class="[^"]*"><svg[^>]*class="size-4 flex-none text-faint"/', $icon);

        $danger = Blade::render('<acun:dropdown.item icon="trash" danger>Sil</acun:dropdown.item>');
        self::assertStringContainsString('text-danger-ink hover:bg-danger-tint', $danger);
        self::assertStringNotContainsString('text-body', $danger);
        self::assertStringContainsString('class="size-4 flex-none"', $danger, 'The icon takes the red text colour.');
    }

    public function test_dropdown_in_parts_wires_the_trigger_and_the_panel(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:dropdown align="start" position="top" class="ms-2">
                <acun:dropdown.trigger data-test="siparis-islemleri"><acun:button variant="white">İşlemler</acun:button></acun:dropdown.trigger>
                <acun:dropdown.content class="w-64">
                    <acun:dropdown.heading>Sipariş</acun:dropdown.heading>
                    <acun:dropdown.item icon="pencil-square">Düzenle</acun:dropdown.item>
                    <acun:dropdown.separator />
                    <acun:dropdown.item icon="trash" danger>Sil</acun:dropdown.item>
                </acun:dropdown.content>
            </acun:dropdown>
            BLADE);

        // The container: Alpine state, id scope, closing on Escape / outside; attributes on the root.
        self::assertStringContainsString("x-id=\"['dropdown-content']\"", $html);
        self::assertStringContainsString('x-on:keydown.escape.window="if (open) closeMenu($el.contains(document.activeElement))"', $html);
        self::assertStringContainsString('x-on:click.outside="open = false"', $html);
        self::assertStringContainsString('data-slot="dropdown"', $html);
        self::assertStringContainsString('class="relative inline-block ms-2"', $html);

        // The trigger wraps the button; the button gets aria-expanded / aria-controls.
        self::assertMatchesRegularExpression('/data-slot="dropdown-trigger"\s+x-ref="dropdownTrigger"\s+x-on:click="open = ! open"\s+x-on:keydown.down.prevent="openMenu\(1\)"\s+x-on:keydown.up.prevent="openMenu\(-1\)"\s+data-test="siparis-islemleri"/', $html);
        self::assertStringContainsString('<button :aria-expanded="open.toString()" :aria-controls="$id(\'dropdown-content\')" type="button"', $html);

        // The panel: placement from the container, its own attributes, keyboard.
        self::assertStringContainsString('data-slot="dropdown-content"', $html);
        self::assertStringContainsString(':id="$id(\'dropdown-content\')"', $html);
        self::assertStringContainsString('x-on:keydown="menuKey($event)"', $html);
        self::assertStringContainsString('class="absolute z-40 max-h-64 overflow-auto rounded-sm bg-surface py-1 shadow-lg min-w-[12rem] left-0 bottom-full mb-2 w-64"', $html);

        // Heading, separator, items.
        self::assertStringContainsString('<div data-slot="dropdown-heading" class="px-3 pt-2 pb-1 font-mono text-2xs', $html);
        self::assertStringContainsString('<hr data-slot="dropdown-separator" class="my-1 border-line">', $html);
        self::assertStringContainsString('text-danger-ink hover:bg-danger-tint', $html);

        // No classic wrapper.
        self::assertStringNotContainsString('<div x-on:click="open = !open">', $html);
    }

    public function test_dropdown_content_defaults_to_the_end_edge_below_and_full_width(): void
    {
        $parts = '<acun:dropdown%s><acun:dropdown.trigger><button type="button">Aç</button></acun:dropdown.trigger><acun:dropdown.content><acun:dropdown.item>A</acun:dropdown.item></acun:dropdown.content></acun:dropdown>';

        self::assertStringContainsString('min-w-[12rem] right-0 top-full mt-2"', Blade::render(sprintf($parts, '')));

        $full = Blade::render(sprintf($parts, ' full-width'));
        self::assertStringContainsString('class="relative block w-full"', $full);
        self::assertStringContainsString('shadow-lg left-0 right-0 top-full mt-2"', $full);
    }

    public function test_classic_dropdown_is_drawn_by_the_parts_with_their_aria_keys_and_attributes(): void
    {
        $html = Blade::render("<acun:dropdown class=\"ms-2\" id=\"siparis-menusu\" wire:key=\"menu-7\">\n<acun:slot:trigger><button>Aç</button></acun:slot:trigger>\n<acun:dropdown.heading>Sipariş</acun:dropdown.heading>\n<acun:dropdown.item href=\"/x\">Git</acun:dropdown.item>\n</acun:dropdown>");

        self::assertStringContainsString('x-id="[\'dropdown-content\']"', $html, 'The keyboard and focus handling of the parts.');
        self::assertStringContainsString('<button :aria-expanded="open.toString()"', $html);
        self::assertSame(1, substr_count($html, 'data-slot="dropdown-content"'));
        self::assertStringContainsString('Sipariş', $html);
        // Attributes are no longer dropped: they go on the root.
        self::assertMatchesRegularExpression('/data-slot="dropdown"\s+class="relative inline-block ms-2" id="siparis-menusu" wire:key="menu-7"/', $html);
    }

    public function test_a_trigger_button_after_livewire_markers_is_still_wired_and_other_elements_are_left_alone(): void
    {
        $marked = Blade::render('<acun:dropdown><acun:dropdown.trigger><!--[if BLOCK]><![endif]--><button type="button">Aç</button></acun:dropdown.trigger><acun:dropdown.content>x</acun:dropdown.content></acun:dropdown>');
        self::assertStringContainsString('<!--[if BLOCK]><![endif]--><button :aria-expanded="open.toString()"', $marked);

        $link = Blade::render('<acun:dropdown><acun:dropdown.trigger><a href="#">Aç</a></acun:dropdown.trigger><acun:dropdown.content>x</acun:dropdown.content></acun:dropdown>');
        self::assertStringContainsString('<a :aria-expanded="open.toString()"', $link);

        $avatar = Blade::render('<acun:dropdown><acun:dropdown.trigger><span>AE</span></acun:dropdown.trigger><acun:dropdown.content>x</acun:dropdown.content></acun:dropdown>');
        self::assertStringContainsString('<span>AE</span>', $avatar);
        self::assertStringNotContainsString('aria-expanded', $avatar);
    }

    public function test_a_dropdown_without_a_trigger_throws(): void
    {
        $this->assertRenderingFails('<acun:dropdown><acun:dropdown.item>A</acun:dropdown.item></acun:dropdown>', 'acun:dropdown needs a trigger');
    }

    public function test_a_dropdown_in_parts_nested_in_a_classic_dropdown_keeps_both_modes(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:dropdown>
                <acun:slot:trigger><button>Dış</button></acun:slot:trigger>
                <acun:dropdown align="start">
                    <acun:dropdown.trigger><button type="button">İç</button></acun:dropdown.trigger>
                    <acun:dropdown.content><acun:dropdown.item>A</acun:dropdown.item></acun:dropdown.content>
                </acun:dropdown>
            </acun:dropdown>
            BLADE);

        self::assertSame(2, substr_count($html, 'data-slot="dropdown-content"'), 'The outer classic one is drawn by the parts too.');
        self::assertStringContainsString('min-w-[12rem] left-0 top-full mt-2"', $html, 'The inner panel takes the inner align.');
        self::assertStringContainsString('min-w-[12rem] right-0 top-full mt-2"', $html, 'The outer panel keeps its own (end).');
    }

    // ---- popover ----------------------------------------------------------

    public function test_popover_in_parts_renders_a_labelled_dialog(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:popover align="end" class="ms-1">
                <acun:popover.trigger><acun:button variant="white">Bilgi</acun:button></acun:popover.trigger>
                <acun:popover.content title="İade kuralı" data-test="kural">Ürünler teslimattan sonra 14 gün içinde iade edilebilir.</acun:popover.content>
            </acun:popover>
            BLADE);

        self::assertStringContainsString("x-id=\"['popover', 'popover-title']\"", $html);
        self::assertStringContainsString('data-slot="popover"', $html);
        self::assertStringContainsString('class="relative inline-block ms-1"', $html);
        self::assertStringContainsString('x-on:keydown.escape.window="if (open) closePopover($el.contains(document.activeElement))"', $html);

        self::assertStringContainsString('<div data-slot="popover-trigger" x-ref="popoverTrigger" x-on:click="open = ! open"', $html);
        self::assertStringContainsString('<button aria-haspopup="dialog" :aria-expanded="open.toString()" :aria-controls="$id(\'popover\')" type="button"', $html);

        self::assertMatchesRegularExpression('/role="dialog"\s+data-slot="popover-content"\s+x-show="open"/', $html);
        self::assertStringContainsString(':id="$id(\'popover\')"', $html);
        self::assertStringContainsString(':aria-labelledby="$id(\'popover-title\')"', $html);
        self::assertStringContainsString('<div :id="$id(\'popover-title\')" class="border-b', $html);
        self::assertMatchesRegularExpression('/class="absolute z-40 [^"]* w-72 top-full mt-2 right-0" data-test="kural"/', $html);
        self::assertStringContainsString('<div class="px-4 py-3 text-dim">Ürünler teslimattan', $html);
    }

    public function test_popover_content_takes_a_custom_width_and_works_without_a_title(): void
    {
        $html = Blade::render('<acun:popover position="top"><acun:popover.trigger><button type="button">?</button></acun:popover.trigger><acun:popover.content class="w-96" aria-label="Kural">Açıklama</acun:popover.content></acun:popover>');

        self::assertStringNotContainsString('w-72', $html);
        self::assertStringContainsString('bottom-full mb-2 left-0 w-96"', $html);
        self::assertStringNotContainsString('aria-labelledby', $html);
        self::assertStringContainsString('aria-label="Kural"', $html);
    }

    public function test_classic_popover_is_unchanged(): void
    {
        $html = Blade::render("<acun:popover title=\"Kural\" class=\"text-xs\">\n<acun:slot:trigger><button>?</button></acun:slot:trigger>\nAçıklama\n</acun:popover>");

        self::assertStringContainsString('<div x-on:click="open = ! open"><button>?</button></div>', $html);
        self::assertStringContainsString('<div class="px-4 py-3 text-dim text-xs">Açıklama</div>', $html);
        self::assertStringNotContainsString('data-slot', $html);
        self::assertStringNotContainsString('aria-expanded', $html);
    }

    public function test_a_popover_in_parts_nested_in_a_classic_popover_keeps_both_modes(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:popover title="Dış">
                <acun:slot:trigger><button>Dış</button></acun:slot:trigger>
                <acun:popover>
                    <acun:popover.trigger><button type="button">İç</button></acun:popover.trigger>
                    <acun:popover.content title="İç">İçerik</acun:popover.content>
                </acun:popover>
            </acun:popover>
            BLADE);

        self::assertStringContainsString('<div x-on:click="open = ! open"><button>Dış</button></div>', $html);
        self::assertSame(1, substr_count($html, 'data-slot="popover-content"'));
        self::assertSame(2, substr_count($html, 'role="dialog"'));
    }

    public function test_a_popover_without_a_trigger_throws(): void
    {
        $this->assertRenderingFails('<acun:popover title="Kural">Açıklama</acun:popover>', 'acun:popover needs a trigger');
    }

    /** Blade wraps an exception thrown in a view in a ViewException; the cause must be ours. */
    private function assertRenderingFails(string $blade, string $message): void
    {
        try {
            Blade::render($blade);
        } catch (Throwable $exception) {
            while (! $exception instanceof InvalidArgumentException && $exception->getPrevious() !== null) {
                $exception = $exception->getPrevious();
            }

            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertStringStartsWith($message, $exception->getMessage());

            return;
        }

        self::fail('Rendering should fail: '.$message);
    }
}

final class RouteTabs extends Component
{
    public string $activeTab = 'orders';

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <acun:tabs wire:model="activeTab" variant="underline" size="md">
                    <acun:tabs.list>
                        <acun:tabs.trigger value="overview">Genel Bakış</acun:tabs.trigger>
                        <acun:tabs.trigger value="orders" badge="8">Siparişler</acun:tabs.trigger>
                        <acun:tabs.trigger value="settings">Ayarlar</acun:tabs.trigger>
                    </acun:tabs.list>

                    <acun:tabs.panel value="overview">Özet</acun:tabs.panel>
                    <acun:tabs.panel value="orders">Siparişler</acun:tabs.panel>
                    <acun:tabs.panel value="settings">Ayarlar</acun:tabs.panel>
                </acun:tabs>
            </div>
            BLADE;
    }
}
