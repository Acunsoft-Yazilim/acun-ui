<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\AcunTagCompiler;
use Acun\Ui\Support\PaidComponents;
use Acun\Ui\Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The free package works alone (this suite registers only its provider): acun:layout.head,
 * acun:layout.blank and every component render, nothing in it uses acunsoft/acun-pro, and a tag of
 * the paid package stops with an error that names it. scripts/test-install.mjs checks the same in a
 * fresh application with only acunsoft/acun-ui installed.
 */
final class FreeAloneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    /**
     * Every free component in use, grouped by family; together they use every name under
     * resources/views/components/ui (test_the_samples_use_every_component).
     *
     * @return array<string, array{0: string}>
     */
    public static function samples(): array
    {
        return [
            'layout.head' => ['<acun:layout.head title="Mağaza" />'],
            'layout.blank' => ['<acun:layout.blank title="Mağaza"><acun:slot:head><link rel="icon" href="/favicon.svg"></acun:slot:head><p>Sayfa</p></acun:layout.blank>'],
            'accordion' => ['<acun:accordion open="a"><acun:accordion-item name="a" title="Kargo ne zaman gelir?">İki iş günü.</acun:accordion-item><acun:accordion.item name="b"><acun:accordion.heading>İade</acun:accordion.heading><acun:accordion.content>14 gün.</acun:accordion.content></acun:accordion.item><acun:accordion.item name="c"><acun:accordion.trigger>Sipariş #1042</acun:accordion.trigger><acun:accordion.content>Kargoda.</acun:accordion.content></acun:accordion.item></acun:accordion>'],
            'alert' => ['<acun:alert variant="success"><acun:alert.title>Kaydedildi</acun:alert.title><acun:alert.description>Sipariş güncellendi.</acun:alert.description></acun:alert>'],
            'auth' => ['<acun:auth-header title="Giriş" description="Hesabınıza girin" /><acun:auth-session-status status="Bağlantı gönderildi" />'],
            'avatar' => ['<acun:avatar-group :more="2"><acun:avatar name="Ayşe Yılmaz" status="online" /><acun:avatar name="Mert Kaya" /></acun:avatar-group>'],
            'badge' => ['<acun:badge variant="success">Ödendi</acun:badge>'],
            'breadcrumb' => ["<acun:breadcrumb :items=\"[['label' => 'Siparişler', 'url' => '/siparisler'], ['label' => '#1042']]\" /><acun:breadcrumb><acun:breadcrumb.item href=\"/\">Anasayfa</acun:breadcrumb.item><acun:breadcrumb.separator>/</acun:breadcrumb.separator><acun:breadcrumb.item :separator=\"false\">Siparişler</acun:breadcrumb.item></acun:breadcrumb>"],
            'button' => ['<acun:button-group><acun:button>Kaydet</acun:button><acun:button variant="white">Vazgeç</acun:button></acun:button-group>'],
            'card' => ['<acun:card title="Özet">Gövde</acun:card><acun:card><acun:card.header title="Kargolar" /><acun:card.body>8 paket</acun:card.body><acun:card.footer>Tümü</acun:card.footer></acun:card>'],
            'carousel' => ['<acun:carousel><acun:carousel.item label="Bir">1</acun:carousel.item><acun:carousel.item>2</acun:carousel.item></acun:carousel>'],
            'checkbox' => ['<acun:checkbox-group label="Kanallar" name="kanallar"><acun:checkbox name="kanallar[]" value="sms" label="SMS" /></acun:checkbox-group>'],
            'chip-group' => ["<acun:chip-group name=\"durum\" value=\"hepsi\" :options=\"['hepsi' => 'Hepsi', 'kargoda' => 'Kargoda']\" />"],
            'collapse' => ['<acun:collapse label="Ayrıntı">Gizli</acun:collapse>'],
            'command-palette' => ["<acun:command-palette :items=\"[['label' => 'Siparişler', 'href' => '/siparisler', 'group' => 'Sayfalar']]\" />"],
            'confirmation-dialog' => ['<acun:confirmation-dialog name="sil" title="Silinsin mi?" />'],
            'copy-button' => ['<acun:copy-button text="TR33 0006 1005" />'],
            'description-list' => ["<acun:description-list :items=\"['Müşteri' => 'Ayşe Yılmaz']\" /><acun:description-list><acun:description-list.item label=\"Kargo\">Yurtiçi</acun:description-list.item><acun:description-item label=\"Ödeme\">Kart</acun:description-item></acun:description-list>"],
            'divider' => ['<acun:divider>Bölüm</acun:divider>'],
            'drawer' => ['<acun:drawer.trigger name="yan"><button type="button">Aç</button></acun:drawer.trigger><acun:drawer name="yan"><acun:drawer.header title="Ayrıntı" /><acun:drawer.body>İçerik</acun:drawer.body><acun:drawer.footer><acun:drawer.close><button type="button">Kapat</button></acun:drawer.close></acun:drawer.footer></acun:drawer><acun:drawer name="filtre" docked title="Filtreler">F</acun:drawer>'],
            'dropdown' => ['<acun:dropdown><acun:dropdown.trigger><button type="button">İşlemler</button></acun:dropdown.trigger><acun:dropdown.content><acun:dropdown.heading>Sipariş</acun:dropdown.heading><acun:dropdown.item href="/x">Git</acun:dropdown.item><acun:dropdown.separator /><acun:dropdown-item>Sil</acun:dropdown-item></acun:dropdown.content></acun:dropdown>'],
            'empty-state' => ['<acun:empty-state title="Kayıt yok" description="Filtreyi değiştirin" />'],
            'file-icon' => ['<acun:file-icon name="fatura.pdf" />'],
            'flag' => ['<acun:flag code="tr" />'],
            'footer' => ['<acun:footer>© 2026</acun:footer>'],
            'icon' => ['<acun:icon name="home" />'],
            'inline-confirm' => ['<acun:inline-confirm question="Silinsin mi?" wire:click="sil">Sil</acun:inline-confirm>'],
            'input' => ['<acun:input name="email" label="E-posta" /><acun:input-group name="tel" prefix="+90" />'],
            'kbd' => ["<acun:kbd :keys=\"['⌘', 'K']\" />"],
            'list-group' => ['<acun:list-group><acun:list-group-item href="/a">A</acun:list-group-item><acun:list-group.item>B</acun:list-group.item></acun:list-group>'],
            'list-toolbar' => ['<acun:list-toolbar><acun:slot:start><acun:per-page-select /></acun:slot:start><acun:search wire:model.live="arama" placeholder="Ara" /><acun:slot:actions><acun:button>Yeni</acun:button></acun:slot:actions></acun:list-toolbar>'],
            'locale-switcher' => ["<acun:locale-switcher action=\"/dil\" :locales=\"['tr' => 'Türkçe', 'en' => 'English']\" />"],
            'logo' => ['<acun:logo src="/logo.svg" />'],
            'modal' => ['<acun:modal.trigger name="duzenle"><button type="button">Düzenle</button></acun:modal.trigger><acun:modal name="duzenle"><acun:modal.header title="Düzenle" /><acun:modal.body>Gövde</acun:modal.body><acun:modal.footer><acun:modal.close><button type="button">Vazgeç</button></acun:modal.close></acun:modal.footer></acun:modal>'],
            'page-header' => ['<acun:page-header title="Siparişler" />'],
            'passkey' => ['<acun:passkey-button options-url="/passkey/options" action-url="/passkey" /><acun:passkey-list :passkeys="[]" />'],
            'popover' => ['<acun:popover><acun:popover.trigger><button type="button">?</button></acun:popover.trigger><acun:popover.content aria-label="Kural">Açıklama</acun:popover.content></acun:popover>'],
            'progress' => ['<acun:progress :value="40" />'],
            'radio' => ['<acun:radio-group label="Kargo" name="kargo"><acun:radio name="kargo" value="hizli" label="Hızlı" /></acun:radio-group>'],
            'recovery-codes' => ["<acun:recovery-codes :codes=\"['a1b2-c3d4']\" />"],
            'scroll-area' => ['<acun:scroll-area>İçerik</acun:scroll-area>'],
            'segmented' => ["<acun:segmented name=\"donem\" :options=\"['gun' => 'Gün', 'hafta' => 'Hafta']\" />"],
            'select' => ["<acun:select name=\"durum\" :options=\"['1' => 'Aktif']\" />"],
            'selection-bar' => ['<acun:selection-bar :count="3"><acun:button>Sil</acun:button></acun:selection-bar>'],
            'skeleton' => ['<acun:skeleton variant="text" />'],
            'sortable-th' => ['<table><thead><tr><acun:sortable-th field="ad" sort-field="ad" sort-direction="asc">Ad</acun:sortable-th></tr></thead></table>'],
            'spinner' => ['<acun:spinner />'],
            'stat-card' => ['<acun:stat-card label="Sipariş" value="3.106" />'],
            'switch' => ['<acun:switch name="bildirim" label="Bildirim" />'],
            'table' => ['<acun:table><acun:table.columns><acun:table.column>Ad</acun:table.column></acun:table.columns><acun:table.rows><acun:table.row><acun:table.cell>Ayşe</acun:table.cell></acun:table.row></acun:table.rows></acun:table>'],
            'tabs' => ["<acun:tabs :tabs=\"['genel' => 'Genel']\"><acun:tab-panel value=\"genel\">G</acun:tab-panel></acun:tabs><acun:tabs><acun:tabs.list><acun:tabs.trigger value=\"a\">A</acun:tabs.trigger></acun:tabs.list><acun:tabs.panel value=\"a\">x</acun:tabs.panel></acun:tabs>"],
            'textarea' => ['<acun:textarea name="not" label="Not" />'],
            'theme' => ['<acun:theme-toggle /><acun:theme-customizer /><acun:brand-color-picker label="Ana renk" />'],
            'theme-switcher' => ['<acun:theme-switcher /><acun:theme-switcher as="drawer" trigger="button"><acun:theme-switcher.section name="yogunluk" title="Yoğunluk" :options="[\'rahat\' => \'Rahat\', \'sik\' => \'Sık\']" value="$store.yogunluk.deger" change="$store.yogunluk.sec" /></acun:theme-switcher><acun:theme-switcher as="inline" :sections="[\'color\']" />'],
            'toast-container' => ['<acun:toast-container />'],
            'tooltip' => ['<acun:tooltip text="İpucu">?</acun:tooltip>'],
            'two-factor-qr' => ['<acun:two-factor-qr secret="JBSWY3DPEHPK3PXP"><svg viewBox="0 0 10 10"></svg></acun:two-factor-qr>'],
        ];
    }

    #[DataProvider('samples')]
    public function test_every_free_component_renders_without_the_paid_package(string $blade): void
    {
        self::assertNotSame('', trim(Blade::render($blade)));
    }

    /** The pagination view is a component file too; Laravel's paginators draw it with links(). */
    public function test_the_pagination_view_renders(): void
    {
        $html = view('acun-ui::components.ui.pagination', ['paginator' => new LengthAwarePaginator(range(1, 10), 30, 10, 2)])->render();

        self::assertStringContainsString('role="navigation"', $html);
    }

    public function test_the_samples_use_every_component(): void
    {
        $used = ['pagination'];
        foreach (self::samples() as [$blade]) {
            array_push($used, ...AcunTagCompiler::names($blade));
        }

        $components = AcunTagsTest::componentNames(__DIR__.'/../../resources/views/components/ui');

        self::assertSame([], array_values(array_diff($components, $used)), 'Components without a sample.');
        self::assertSame([], array_values(array_diff(array_unique($used), $components)), 'Samples of components the package does not have.');
    }

    /** acunsoft/acun-ui never uses the paid package: no class, view or namespace of it, only the optional extras. */
    public function test_nothing_in_the_package_uses_the_paid_one(): void
    {
        $root = __DIR__.'/../..';
        $found = [];

        foreach (['src', 'resources', 'config', 'routes'] as $folder) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$root/$folder", \FilesystemIterator::SKIP_DOTS)) as $file) {
                // Comments may name the paid package; code may not.
                $source = (string) preg_replace(['/\{\{--.*?--\}\}|\/\*.*?\*\//s', '/^\s*(?:\/\/|\*).*$/m'], '', (string) file_get_contents($file->getPathname()));
                $source = str_replace("@includeIf('acun-pro::layout-extras')", '', $source);

                if (preg_match('/Acun\\\\(?:Panel|Commerce|Themes|AcunProServiceProvider)\b|acun-pro::|acun-ui-pro::|ecommerce::/', $source, $match)) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1).': '.$match[0];
                }
            }
        }

        self::assertSame([], $found);
    }

    /** @return array<string, array{0: string}> */
    public static function paidTags(): array
    {
        return [
            'panel layout' => ['<acun:layout.sidebar brand-name="Panel">İçerik</acun:layout.sidebar>'],
            'menu' => ['<acun:menu menu="main" />'],
            'menu part' => ['<acun:nav.item href="/" title="Anasayfa" />'],
            'PRO component' => ['<acun:combobox name="sehir" :options="[1 => \'İstanbul\']" />'],
            'x-acun:: form' => ['<x-acun::chart />'],
            'storefront' => ['<acun:shop.price :amount="100" />'],
        ];
    }

    #[DataProvider('paidTags')]
    public function test_a_paid_tag_says_which_package_it_needs(string $blade): void
    {
        try {
            Blade::render($blade);
            self::fail('A paid tag rendered without acunsoft/acun-pro.');
        } catch (ViewException|\InvalidArgumentException $exception) {
            $message = $exception->getMessage();
        }

        self::assertStringContainsString('is an Acun Pro component (acunsoft/acun-pro)', $message);
    }

    /** Shown code and comments are not tags; an unknown name is not a paid one (Laravel's own error). */
    public function test_only_real_tags_of_paid_components_stop(): void
    {
        self::assertStringContainsString('<acun:combobox />', Blade::render('@verbatim<acun:combobox />@endverbatim'));
        self::assertSame('x', trim(Blade::render("{{-- <acun:layout.sidebar> --}}\nx")));
        self::assertTrue(PaidComponents::isPaid('nav.horizontal-group'));
        self::assertTrue(PaidComponents::isPaid('shop.navbar.header'));
        self::assertFalse(PaidComponents::isPaid('navigation'));
        self::assertFalse(PaidComponents::isPaid('layout.blank'));

        try {
            Blade::render('<acun:buton>Kaydet</acun:buton>');
            self::fail('An unknown tag rendered.');
        } catch (ViewException|\InvalidArgumentException $exception) {
            self::assertStringContainsString('Unable to locate a class or view for component [acun::buton]', $exception->getMessage());
        }
    }
}
