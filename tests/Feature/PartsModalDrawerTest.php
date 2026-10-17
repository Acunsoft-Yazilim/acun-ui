<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component;
use Livewire\Livewire;

final class PartsModalDrawerTest extends TestCase
{
    /**
     * The classic body around a nested Livewire component (the slot's first element): it
     * gives way (display: contents), with the component's root, when the component starts
     * with a part. The drawer's is its bodyClass.
     */
    private const COMPONENT_BODY = [
        'modal' => 'flex-1 overflow-y-auto p-5 [&:has(>[wire\:id]>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents [&>[wire\:id]:has(>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents',
        'drawer' => 'flex-1 p-5 overflow-y-auto [&:has(>[wire\:id]>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents [&>[wire\:id]:has(>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents',
    ];

    /** The click of modal.close / drawer.close: the dialog's close(), or a console error outside one (no ReferenceError). */
    private const CLOSE = [
        'modal' => "\$el.closest('[data-acun-modal]') ? close() : console.error('acun:modal.close is not inside an acun:modal.')",
        'drawer' => "\$el.closest('[data-acun-drawer]') ? close() : console.error('acun:drawer.close is not inside an acun:drawer.')",
    ];

    /** In a panel written in parts, a nested component's root that starts with a part is layout-neutral. */
    private const COMPONENT_ROOT = [
        'modal' => '[&>[wire\:id]:has(>:first-child:is([data-slot=modal-header],[data-slot=modal-body],[data-slot=modal-footer]))]:contents',
        'drawer' => '[&>[wire\:id]:has(>:first-child:is([data-slot=drawer-header],[data-slot=drawer-body],[data-slot=drawer-footer]))]:contents',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    // ---------------------------------------------------------------- modal

    public function test_modal_written_in_parts_fills_the_panel_with_its_parts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:modal name="musteri-ekle" max-width="lg">
                <acun:modal.header title="Yeni müşteri" description="Müşteri grubunu ve sorumlu temsilciyi seçin." class="bg-white" />
                <acun:modal.body class="space-y-4" wire:key="govde"><p>Form</p></acun:modal.body>
                <acun:modal.footer id="alt">
                    <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
                    <acun:button type="submit">Kaydet</acun:button>
                </acun:modal.footer>
            </acun:modal>
        BLADE);

        $xpath = $this->xpath($html);
        $panel = $xpath->query('//*[@role="dialog"]')->item(0);

        self::assertNotNull($panel);
        self::assertStringContainsString('sm:max-w-lg', $panel->getAttribute('class'));
        // The parts sit right in the panel, in order; no classic padded body around them.
        self::assertSame(
            ['modal-header', 'modal-body', 'modal-footer'],
            array_map(fn (\DOMElement $element) => $element->getAttribute('data-slot'), iterator_to_array($xpath->query('./*', $panel))),
        );
        self::assertStringNotContainsString('<div class="flex-1 overflow-y-auto p-5">', $html);

        // The header's title names the dialog, its description describes it.
        self::assertSame('musteri-ekle-title', $panel->getAttribute('aria-labelledby'));
        self::assertSame('musteri-ekle-description', $panel->getAttribute('aria-describedby'));
        self::assertFalse($panel->hasAttribute('aria-label'));
        self::assertSame('Yeni müşteri', trim($xpath->query('//h3[@id="musteri-ekle-title"]')->item(0)->textContent));
        self::assertSame('Müşteri grubunu ve sorumlu temsilciyi seçin.', trim($xpath->query('//p[@id="musteri-ekle-description"]')->item(0)->textContent));
        self::assertSame(1, substr_count($html, '<h3'), 'Only the header part draws a title.');

        // Attributes go on each part's own root.
        $header = $xpath->query('//*[@data-slot="modal-header"]')->item(0);
        self::assertStringContainsString('bg-white', $header->getAttribute('class'));
        self::assertStringContainsString('px-5 py-4', $header->getAttribute('class'));
        $body = $xpath->query('//*[@data-slot="modal-body"]')->item(0);
        self::assertSame('flex-1 overflow-y-auto p-5 space-y-4', $body->getAttribute('class'));
        self::assertSame('govde', $body->getAttribute('wire:key'));
        $footer = $xpath->query('//*[@data-slot="modal-footer"]')->item(0);
        self::assertSame('alt', $footer->getAttribute('id'));
        self::assertSame('flex flex-none items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50', $footer->getAttribute('class'));

        // The header's X goes through requestClose() as the classic one does.
        $x = $xpath->query('.//button', $header)->item(0);
        self::assertSame('requestClose()', $x->getAttribute('x-on:click'));
        // Same label as the classic modal's X (translated by lang/tr.json).
        self::assertSame(__('Close'), $x->getAttribute('aria-label'));

        // modal.close wraps the button without changing it and calls the modal's close().
        $close = $xpath->query('//*[@data-slot="modal-close"]')->item(0);
        self::assertSame(self::CLOSE['modal'], $close->getAttribute('x-on:click'));
        self::assertSame('contents', $close->getAttribute('class'));
        self::assertSame('Vazgeç', trim($xpath->query('./button', $close)->item(0)->textContent));
    }

    /** The documented form pattern: after the header, one form (display: contents) holds body and footer. */
    public function test_a_form_around_body_and_footer_keeps_parts_mode(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:modal name="musteri-ekle">
                <acun:modal.header title="Yeni müşteri" />
                <form wire:submit="kaydet" class="contents">
                    <acun:modal.body>Alanlar</acun:modal.body>
                    <acun:modal.footer><acun:button type="submit">Kaydet</acun:button></acun:modal.footer>
                </form>
            </acun:modal>
        BLADE);

        $xpath = $this->xpath($html);
        $panel = $xpath->query('//*[@role="dialog"]')->item(0);

        self::assertSame('musteri-ekle-title', $panel->getAttribute('aria-labelledby'));
        self::assertNotNull($xpath->query('./form/*[@data-slot="modal-body"]', $panel)->item(0));
        self::assertNotNull($xpath->query('./form/*[@data-slot="modal-footer"]', $panel)->item(0));
        self::assertStringNotContainsString('<div class="flex-1 overflow-y-auto p-5">', $html);
    }

    public function test_modal_header_without_title_uses_its_slot_as_the_heading(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:modal name="musteri">
                <acun:modal.header><acun:icon name="user" size="4" /> Müşteri <span class="badge">3</span></acun:modal.header>
                <acun:modal.body>…</acun:modal.body>
            </acun:modal>
        BLADE);

        $xpath = $this->xpath($html);
        $heading = $xpath->query('//h3[@id="musteri-title"]')->item(0);

        self::assertNotNull($heading->getElementsByTagName('svg')->item(0));
        self::assertStringContainsString('Müşteri', $heading->textContent);
        self::assertSame('musteri-title', $xpath->query('//*[@role="dialog"]')->item(0)->getAttribute('aria-labelledby'));
        self::assertStringNotContainsString('aria-describedby', $html);
        self::assertStringContainsString('items-center', $xpath->query('//*[@data-slot="modal-header"]')->item(0)->getAttribute('class'));
    }

    public function test_modal_header_with_title_puts_its_slot_under_the_title(): void
    {
        $html = Blade::render('<acun:modal name="siparis"><acun:modal.header title="Sipariş 1042"><span class="meta">3 ürün</span></acun:modal.header></acun:modal>');

        $xpath = $this->xpath($html);

        self::assertSame('Sipariş 1042', trim($xpath->query('//h3')->item(0)->textContent));
        self::assertNotNull($xpath->query('//h3/following-sibling::span[@class="meta"]')->item(0));
    }

    public function test_modal_in_parts_without_a_header_keeps_the_title_row_or_its_accessible_name(): void
    {
        // The title prop still draws the classic title row when there is no header part.
        $withTitle = Blade::render('<acun:modal name="siparis" title="Sipariş"><acun:modal.body>Gövde</acun:modal.body><acun:slot:footer><button>Tamam</button></acun:slot:footer></acun:modal>');
        $xpath = $this->xpath($withTitle);
        $panel = $xpath->query('//*[@role="dialog"]')->item(0);

        self::assertSame('siparis-title', $panel->getAttribute('aria-labelledby'));
        self::assertSame('Sipariş', trim($xpath->query('//h3[@id="siparis-title"]')->item(0)->textContent));
        self::assertNotNull($xpath->query('./*[@data-slot="modal-body"]', $panel)->item(0));
        // The classic footer slot still renders after the parts.
        self::assertStringContainsString('<button>Tamam</button>', $withTitle);

        $withoutTitle = Blade::render('<acun:modal name="siparis" aria-label="Sipariş bilgisi"><acun:modal.body>Gövde</acun:modal.body></acun:modal>');
        $panel = $this->xpath($withoutTitle)->query('//*[@role="dialog"]')->item(0);

        self::assertSame('Sipariş bilgisi', $panel->getAttribute('aria-label'));
        self::assertFalse($panel->hasAttribute('aria-labelledby'));
    }

    public function test_classic_modal_is_unchanged(): void
    {
        $html = Blade::render('<acun:modal name="siparis" title="Sipariş"><p>Gövde</p><acun:slot:footer><button>Tamam</button></acun:slot:footer></acun:modal>');

        self::assertStringContainsString('aria-labelledby="siparis-title"', $html);
        self::assertStringContainsString('<h3 id="siparis-title" class="text-base font-semibold text-gray-900 dark:text-zinc-100">Sipariş</h3>', $html);
        self::assertStringContainsString('aria-label="Kapat"', $html);
        self::assertMatchesRegularExpression('#<div class="flex-1 overflow-y-auto p-5">\s*<p>Gövde</p>\s*</div>#', $html);
        self::assertStringNotContainsString('data-slot', $html);
        self::assertStringNotContainsString('aria-describedby', $html);
    }

    public function test_a_classic_modal_body_may_start_with_a_trigger_or_a_close(): void
    {
        foreach ([
            '<acun:modal.trigger name="iptal-onay"><button>İptal et</button></acun:modal.trigger>',
            '<acun:modal.close><button>Geri</button></acun:modal.close>',
        ] as $first) {
            $html = Blade::render('<acun:modal name="teslimat" title="Teslimat">'.$first.'<p>Ayrıntı</p></acun:modal>');

            self::assertStringContainsString('<div class="flex-1 overflow-y-auto p-5">', $html, $first);
            self::assertStringContainsString('aria-labelledby="teslimat-title"', $html, $first);
        }
    }

    public function test_nested_modals_keep_their_own_mode(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:modal name="dis">
                <acun:modal name="ic">
                    <acun:modal.header title="İç" />
                    <acun:modal.body>İç gövde</acun:modal.body>
                </acun:modal>
            </acun:modal>
        BLADE);

        $xpath = $this->xpath($html);
        $dialogs = $xpath->query('//*[@role="dialog"]');

        // The outer modal starts with the inner modal, not with a part: classic body.
        self::assertSame('Pencere', $dialogs->item(0)->getAttribute('aria-label'));
        self::assertNotNull($xpath->query('./div[@class="flex-1 overflow-y-auto p-5"]', $dialogs->item(0))->item(0));
        self::assertSame('ic-title', $dialogs->item(1)->getAttribute('aria-labelledby'));
        self::assertSame('modal-header', $xpath->query('./*', $dialogs->item(1))->item(0)->getAttribute('data-slot'));
    }

    /**
     * Inside the template of a component that has a name prop of its own, Laravel's
     * @aware hands the header that outer name. The dialog reads the title id from the
     * header, so aria-labelledby still points at the title.
     */
    public function test_modal_and_drawer_label_themselves_with_the_header_title_they_find(): void
    {
        $directory = sys_get_temp_dir().'/acun-ui-parts-modal-drawer-test';
        @mkdir($directory, 0777, true);
        file_put_contents($directory.'/siparis-satiri.blade.php', <<<'BLADE'
            @props(['name'])
            <acun:modal name="siparis-duzenle">
                <acun:modal.header title="Siparişi düzenle" description="Ürünleri sıralayın." />
                <acun:modal.body>{{ $name }}</acun:modal.body>
            </acun:modal>
            <acun:drawer name="siparis-gecmisi">
                <acun:drawer.header title="Sipariş geçmişi" />
                <acun:drawer.body>{{ $name }}</acun:drawer.body>
            </acun:drawer>
        BLADE);
        Blade::anonymousComponentPath($directory);

        try {
            $html = Blade::render('<x-siparis-satiri name="siparis-1042" />', deleteCachedView: true);
        } finally {
            @unlink($directory.'/siparis-satiri.blade.php');
            @rmdir($directory);
        }

        $xpath = $this->xpath($html);
        $dialogs = $xpath->query('//*[@role="dialog"]');

        foreach (['modal', 'drawer'] as $index => $component) {
            $heading = $xpath->query('//*[@data-slot="'.$component.'-header"]//h3')->item(0);

            self::assertNotSame('', $heading->getAttribute('id'), $component);
            self::assertSame($heading->getAttribute('id'), $dialogs->item($index)->getAttribute('aria-labelledby'), $component);
            self::assertFalse($dialogs->item($index)->hasAttribute('aria-label'), $component);
        }

        $description = $xpath->query('//*[@data-slot="modal-header"]//p')->item(0);
        self::assertSame($description->getAttribute('id'), $dialogs->item(0)->getAttribute('aria-describedby'));
    }

    public function test_modal_trigger_opens_the_named_modal_from_anywhere(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:modal.trigger name="musteri-ekle" id="ac" class="sm:hidden"><acun:button>Yeni müşteri</acun:button></acun:modal.trigger>
            <acun:modal name="musteri-ekle" title="Yeni müşteri">Gövde</acun:modal>
        BLADE);

        $xpath = $this->xpath($html);
        $trigger = $xpath->query('//*[@data-slot="modal-trigger"]')->item(0);

        self::assertSame('ac', $trigger->getAttribute('id'));
        self::assertSame('contents sm:hidden', $trigger->getAttribute('class'));
        self::assertTrue($trigger->hasAttribute('x-data'));
        self::assertSame("\$dispatch('acun:modal:open', { name: 'musteri-ekle' })", $trigger->getAttribute('x-on:click'));
        // The modal it opens listens to acun:modal:open (and open-modal) under its name (acunModal, tests/js).
        self::assertStringContainsString('data-acun-modal="musteri-ekle"', $html);

        // The button keeps its role and says it opens a dialog.
        $button = $xpath->query('./button', $trigger)->item(0);
        self::assertSame('dialog', $button->getAttribute('aria-haspopup'));
        self::assertSame('button', $button->getAttribute('type'));
    }

    public function test_modal_trigger_only_marks_a_button_or_link_once(): void
    {
        $own = Blade::render('<acun:modal.trigger name="m"><button aria-haspopup="true">Aç</button></acun:modal.trigger>');
        self::assertSame(1, substr_count($own, 'aria-haspopup'));
        self::assertStringContainsString('aria-haspopup="true"', $own);

        $text = Blade::render('<acun:modal.trigger name="m">Aç</acun:modal.trigger>');
        self::assertStringNotContainsString('aria-haspopup', $text);

        $span = Blade::render('<acun:modal.trigger name="m"><span>Aç</span></acun:modal.trigger>');
        self::assertStringNotContainsString('aria-haspopup', $span);

        // The wrapper is phrasing content: a trigger inside a sentence does not break the paragraph.
        $sentence = $this->xpath(Blade::render('<p>Ayrıntı için <acun:modal.trigger name="m"><a href="#">tıklayın</a></acun:modal.trigger>.</p>'));
        self::assertNotNull($sentence->query('//p/span[@data-slot="modal-trigger"]/a')->item(0));

        // Livewire's block markers before the button are skipped; a ">" in an Alpine value is no tag end.
        $link = Blade::render("<acun:modal.trigger name=\"m\">\n<!--[if BLOCK]><![endif]--><a href=\"#\" x-on:click=\"n > 0 && go()\">Aç</a></acun:modal.trigger>");
        self::assertStringContainsString('<a aria-haspopup="dialog" href="#" x-on:click="n > 0 && go()">Aç</a>', $link);
    }

    public function test_modal_trigger_opens_a_confirmation_dialog(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:modal.trigger name="siparis-sil"><acun:button variant="danger">Siparişi sil</acun:button></acun:modal.trigger>
            <acun:confirmation-dialog name="siparis-sil" title="Sipariş silinsin mi?">
                <acun:modal.close><acun:button variant="danger" wire:click="sil">Sil</acun:button></acun:modal.close>
            </acun:confirmation-dialog>
        BLADE);

        // They share the acun:modal:open event.
        self::assertStringContainsString("x-on:click=\"\$dispatch('acun:modal:open', { name: 'siparis-sil' })\"", $html);
        // The modal it opens listens to acun:modal:open (and open-modal) under its name (acunModal, tests/js).
        self::assertStringContainsString('data-acun-modal="siparis-sil"', $html);
        // modal.close works in its confirm slot too: the confirm button also closes it.
        self::assertMatchesRegularExpression('#<span data-slot="modal-close" x-on:click="'.preg_quote(self::CLOSE['modal'], '#').'" class="contents"><button[^>]*wire:click="sil"#', $html);
    }

    public function test_modal_close_works_in_the_classic_footer_slot(): void
    {
        $html = Blade::render('<acun:modal name="siparis" title="Sipariş">Gövde<acun:slot:footer><acun:modal.close><button>Vazgeç</button></acun:modal.close></acun:slot:footer></acun:modal>');

        self::assertStringContainsString('<span data-slot="modal-close" x-on:click="'.self::CLOSE['modal'].'" class="contents"><button>Vazgeç</button></span>', $html);
        self::assertStringContainsString('<div class="flex-1 overflow-y-auto p-5">', $html);
    }

    /**
     * A nested Livewire component that re-renders on its own is drawn without the modal
     * around it: header and close need no name then. close() and requestClose() come from
     * the modal's Alpine scope; the browser gives the header the modal's ids.
     */
    public function test_modal_parts_without_the_modal_around_them_render_for_the_browser(): void
    {
        // The nested component's own render, as Livewire sends it on its updates.
        $html = Livewire::test(NestedModalForm::class)->call('$refresh')->html();
        $xpath = $this->xpath($html);

        $title = $xpath->query('//*[@data-slot="modal-header"]//h3')->item(0);
        self::assertFalse($title->hasAttribute('id'), 'No name, no id of its own.');
        self::assertSame("\$el.id || (\$el.closest('[data-acun-modal]') ? \$el.closest('[data-acun-modal]').dataset.acunModal + '-title' : null)", $title->getAttribute('x-bind:id'));
        $description = $xpath->query('//*[@data-slot="modal-header"]//p')->item(0);
        self::assertSame("\$el.id || (\$el.closest('[data-acun-modal]') ? \$el.closest('[data-acun-modal]').dataset.acunModal + '-description' : null)", $description->getAttribute('x-bind:id'));
        // Its X calls the modal's requestClose(); outside a modal it says so (no ReferenceError).
        self::assertSame(
            "\$el.closest('[data-acun-modal]') ? requestClose() : console.error('acun:modal.header is not inside an acun:modal.')",
            $xpath->query('//*[@data-slot="modal-header"]//button')->item(0)->getAttribute('x-on:click'),
        );
        self::assertStringContainsString('<span data-slot="modal-close" x-on:click="'.self::CLOSE['modal'].'" class="contents">', $html);

        // With the modal around them they keep their plain ids (the modal reads them).
        self::assertStringNotContainsString('x-bind:id', Blade::render('<acun:modal name="m"><acun:modal.header title="T" description="D" /></acun:modal>'));

        // body and footer only style: they need no modal.
        self::assertStringContainsString('data-slot="modal-body"', Blade::render('<acun:modal.body>x</acun:modal.body>'));
        self::assertStringContainsString('data-slot="modal-footer"', Blade::render('<acun:modal.footer>x</acun:modal.footer>'));
    }

    /** The modal in the page's component, its parts in a nested one that re-renders on its own. */
    public function test_modal_parts_in_a_nested_livewire_component_survive_its_own_updates(): void
    {
        Livewire::component('musteri-formu', NestedModalForm::class);

        // First render: the page draws the modal and the nested component together.
        $page = Blade::render('<acun:modal name="musteri-ekle"><livewire:musteri-formu /></acun:modal>');
        $xpath = $this->xpath($page);

        // The header knows the modal's name here (@aware): plain ids, as the browser gives them later.
        self::assertNotNull($xpath->query('//h3[@id="musteri-ekle-title"]')->item(0));
        self::assertNotNull($xpath->query('//p[@id="musteri-ekle-description"]')->item(0));
        self::assertStringNotContainsString('x-bind:id', $page);

        // Its own update: drawn alone, without the modal. It used to throw a ViewException.
        Livewire::test(NestedModalForm::class)
            ->call('$refresh')
            ->assertSeeHtml('data-slot="modal-header"')
            ->assertSeeHtml("x-bind:id=\"\$el.id || (\$el.closest('[data-acun-modal]') ? \$el.closest('[data-acun-modal]').dataset.acunModal + '-title' : null)\"")
            ->assertSeeHtml('<span data-slot="modal-close" x-on:click="'.self::CLOSE['modal'].'" class="contents">');
    }

    /**
     * The modal's slot is a nested Livewire component written in parts (real Livewire output:
     * tests/Fixtures/livewire). The header names the dialog, and the panel markup is the same
     * on the page's own updates, where Livewire leaves an empty stub for the component:
     * otherwise the morph would move or drop the component's DOM.
     */
    public function test_a_nested_livewire_component_written_in_parts_fills_the_modal(): void
    {
        Livewire::addLocation(viewPath: __DIR__.'/../Fixtures/livewire');
        $page = Livewire::test('musteri-sayfasi');

        $first = $this->xpath($page->html());
        $panel = $first->query('//*[@role="dialog"]')->item(0);
        // It used to be named "Pencere", with the parts inside the padded body.
        self::assertSame('musteri-ekle-title', $panel->getAttribute('aria-labelledby'));
        self::assertSame('musteri-ekle-description', $panel->getAttribute('aria-describedby'));
        self::assertFalse($panel->hasAttribute('aria-label'));
        self::assertSame('Yeni müşteri', trim($first->query('//h3[@id="musteri-ekle-title"]')->item(0)->textContent));
        self::assertSame(1, $first->query('//h3')->length, 'No title row of the modal beside the header.');

        // The body around the component gives way to its parts in the browser, when the
        // component starts with one (CSS :has; the stub on later updates holds nothing).
        $body = $first->query('./div[./*[@*[name()="wire:name"]="musteri-formu"]]', $panel)->item(0);
        self::assertSame(self::COMPONENT_BODY['modal'], $body->getAttribute('class'));

        // The page's own update: the same panel, with a stub in the same place.
        $update = $this->xpath($page->call('$refresh')->html());
        $panel = $update->query('//*[@role="dialog"]')->item(0);
        $body = $update->query('./div[./*[@*[name()="wire:name"]="musteri-formu"]]', $panel)->item(0);
        self::assertSame(self::COMPONENT_BODY['modal'], $body->getAttribute('class'));
        self::assertSame('', trim($body->textContent), 'Livewire leaves a stub for the nested component.');
        // The server cannot see the header now: it prints the fallback name, and the browser
        // keeps the header's (acunModal; modal-parts.test.mjs).
        self::assertSame('Pencere', $panel->getAttribute('aria-label'));
        self::assertSame(0, $update->query('//h3')->length, 'Still no title row.');
    }

    /** After a header part, a nested component holding the body and footer lays them out in the panel. */
    public function test_a_nested_component_after_the_header_part_lays_out_its_parts_in_the_panel(): void
    {
        Livewire::component('not-formu', NestedModalBody::class);

        $html = Blade::render('<acun:modal name="siparis-notu"><acun:modal.header title="Sipariş notu" /><livewire:not-formu /></acun:modal>');
        $xpath = $this->xpath($html);
        $panel = $xpath->query('//*[@role="dialog"]')->item(0);

        self::assertSame('siparis-notu-title', $panel->getAttribute('aria-labelledby'));
        self::assertNotNull($xpath->query('./*[@*[name()="wire:name"]="not-formu"]/*[@data-slot="modal-body"]', $panel)->item(0));
        // The component's root is layout-neutral when it starts with a part, so body and footer are the panel's rows.
        self::assertStringContainsString(self::COMPONENT_ROOT['modal'], $panel->getAttribute('class'));
        // A classic modal's panel does not need it.
        self::assertStringNotContainsString('wire', $this->xpath(Blade::render('<acun:modal name="m" title="T">Gövde</acun:modal>'))->query('//*[@role="dialog"]')->item(0)->getAttribute('class'));
    }

    /**
     * Rendered where no modal or drawer can be around it (plain Blade, no Livewire component
     * that the browser puts into one), a header fails at once instead of a nameless dialog
     * and an X that throws a ReferenceError.
     */
    public function test_a_header_outside_its_dialog_fails_loudly(): void
    {
        foreach (['modal' => '<acun:modal.header title="Yeni müşteri" />', 'drawer' => '<acun:drawer.header title="Sipariş #4821" />'] as $component => $template) {
            try {
                Blade::render($template, deleteCachedView: true);
                self::fail("acun:{$component}.header rendered outside an acun:{$component}.");
            } catch (\Illuminate\View\ViewException $exception) {
                self::assertStringContainsString("acun:{$component}.header must be inside an acun:{$component}", $exception->getMessage());
            }
        }
    }

    // --------------------------------------------------------------- drawer

    public function test_drawer_written_in_parts_fills_the_panel_with_its_parts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:drawer name="siparis-detay" title="Yok sayılır" body-class="p-0" aria-label="Yok sayılır">
                <acun:drawer.header title="Sipariş #4821" description="Ayşe Yılmaz, 3 ürün" class="bg-white" />
                <acun:drawer.body class="space-y-4">Müşteri bilgileri</acun:drawer.body>
                <acun:drawer.footer>
                    <acun:drawer.close><acun:button variant="white">Kapat</acun:button></acun:drawer.close>
                </acun:drawer.footer>
            </acun:drawer>
        BLADE);

        $xpath = $this->xpath($html);
        $dialog = $xpath->query('//*[@role="dialog"]')->item(0);
        $panel = $xpath->query('//*[@x-ref="panel"]')->item(0);

        self::assertSame('siparis-detay', $dialog->getAttribute('id'));
        self::assertSame('siparis-detay-title', $dialog->getAttribute('aria-labelledby'));
        self::assertSame('siparis-detay-description', $dialog->getAttribute('aria-describedby'));
        self::assertFalse($dialog->hasAttribute('aria-label'));
        self::assertSame(
            ['drawer-header', 'drawer-body', 'drawer-footer'],
            array_map(fn (\DOMElement $element) => $element->getAttribute('data-slot'), iterator_to_array($xpath->query('./*', $panel))),
        );
        // No classic title row and no bodyClass wrapper.
        self::assertSame(1, substr_count($html, '<h3'));
        self::assertStringNotContainsString('Yok sayılır</h3>', $html);
        self::assertStringNotContainsString('class="p-0"', $html);

        $header = $xpath->query('//*[@data-slot="drawer-header"]')->item(0);
        self::assertStringContainsString('bg-white', $header->getAttribute('class'));
        self::assertStringContainsString('items-start', $header->getAttribute('class'));
        self::assertSame('Sipariş #4821', trim($xpath->query('.//h3[@id="siparis-detay-title"]', $header)->item(0)->textContent));
        $x = $xpath->query('.//button', $header)->item(0);
        self::assertSame('close()', $x->getAttribute('x-on:click'));
        self::assertSame('Kapat', $x->getAttribute('aria-label'));

        self::assertSame('flex-1 overflow-y-auto p-5 space-y-4', $xpath->query('//*[@data-slot="drawer-body"]')->item(0)->getAttribute('class'));
        self::assertSame('flex items-center justify-end gap-2 px-5 py-4 bg-gray-50 border-t border-gray-200 dark:bg-zinc-800/50 dark:border-zinc-700', $xpath->query('//*[@data-slot="drawer-footer"]')->item(0)->getAttribute('class'));
        self::assertSame(self::CLOSE['drawer'], $xpath->query('//*[@data-slot="drawer-close"]')->item(0)->getAttribute('x-on:click'));
    }

    public function test_drawer_footer_follows_compact_and_docked(): void
    {
        $compact = $this->xpath(Blade::render('<acun:drawer name="d" compact><acun:drawer.body>x</acun:drawer.body><acun:drawer.footer>F</acun:drawer.footer></acun:drawer>'));
        self::assertSame('flex items-center gap-1.5 px-3 py-2.5 bg-gray-50 border-t border-gray-200 dark:bg-zinc-800/50 dark:border-zinc-700', $compact->query('//*[@data-slot="drawer-footer"]')->item(0)->getAttribute('class'));

        $docked = $this->xpath(Blade::render('<acun:drawer name="filtre" docked><acun:drawer.body>x</acun:drawer.body><acun:drawer.footer>F</acun:drawer.footer></acun:drawer>'));
        self::assertSame('flex items-center justify-end gap-2 px-5 py-4 bg-gray-50 dark:bg-zinc-800/50', $docked->query('//*[@data-slot="drawer-footer"]')->item(0)->getAttribute('class'));
    }

    public function test_docked_drawer_takes_parts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:drawer name="siparis-filtreleri" docked>
                <acun:drawer.header title="Sipariş filtreleri" />
                <acun:drawer.body>Durum</acun:drawer.body>
                <acun:drawer.footer><button>Uygula</button></acun:drawer.footer>
            </acun:drawer>
        BLADE);

        $xpath = $this->xpath($html);
        $dialog = $xpath->query('//*[@role="dialog"]')->item(0);
        $inner = $xpath->query('./div', $dialog)->item(0);

        self::assertStringContainsString('filter-panel', $dialog->getAttribute('class'));
        self::assertSame('siparis-filtreleri-title', $dialog->getAttribute('aria-labelledby'));
        self::assertFalse($dialog->hasAttribute('aria-label'));
        self::assertSame(
            ['drawer-header', 'drawer-body', 'drawer-footer'],
            array_map(fn (\DOMElement $element) => $element->getAttribute('data-slot'), iterator_to_array($xpath->query('./*', $inner))),
        );
        self::assertStringNotContainsString('<div class="flex-1 overflow-y-auto p-5">', $html);
    }

    public function test_drawer_in_parts_without_a_header_keeps_the_title_row_and_header_slot(): void
    {
        $title = $this->xpath(Blade::render('<acun:drawer name="d" title="Teslimat"><acun:drawer.body>x</acun:drawer.body></acun:drawer>'));
        self::assertSame('Teslimat', trim($title->query('//h3')->item(0)->textContent));
        self::assertSame('Teslimat', $title->query('//*[@role="dialog"]')->item(0)->getAttribute('aria-label'));

        $slot = Blade::render('<acun:drawer name="d"><acun:slot:header>Sekmeler</acun:slot:header><acun:drawer.body>x</acun:drawer.body></acun:drawer>');
        self::assertStringContainsString('Sekmeler', $slot);
        self::assertStringContainsString('aria-label="Menü"', $slot);

        // With a header part, the header slot gives way to it.
        $both = Blade::render('<acun:drawer name="d"><acun:slot:header>Sekmeler</acun:slot:header><acun:drawer.header title="Teslimat" /><acun:drawer.body>x</acun:drawer.body></acun:drawer>');
        self::assertStringNotContainsString('Sekmeler', $both);
    }

    public function test_classic_drawer_is_unchanged(): void
    {
        $html = Blade::render('<acun:drawer name="yan" title="Yan panel" body-class="p-2"><acun:drawer.close><button>Geri</button></acun:drawer.close><p>İçerik</p></acun:drawer>');

        self::assertStringContainsString('aria-label="Yan panel"', $html);
        self::assertStringContainsString('<h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100">Yan panel</h3>', $html);
        // A body that starts with drawer.close is still a classic body.
        self::assertMatchesRegularExpression('#<div class="p-2">\s*<span data-slot="drawer-close"#', $html);
        self::assertStringNotContainsString('aria-labelledby', $html);
    }

    public function test_nested_drawer_and_modal_keep_their_own_mode(): void
    {
        $html = Blade::render(<<<'BLADE'
            <acun:drawer name="musteri-detay">
                <acun:drawer.header title="Ayşe Yılmaz" />
                <acun:drawer.body>
                    <acun:modal name="adres-ekle" title="Adres ekle"><p>Adres</p></acun:modal>
                </acun:drawer.body>
            </acun:drawer>
        BLADE);

        $xpath = $this->xpath($html);
        $dialogs = $xpath->query('//*[@role="dialog"]');

        self::assertSame('musteri-detay-title', $dialogs->item(0)->getAttribute('aria-labelledby'));
        self::assertSame('adres-ekle-title', $dialogs->item(1)->getAttribute('aria-labelledby'));
        self::assertNotNull($xpath->query('./div[@class="flex-1 overflow-y-auto p-5"]', $dialogs->item(1))->item(0), 'The nested modal stays classic.');
    }

    public function test_drawer_trigger_opens_or_toggles_and_reports_its_state(): void
    {
        $html = Blade::render('<acun:drawer.trigger name="siparis-detay" class="lg:hidden"><acun:button variant="white">Detay</acun:button></acun:drawer.trigger>');

        $xpath = $this->xpath($html);
        $trigger = $xpath->query('//*[@data-slot="drawer-trigger"]')->item(0);

        self::assertSame('contents lg:hidden', $trigger->getAttribute('class'));
        self::assertSame("\$dispatch('acun:drawer:open', { name: 'siparis-detay' })", $trigger->getAttribute('x-on:click'));
        self::assertSame("if (\$event.detail.name === 'siparis-detay') drawerOpen = true", $trigger->getAttribute('x-on:acun:drawer:opened.window'));
        self::assertSame("if (\$event.detail.name === 'siparis-detay') drawerOpen = false", $trigger->getAttribute('x-on:acun:drawer:closed.window'));

        $button = $xpath->query('./button', $trigger)->item(0);
        self::assertSame('dialog', $button->getAttribute('aria-haspopup'));
        self::assertSame('false', $button->getAttribute('aria-expanded'));
        self::assertSame('drawerOpen.toString()', $button->getAttribute('x-bind:aria-expanded'));

        $toggle = Blade::render('<acun:drawer.trigger name="siparis-filtreleri" toggle><button :aria-expanded="acik">Filtreler</button></acun:drawer.trigger>');
        self::assertStringContainsString("x-on:click=\"\$dispatch('acun:drawer:toggle', { name: 'siparis-filtreleri' })\"", $toggle);
        self::assertStringNotContainsString('drawerOpen.toString()', $toggle, 'An aria-expanded of its own is kept.');
    }

    /**
     * A nested Livewire component that re-renders on its own is drawn without the drawer
     * around it: header and close need no name then. close() comes from the drawer's
     * Alpine scope; the browser gives the header the drawer's ids.
     */
    public function test_drawer_parts_without_the_drawer_around_them_render_for_the_browser(): void
    {
        // The nested component's own render, as Livewire sends it on its updates.
        $html = Livewire::test(NestedDrawerDetail::class)->call('$refresh')->html();
        $xpath = $this->xpath($html);

        $title = $xpath->query('//*[@data-slot="drawer-header"]//h3')->item(0);
        self::assertFalse($title->hasAttribute('id'), 'No name, no id of its own.');
        self::assertSame("\$el.id || (\$el.closest('[data-acun-drawer]') ? \$el.closest('[data-acun-drawer]').dataset.acunDrawer + '-title' : null)", $title->getAttribute('x-bind:id'));
        $description = $xpath->query('//*[@data-slot="drawer-header"]//p')->item(0);
        self::assertSame("\$el.id || (\$el.closest('[data-acun-drawer]') ? \$el.closest('[data-acun-drawer]').dataset.acunDrawer + '-description' : null)", $description->getAttribute('x-bind:id'));
        // Its X calls the drawer's close(); outside a drawer it says so (no ReferenceError).
        self::assertSame(
            "\$el.closest('[data-acun-drawer]') ? close() : console.error('acun:drawer.header is not inside an acun:drawer.')",
            $xpath->query('//*[@data-slot="drawer-header"]//button')->item(0)->getAttribute('x-on:click'),
        );
        self::assertStringContainsString('<span data-slot="drawer-close" x-on:click="'.self::CLOSE['drawer'].'" class="contents">', $html);

        // With the drawer around them they keep their plain ids (the drawer reads them), docked too.
        self::assertStringNotContainsString('x-bind:id', Blade::render('<acun:drawer name="d"><acun:drawer.header title="T" description="D" /></acun:drawer>'));
        self::assertStringNotContainsString('x-bind:id', Blade::render('<acun:drawer name="f" docked><acun:drawer.header title="T" description="D" /></acun:drawer>'));

        // body and footer only style: they need no drawer.
        self::assertStringContainsString('data-slot="drawer-body"', Blade::render('<acun:drawer.body>x</acun:drawer.body>'));
        self::assertStringContainsString('data-slot="drawer-footer"', Blade::render('<acun:drawer.footer>x</acun:drawer.footer>'));
    }

    /** The drawer in the page's component, its parts in a nested one that re-renders on its own. */
    public function test_drawer_parts_in_a_nested_livewire_component_survive_its_own_updates(): void
    {
        Livewire::component('siparis-detayi', NestedDrawerDetail::class);

        // First render: the page draws the drawer and the nested component together.
        $page = Blade::render('<acun:drawer name="siparis-detay"><livewire:siparis-detayi /></acun:drawer>');
        $xpath = $this->xpath($page);

        // The header knows the drawer's name here (@aware): plain ids, as the browser gives them later.
        self::assertNotNull($xpath->query('//h3[@id="siparis-detay-title"]')->item(0));
        self::assertNotNull($xpath->query('//p[@id="siparis-detay-description"]')->item(0));
        self::assertStringNotContainsString('x-bind:id', $page);

        // Its own update: drawn alone, without the drawer. It used to throw a ViewException.
        Livewire::test(NestedDrawerDetail::class)
            ->call('$refresh')
            ->assertSeeHtml('data-slot="drawer-header"')
            ->assertSeeHtml("x-bind:id=\"\$el.id || (\$el.closest('[data-acun-drawer]') ? \$el.closest('[data-acun-drawer]').dataset.acunDrawer + '-title' : null)\"")
            ->assertSeeHtml('<span data-slot="drawer-close" x-on:click="'.self::CLOSE['drawer'].'" class="contents">');
    }

    /** As in the modal: a nested component written in parts names the drawer and keeps one panel markup. */
    public function test_a_nested_livewire_component_written_in_parts_fills_the_drawer(): void
    {
        Livewire::addLocation(viewPath: __DIR__.'/../Fixtures/livewire');
        $page = Livewire::test('siparis-sayfasi');

        $first = $this->xpath($page->html());
        $drawer = $first->query('//*[@role="dialog"]')->item(0);
        // It used to be named "Menü", with the parts inside the padded body.
        self::assertSame('siparis-detay-title', $drawer->getAttribute('aria-labelledby'));
        self::assertSame('siparis-detay-description', $drawer->getAttribute('aria-describedby'));
        self::assertFalse($drawer->hasAttribute('aria-label'));

        $body = $first->query('//div[./*[@*[name()="wire:name"]="siparis-detayi"]]')->item(0);
        self::assertSame(self::COMPONENT_BODY['drawer'], $body->getAttribute('class'));
        self::assertSame('panel', $body->parentNode->getAttribute('x-ref'));

        // The page's own update: the same panel, with a stub in the same place.
        $update = $this->xpath($page->call('$refresh')->html());
        $body = $update->query('//div[./*[@*[name()="wire:name"]="siparis-detayi"]]')->item(0);
        self::assertSame(self::COMPONENT_BODY['drawer'], $body->getAttribute('class'));
        self::assertSame('Menü', $update->query('//*[@role="dialog"]')->item(0)->getAttribute('aria-label'));

        // The docked panel and a bodyClass of its own take the component the same way.
        Livewire::component('siparis-detayi-ic', NestedDrawerDetail::class);
        $docked = $this->xpath(Blade::render('<acun:drawer name="filtreler" docked><livewire:siparis-detayi-ic /></acun:drawer>'));
        self::assertSame(str_replace('flex-1 p-5 overflow-y-auto', 'flex-1 overflow-y-auto p-5', self::COMPONENT_BODY['drawer']), $docked->query('//div[./*[@*[name()="wire:name"]="siparis-detayi-ic"]]')->item(0)->getAttribute('class'));
        self::assertSame('filtreler-title', $docked->query('//*[@role="dialog"]')->item(0)->getAttribute('aria-labelledby'));
        $own = $this->xpath(Blade::render('<acun:drawer name="d" body-class="flex-1 overflow-y-auto"><livewire:siparis-detayi-ic /></acun:drawer>'));
        self::assertStringStartsWith('flex-1 overflow-y-auto [&:has(', $own->query('//div[./*[@*[name()="wire:name"]="siparis-detayi-ic"]]')->item(0)->getAttribute('class'));
    }

    private function xpath(string $html): \DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($document);
    }
}

/** A form in a modal of the page's component: its parts are inside this nested component. */
final class NestedModalForm extends Component
{
    public string $musteri = '';

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <acun:modal.header title="Yeni müşteri" description="Grup ve temsilci" />
                <acun:modal.body>{{ $musteri }}</acun:modal.body>
                <acun:modal.footer>
                    <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
                </acun:modal.footer>
            </div>
            BLADE;
    }
}

/** An order's details in a drawer of the page's component: its parts are inside this nested component. */
final class NestedDrawerDetail extends Component
{
    public string $siparis = '';

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <acun:drawer.header title="Sipariş #4821" description="Ayşe Yılmaz, 3 ürün" />
                <acun:drawer.body>{{ $siparis }}</acun:drawer.body>
                <acun:drawer.footer>
                    <acun:drawer.close><acun:button variant="white">Kapat</acun:button></acun:drawer.close>
                </acun:drawer.footer>
            </div>
            BLADE;
    }
}

/** An order note form after the modal's header part: it starts with the body and holds the footer. */
final class NestedModalBody extends Component
{
    public string $not = '';

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <acun:modal.body><acun:textarea wire:model="not" label="Not" /></acun:modal.body>
                <acun:modal.footer>
                    <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
                    <acun:button wire:click="kaydet">Kaydet</acun:button>
                </acun:modal.footer>
            </div>
            BLADE;
    }
}
