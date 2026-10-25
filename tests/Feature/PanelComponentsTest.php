<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Icon;
use Acun\Ui\Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Js;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;
use PHPUnit\Framework\Attributes\DataProvider;

final class PanelComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function components(): array
    {
        return [
            'alert' => ['<x-ui.alert variant="success" title="Tamam" dismissible>Kaydedildi</x-ui.alert>', 'Kaydedildi'],
            'badge' => ['<x-ui.badge variant="danger">Pasif</x-ui.badge>', 'bg-danger-tint'],
            'button-group' => ['<x-ui.button-group><x-ui.button>A</x-ui.button></x-ui.button-group>', 'role="group"'],
            'card' => ['<x-ui.card title="Özet">Gövde</x-ui.card>', 'Özet'],
            'checkbox' => ['<x-ui.checkbox wire:model.live="secili" label="Seç" />', 'id="secili"'],
            'confirmation-dialog' => ['<x-ui.confirmation-dialog name="sil" title="Silinsin mi?" />', 'sil-confirmed'],
            'drawer' => ['<x-ui.drawer name="yan" title="Yan Panel">İçerik</x-ui.drawer>', 'id="yan"'],
            'drawer docked' => ['<x-ui.drawer name="filtre" docked title="Filtreler">F</x-ui.drawer>', 'filter-panel'],
            'dropdown' => ["<x-ui.dropdown>\n<x-slot:trigger><button>Aç</button></x-slot:trigger>\n<x-ui.dropdown-item href=\"/x\">Git</x-ui.dropdown-item>\n</x-ui.dropdown>", 'href="/x"'],
            'input' => ['<x-ui.input wire:model.live.debounce.400ms="arama" type="search" label="Ara" />', 'id="arama"'],
            'input floating' => ['<x-ui.input name="email" variant="floating" label="E-posta" viewable />', 'peer-placeholder-shown'],
            'modal' => ['<x-ui.modal name="form" title="Başlık" max-width="2xl">İçerik</x-ui.modal>', 'sm:max-w-2xl'],
            'progress' => ['<x-ui.progress :value="140" striped />', 'width: 100%'],
            'radio' => ['<x-ui.radio name="tur" value="a" label="A" />', 'id="tur-a"'],
            'select' => ['<x-ui.select wire:model.live="durum" :options="[\'1\' => \'Aktif\']" placeholder="Seçiniz" />', 'Aktif'],
            'sortable-th' => ['<x-ui.sortable-th field="ad" sort-field="ad" sort-direction="desc">Ad</x-ui.sortable-th>', "sortBy('ad')"],
            'table' => ['<x-ui.table><table class="ui-table"></table></x-ui.table>', 'class="ui-table-box relative overflow-x-auto'],
            'tabs' => ['<x-ui.tabs :tabs="[\'genel\' => \'Genel\']"><x-ui.tab-panel value="genel">G</x-ui.tab-panel></x-ui.tabs>', "selected('genel')"],
            'textarea' => ['<x-ui.textarea name="not" label="Not" />', 'id="not"'],
            'toast-container' => ['<x-ui.toast-container />', 'x-on:toast.window'],
            'tooltip' => ['<x-ui.tooltip text="İpucu">?</x-ui.tooltip>', 'İpucu'],
            'theme-toggle' => ['<x-ui.theme-toggle />', '$store.appearance.toggle()'],
            'auth-header' => ['<x-ui.auth-header title="Giriş" description="Hesabınıza girin" />', 'Hesabınıza girin'],
            'auth-session-status' => ['<x-ui.auth-session-status status="Bağlantı gönderildi" />', 'Bağlantı gönderildi'],
            'list-toolbar' => ["<x-ui.list-toolbar>\n<x-slot:actions>Yeni</x-slot:actions>\nAra\n</x-ui.list-toolbar>", '<div class="flex flex-wrap items-center gap-2">'],
            'selection-bar' => ['<x-ui.selection-bar :count="3"><x-ui.button>Sil</x-ui.button></x-ui.selection-bar>', '3 kayıt seçildi.'],
            'accordion' => ["<x-ui.accordion open=\"a\">\n<x-ui.accordion-item name=\"a\" title=\"Başlık\">İçerik</x-ui.accordion-item>\n</x-ui.accordion>", ':aria-controls="$id(\'accordion\', \'a\')"'],
            'collapse' => ['<x-ui.collapse label="Ayrıntı">Gizli</x-ui.collapse>', 'x-collapse'],
            'carousel' => ['<x-ui.carousel :interval="3000"><div>1</div><div>2</div></x-ui.carousel>', 'aria-roledescription="carousel"'],
            'list-group' => ['<x-ui.list-group><x-ui.list-group-item href="/a" active>A</x-ui.list-group-item><x-ui.list-group-item disabled>B</x-ui.list-group-item></x-ui.list-group>', 'aria-disabled="true"'],
            'spinner' => ['<x-ui.spinner type="grow" />', 'role="status"'],
            'popover' => ["<x-ui.popover title=\"Kural\">\n<x-slot:trigger><button>?</button></x-slot:trigger>\nAçıklama\n</x-ui.popover>", 'Kural'],
            'footer' => ["<x-ui.footer>\n<x-slot:links><a href=\"#\">Destek</a></x-slot:links>\n© 2026\n</x-ui.footer>", 'Destek'],
            'divider' => ['<x-ui.divider align="start" variant="dashed">Bölüm</x-ui.divider>', 'border-dashed'],
            'icon' => ['<x-ui.icon name="trash" label="Sil" />', 'aria-label="Sil"'],
            'icon solid' => ['<x-ui.icon name="trash" solid />', 'fill-rule="evenodd"'],
            'icon full set' => ['<x-ui.icon name="academic-cap" />', 'stroke-linejoin="round"'],
            'stat-card' => ['<x-ui.stat-card label="Sipariş" value="3.106" icon="shopping-bag" :change="-4" />', 'text-danger-ink'],
            'card extras' => ['<x-ui.card title="T" subtitle="Alt" collapsible closable>G</x-ui.card>', 'visible = false'],
            'input-group' => ['<x-ui.input-group name="tel" prefix="+90" />', '+90'],
            'tabs pills' => ["<x-ui.tabs variant=\"pills\" :tabs=\"['a' => 'A']\"><x-ui.tab-panel value=\"a\">x</x-ui.tab-panel></x-ui.tabs>", 'role="tab"'],
            'command-palette' => ["<x-ui.command-palette placeholder=\"Ara\" :items=\"[['label' => 'Siparişler', 'href' => '/siparisler', 'group' => 'Sayfalar']]\" />", 'command-palette-open.window'],
            'brand-color-picker' => ['<x-ui.brand-color-picker label="Ana renk" />', 'type="color"'],
            'theme-customizer' => ['<x-ui.theme-customizer />', 'theme-customizer'],
            'button label' => ['<x-ui.button variant="soft-danger">Sil</x-ui.button>', 'bg-danger-soft'],
            'empty-state' => ['<x-ui.empty-state title="Kayıt yok" description="Filtreyi değiştirin" />', 'Kayıt yok'],
            'skeleton' => ['<x-ui.skeleton variant="circle" class="size-8" />', 'rounded-full'],
            'avatar' => ['<x-ui.avatar name="Ayşe Yılmaz" status="online" />', 'AY'],
            'avatar-group' => ['<x-ui.avatar-group :more="3"><x-ui.avatar name="A B" /></x-ui.avatar-group>', '+3'],
            'switch' => ['<x-ui.switch name="bildirim" label="Bildirim" checked />', 'role="switch"'],
        ];
    }

    #[DataProvider('components')]
    public function test_component_renders(string $blade, string $expected): void
    {
        self::assertStringContainsString($expected, Blade::render($blade));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wireModelControls(): array
    {
        return [
            'checkbox' => ['<x-ui.checkbox wire:model="remember" label="Beni hatırla" />', 'remember'],
            'checkbox in array' => ['<x-ui.checkbox wire:model.live="selected" value="5" label="Seç" />', 'selected-5'],
            'radio' => ['<x-ui.radio wire:model="plan" value="team" label="Takım" />', 'plan-team'],
            'switch' => ['<x-ui.switch wire:model="notify" label="Bildirim" />', 'switch-notify'],
            'switch in array' => ['<x-ui.switch wire:model="channels" value="sms" label="SMS" />', 'switch-channels-sms'],
        ];
    }

    #[DataProvider('wireModelControls')]
    public function test_wire_model_controls_are_associated_with_their_label(string $blade, string $expectedId): void
    {
        $html = Blade::render($blade);

        self::assertStringContainsString('id="'.$expectedId.'"', $html);
        self::assertStringContainsString('for="'.$expectedId.'"', $html);
    }

    public function test_radio_takes_its_group_name_from_wire_model(): void
    {
        self::assertStringContainsString('name="plan"', Blade::render('<x-ui.radio wire:model.live="plan" value="team" label="Takım" />'));
    }

    public function test_icon_set_has_every_outline_icon_in_solid_and_rejects_unknown_names(): void
    {
        self::assertCount(324, Icon::all());
        self::assertSame([], array_diff(array_keys(Icon::all()), array_keys(Icon::all('solid'))));

        $this->expectException(ViewException::class);
        Blade::render('<x-ui.icon name="olmayan-ikon" />');
    }

    public function test_button_supports_panel_variants_links_and_form_modal_appearance(): void
    {
        self::assertStringContainsString('border-danger bg-danger text-on-danger', Blade::render('<x-ui.button variant="danger">Sil</x-ui.button>'));
        self::assertStringContainsString('<a href="/yeni"', Blade::render('<x-ui.button href="/yeni">Yeni</x-ui.button>'));
        self::assertStringContainsString('border-danger-line text-danger-ink', Blade::render('<x-ui.button variant="danger" appearance="outline">Sil</x-ui.button>'));
        self::assertStringContainsString('border-transparent bg-transparent text-dim hover:not-disabled:text-ink', Blade::render('<x-ui.button variant="white" appearance="text">Vazgeç</x-ui.button>'));
        self::assertStringContainsString('aria-busy="true"', Blade::render('<x-ui.button loading>Kaydet</x-ui.button>'));
    }

    public function test_breadcrumb_falls_back_to_site_root_without_dashboard_route(): void
    {
        $html = Blade::render('<x-ui.breadcrumb :items="[[\'label\' => \'Liste\', \'url\' => \'/liste\'], [\'label\' => \'Düzenle\']]" />');

        self::assertStringContainsString('href="'.url('/').'"', $html);
        self::assertStringContainsString('href="/liste"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
    }

    public function test_breadcrumb_uses_dashboard_route_when_defined(): void
    {
        Route::get('/panel', fn () => '')->name('dashboard');
        app('router')->getRoutes()->refreshNameLookups();

        self::assertStringContainsString('href="'.url('/panel').'"', Blade::render('<x-ui.breadcrumb />'));
    }

    public function test_sortable_th_uses_a_keyboard_reachable_button_and_announces_the_sort(): void
    {
        $html = Blade::render('<x-ui.sortable-th field="ad" sort-field="ad" sort-direction="desc">Ad</x-ui.sortable-th>');

        self::assertMatchesRegularExpression('/<button[^>]*type="button"[^>]*wire:click="sortBy\(\'ad\'\)"/', $html);
        self::assertStringContainsString('aria-sort="descending"', $html);

        $other = Blade::render('<x-ui.sortable-th field="tarih" sort-field="ad" sort-direction="desc">Tarih</x-ui.sortable-th>');
        self::assertStringNotContainsString('aria-sort', $other);
        self::assertStringNotContainsString('<th wire:click', $other);

        // The component's own sorting method.
        $own = Blade::render('<x-ui.sortable-th field="ad" sort-field="ad" sort-direction="asc" method="sirala">Ad</x-ui.sortable-th>');
        self::assertMatchesRegularExpression('/wire:click="sirala\(\'ad\'\)"/', $own);
    }

    public function test_overlays_share_the_theme_backdrop(): void
    {
        $overlays = [
            'modal' => '<x-ui.modal name="duzenle" title="Düzenle">İçerik</x-ui.modal>',
            'drawer' => '<x-ui.drawer name="filtreler" title="Filtreler">İçerik</x-ui.drawer>',
            'command-palette' => '<x-ui.command-palette :items="[]" />',
        ];

        foreach ($overlays as $name => $blade) {
            $html = Blade::render($blade);

            self::assertStringContainsString('class="ui-backdrop fixed inset-0', $html, $name);
            self::assertStringNotContainsString('bg-gray-900/60', $html, $name);
        }
    }

    /** layout.blank: the document and closing scripts only; attributes go on <body>. */
    public function test_blank_layout_is_a_document_around_the_page(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.layout.blank title="Boş sayfa" class="bg-surface" data-page="vitrin">
                <x-slot:head><link rel="icon" href="/favicon.svg"></x-slot:head>
                <p>Kendi içeriği</p>
            </x-ui.layout.blank>
        BLADE);

        self::assertStringStartsWith('<!DOCTYPE html>', trim($html));
        self::assertStringContainsString('<title>Boş sayfa</title>', $html);
        self::assertStringContainsString('<link rel="icon" href="/favicon.svg">', $html);
        self::assertMatchesRegularExpression('~<body class="min-h-dvh text-body bg-surface" data-page="vitrin">\s*<p>Kendi içeriği</p>~', $html);
        self::assertStringNotContainsString('panel-sidebar', $html);
        self::assertStringContainsString('x-on:toast.window="push($event.detail)"', $html);
    }

    public function test_selection_bar_floats_by_default_and_can_stay_in_the_flow(): void
    {
        $floating = Blade::render('<x-ui.selection-bar :count="2">Sil</x-ui.selection-bar>');
        self::assertStringContainsString('sticky bottom-4', $floating);

        $inline = Blade::render('<x-ui.selection-bar :count="2" :floating="false">Sil</x-ui.selection-bar>');
        self::assertStringNotContainsString('sticky bottom-4', $inline);
    }

    public function test_pagination_bar_stays_with_no_results_and_scrolls_to_the_list_on_page_change(): void
    {
        app()->setLocale('tr');
        $empty = new LengthAwarePaginator([], 0, 25, 1);
        $html = view('acun-ui::components.ui.pagination', ['paginator' => $empty, 'elements' => [[1 => '/?page=1']]])->render();

        self::assertStringContainsString('role="navigation"', $html);
        self::assertStringContainsString('Sonuç yok', $html);
        self::assertStringContainsString("closest('[wire\\\\:id]')", $html);
    }

    public function test_pagination_summary_is_one_translated_sentence_with_its_numbers(): void
    {
        app()->setLocale('tr');
        $page = new LengthAwarePaginator(range(1, 25), 1250, 25, 3);
        $html = view('acun-ui::components.ui.pagination', ['paginator' => $page])->render();

        self::assertMatchesRegularExpression('/<span class="font-medium tabular-nums">51<\/span>–<span class="font-medium tabular-nums">75<\/span> \/ <span class="font-medium tabular-nums">1\.250<\/span> sonuç/', $html);
        self::assertStringContainsString('wire:click.prevent="previousPage(&#039;page&#039;)"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
    }

    public function test_simple_and_cursor_paginators_get_the_bar_too(): void
    {
        app()->setLocale('tr');
        $simple = new \Illuminate\Pagination\Paginator(range(1, 11), 10, 2);
        $html = view('acun-ui::components.ui.pagination', ['paginator' => $simple])->render();

        self::assertStringContainsString('role="navigation"', $html);
        self::assertMatchesRegularExpression('/>11<\/span>–<span class="font-medium tabular-nums">20<\/span> arası gösteriliyor/', $html, 'No total: simplePaginate() does not count.');
        self::assertStringContainsString('wire:click.prevent="nextPage(&#039;page&#039;)"', $html);
        self::assertStringNotContainsString('aria-current="page"', $html, 'No page numbers.');

        $cursor = new \Illuminate\Pagination\CursorPaginator(collect([['id' => 1], ['id' => 2], ['id' => 3]]), 2, null, ['parameters' => ['id']]);
        $html = view('acun-ui::components.ui.pagination', ['paginator' => $cursor])->render();

        self::assertStringContainsString('role="navigation"', $html);
        self::assertMatchesRegularExpression('/wire:click\.prevent="setPage\(&#039;[A-Za-z0-9_-]+&#039;, &#039;cursor&#039;\)"/', $html);
        self::assertStringNotContainsString('gösteriliyor', $html, 'A cursor has no item numbers.');
    }
    /** No inline code: the head prints data and the boot script's tag; keep-in-view, stores and dialogs live in the runtime (tests/js). */
    public function test_head_prints_no_inline_script(): void
    {
        $html = Blade::render('<x-ui.layout.blank title="Panel">İçerik</x-ui.layout.blank>');

        preg_match_all('~<script\b([^>]*)>(.*?)</script>~s', $html, $scripts, PREG_SET_ORDER);
        foreach ($scripts as [, $attributes, $body]) {
            self::assertTrue(trim($body) === '' || str_contains($attributes, 'type="application/json"'), 'Inline code: '.$attributes);
        }

        self::assertStringNotContainsString('acunScrollLock', $html);
        self::assertStringNotContainsString('Livewire.hook', $html);
    }

    /** The badges slot sits beside the title; without it the header is a plain h1. */
    public function test_page_header_shows_badges_beside_the_title(): void
    {
        $html = Blade::render('<x-ui.page-header title="Sipariş SP-1"><x-slot:badges><x-ui.badge variant="success">Ödendi</x-ui.badge></x-slot:badges></x-ui.page-header>');
        self::assertMatchesRegularExpression('/<h1[^>]*>Sipariş SP-1<\/h1>\s*<div class="flex flex-wrap items-center gap-2">\s*<span[^>]*>\s*Ödendi/', $html);

        $plain = Blade::render('<x-ui.page-header title="Siparişler" />');
        self::assertStringNotContainsString('gap-x-3 gap-y-1', $plain);
    }

    /** A narrow screen scrolls the underline tabs sideways instead of overflowing the page. */
    public function test_underline_tabs_scroll_sideways_and_pills_wrap(): void
    {
        $underline = Blade::render('<x-ui.tabs :tabs="[\'a\' => \'Genel bakış\', \'b\' => \'Güvenlik\']">x</x-ui.tabs>');
        self::assertMatchesRegularExpression('/role="tablist"[^>]*class="[^"]*overflow-x-auto/', $underline);
        self::assertStringContainsString('shrink-0', $underline);
        self::assertStringContainsString("scrollIntoView({ block: 'nearest', inline: 'nearest' })", $underline);

        $pills = Blade::render('<x-ui.tabs variant="pills" :tabs="[\'a\' => \'Genel bakış\']">x</x-ui.tabs>');
        self::assertMatchesRegularExpression('/role="tablist"[^>]*class="[^"]*flex-wrap/', $pills);
        self::assertStringNotContainsString('overflow-x-auto', $pills);
    }

    /** An accordion item can be hidden (x-show on the item); class still styles the content. */
    public function test_accordion_items_take_their_attributes_and_draw_their_own_lines(): void
    {
        $html = Blade::render('<x-ui.accordion><x-ui.accordion-item name="a" title="Soru" x-show="visible" class="text-xs">Yanıt</x-ui.accordion-item></x-ui.accordion>');

        self::assertMatchesRegularExpression('/<div class="border-b[^"]*" x-show="visible">\s*<h3>/', $html);
        self::assertMatchesRegularExpression('/<div class="px-5 pb-5[^"]*text-xs[^"]*">\s*Yanıt/', $html);
        // The list sits 1px lower than the box, which clips the last visible line.
        self::assertStringContainsString('<div class="-mb-px">', $html);
        self::assertStringNotContainsString('divide-y', $html);
    }

    /** An item written in parts: a rich heading (icon, badge) wired to its content by the item's name. */
    public function test_accordion_item_can_be_written_with_heading_and_content_parts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.accordion open="iade">
                <x-ui.accordion-item name="iade" wire:key="iade">
                    <x-ui.accordion.heading class="py-3"><span data-icon></span> İade nasıl yapılır? <x-ui.badge>Yeni</x-ui.badge></x-ui.accordion.heading>
                    <x-ui.accordion.content class="text-xs">14 gün içinde iade talebi açın.</x-ui.accordion.content>
                </x-ui.accordion-item>
            </x-ui.accordion>
        BLADE);

        // Every item attribute goes on the item; each part takes its own.
        self::assertMatchesRegularExpression('/<div class="border-b[^"]*" wire:key="iade">\s*<h3>/', $html);
        self::assertMatchesRegularExpression('/<button\s+type="button"\s+:id="\$id\(\'accordion\', \'iade\'\) \+ \'-heading\'"\s+x-on:click="toggle\(\'iade\'\)"[^>]*:aria-controls="\$id\(\'accordion\', \'iade\'\)"[^>]*class="[^"]*py-3/', $html);
        self::assertMatchesRegularExpression('/<span data-icon><\/span> İade nasıl yapılır\? <span[^>]*>\s*Yeni/', $html);
        self::assertMatchesRegularExpression('/<div :id="\$id\(\'accordion\', \'iade\'\)" role="region" :aria-labelledby="\$id\(\'accordion\', \'iade\'\) \+ \'-heading\'" x-show="isOpen\(\'iade\'\)"/', $html);
        self::assertMatchesRegularExpression('/<div class="px-5 pb-5[^"]*text-xs[^"]*">\s*14 gün/', $html);
    }

    public function test_accordion_parts_outside_an_item_say_where_they_belong(): void
    {
        $this->expectExceptionMessage('acun:accordion.heading must be inside an acun:accordion-item with a name.');

        Blade::render('<x-ui.accordion><x-ui.accordion.heading>Başlık</x-ui.accordion.heading></x-ui.accordion>');
    }

    /** The width of the list, not the screen's, decides whether labels sit beside their values. */
    public function test_description_list_lays_out_by_its_own_width(): void
    {
        $html = Blade::render('<x-ui.description-list :items="[\'Ad soyad\' => \'Ayşe Yılmaz\']" />');

        self::assertMatchesRegularExpression('/<dl class="@container /', $html);
        self::assertStringContainsString('@sm:grid-cols-3', $html);
        self::assertStringNotContainsString(' sm:grid-cols-3', $html);
    }

    public function test_head_without_preference_url_does_not_post(): void
    {
        $html = Blade::render('<x-ui.layout.head />');

        self::assertNull($this->runtimeConfig($html)['preferenceUrl']);
        self::assertStringContainsString('<title>'.config('app.name'), $html);
    }

    public function test_confirmation_dialog_name_cannot_break_out_of_its_event(): void
    {
        $html = Blade::render('<x-ui.confirmation-dialog :name="$name" />', ['name' => "x'); alert(1); ('"]);

        // The event name is an attribute value (data-acun-dispatch), escaped; no script is written.
        self::assertStringContainsString('data-acun-dispatch="'.e("x'); alert(1); ('-confirmed").'"', $html);
        self::assertStringNotContainsString("alert(1); ('", $html);
        self::assertStringNotContainsString('$dispatch(', $html);
    }

    public function test_head_lets_the_page_be_zoomed_and_takes_a_csp_nonce(): void
    {
        $html = Blade::render('<x-ui.layout.head nonce="r4nd0m" />');

        self::assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1" />', $html);
        self::assertStringNotContainsString('user-scalable', $html);
        self::assertMatchesRegularExpression('/<script src="[^"]*acun-ui-boot[^"]*" nonce="r4nd0m" data-acun-ui-boot/', $html);
    }
}
