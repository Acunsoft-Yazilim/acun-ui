<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Menu\Menu;
use Acun\Ui\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MenuTest extends TestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function definition(): array
    {
        return [
            ['section' => 'Genel'],
            ['title' => 'Anasayfa', 'icon' => 'home', 'route' => 'anasayfa'],
            ['title' => 'Siparişler', 'icon' => 'shopping-bag', 'route' => 'siparisler.index', 'active' => 'siparisler.*'],
            ['title' => 'Raporlar', 'icon' => 'chart-bar', 'badge' => true, 'children' => [
                ['title' => 'Satışlar', 'url' => '/raporlar/satislar'],
                ['title' => 'Arşiv', 'children' => [['title' => '2025', 'url' => '/raporlar/arsiv/2025']]],
            ]],
            ['section' => 'Yönetim'],
            ['title' => 'Kullanıcılar', 'icon' => 'users', 'url' => '/kullanicilar'],
            ['section' => 'Destek'],
            ['title' => 'Yardım merkezi', 'icon' => 'lifebuoy', 'url' => 'https://destek.ornek.com', 'target' => '_blank'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/', fn () => '')->name('anasayfa');
        Route::get('/siparisler', fn () => '')->name('siparisler.index');
        Route::get('/siparisler/{id}/duzenle', fn () => '')->name('siparisler.duzenle');
        app('router')->getRoutes()->refreshNameLookups();
    }

    private function simulateRequest(string $url): void
    {
        $request = Request::create($url);
        $request->setRouteResolver(function () use ($request) {
            try {
                return app('router')->getRoutes()->match($request);
            } catch (NotFoundHttpException) {
                return null;
            }
        });
        $this->app->instance('request', $request);
    }

    public function test_hides_section_headings_with_no_items_below(): void
    {
        $titles = array_column((new Menu([
            ['section' => 'Genel'],
            ['title' => 'Anasayfa', 'url' => '/'],
            ['section' => 'Boş'],
            ['section' => 'Destek'],
            ['title' => 'Yardım', 'url' => '/yardim'],
        ]))->items(), 'title');

        self::assertSame(['Genel', 'Anasayfa', 'Destek', 'Yardım'], $titles);
    }

    public function test_finds_active_item_from_route_pattern_and_url(): void
    {
        $this->simulateRequest('/siparisler/5/duzenle');
        $orders = collect((new Menu($this->definition()))->items())->firstWhere('title', 'Siparişler');
        self::assertTrue($orders['active']);

        $this->simulateRequest('/raporlar/arsiv/2025');
        $reports = collect((new Menu($this->definition()))->items())->firstWhere('title', 'Raporlar');
        self::assertTrue($reports['active'], 'A group with an active grandchild must be active too.');
        self::assertTrue($reports['children'][1]['active']);
        self::assertFalse($reports['children'][0]['active']);
    }

    public function test_route_items_with_params_and_urls_with_a_query_are_active_only_on_their_own_address(): void
    {
        $menu = fn () => collect((new Menu([
            ['title' => 'Beşinci', 'route' => 'siparisler.duzenle', 'params' => ['id' => 5]],
            ['title' => 'Altıncı', 'route' => 'siparisler.duzenle', 'params' => ['id' => 6]],
            ['title' => 'Açık', 'url' => '/siparisler?durum=acik'],
            ['title' => 'Tümü', 'url' => '/siparisler'],
        ]))->items())->pluck('active', 'title')->all();

        $this->simulateRequest('/siparisler/5/duzenle');
        self::assertSame(['Beşinci' => true, 'Altıncı' => false, 'Açık' => false, 'Tümü' => false], $menu());

        $this->simulateRequest('/siparisler?durum=acik&sayfa=2');
        self::assertSame(['Beşinci' => false, 'Altıncı' => false, 'Açık' => true, 'Tümü' => true], $menu());

        $this->simulateRequest('/siparisler?durum=kapali');
        self::assertFalse($menu()['Açık']);
    }

    public function test_an_unknown_icon_name_shows_no_icon_instead_of_breaking_the_page(): void
    {
        $items = (new Menu([
            ['title' => 'Anasayfa', 'icon' => 'house', 'url' => '/'],
            ['title' => 'Grup', 'icon' => 'yok-boyle-ikon', 'children' => [['title' => 'Alt', 'icon' => 'home', 'url' => '/alt']]],
        ]))->items();

        self::assertNull($items[0]['icon']);
        self::assertNull($items[1]['icon']);
        self::assertSame('home', $items[1]['children'][0]['icon']);
        self::assertStringContainsString('Anasayfa', Blade::render('<x-ui.menu :menu="$menu" />', ['menu' => new Menu([['title' => 'Anasayfa', 'icon' => 'house', 'url' => '/']])]));
    }

    public function test_the_tree_is_worked_out_once_per_menu(): void
    {
        $calls = 0;
        $menu = new Menu([['title' => 'Anasayfa', 'url' => '/']], transform: function (array $item) use (&$calls) {
            $calls++;

            return $item;
        });

        $menu->items();
        $menu->searchItems();
        $menu->items();

        self::assertSame(1, $calls);
    }

    public function test_badge_true_gives_child_count_and_resolves_three_levels(): void
    {
        $reports = collect((new Menu($this->definition()))->items())->firstWhere('title', 'Raporlar');

        self::assertSame(2, $reports['badge']);
        self::assertSame('group', $reports['children'][1]['type']);
        self::assertSame(url('/raporlar/arsiv/2025'), $reports['children'][1]['children'][0]['href']);
    }

    public function test_reads_from_json_file(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'menu').'.json';
        file_put_contents($file, json_encode(['menu' => $this->definition()]));

        self::assertCount(count((new Menu($this->definition()))->items()), Menu::fromFile($file)->items());
    }

    public function test_search_items_are_built_from_menu(): void
    {
        $search = (new Menu($this->definition()))->searchItems();
        $sales = collect($search)->firstWhere('label', 'Satışlar');

        self::assertSame('Genel', $sales['group']);
        self::assertSame('Raporlar', $sales['hint']);
        self::assertSame('Raporlar › Arşiv', collect($search)->firstWhere('label', '2025')['hint']);
    }

    public function test_unsafe_url_schemes_become_hash(): void
    {
        $unsafe = [
            ['url' => 'javascript://%0Aalert(1)'],
            ['url' => "java\tscript://%0Aalert(1)"],
            ['href' => 'javascript:alert(1)'],
            ['href' => " \x01JaVaScRiPt:alert(1)"],
            ['href' => "java\nscript:alert(1)"],
            ['href' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            ['href' => 'vbscript:msgbox(1)'],
            // Protocol-relative: another site, not an application address.
            ['url' => '//evil.example/giris'],
            ['href' => '/\\evil.example'],
        ];

        $items = (new Menu(array_map(fn (array $link, int $i) => ['title' => 'Kötü '.$i] + $link, $unsafe, array_keys($unsafe))))->items();

        foreach ($items as $item) {
            self::assertSame('#', $item['href'], $item['title']);
        }

        $html = Blade::render('<x-ui.menu :menu="$menu" />', ['menu' => [['title' => 'Kötü', 'href' => 'javascript:alert(1)']]]);
        self::assertStringNotContainsStringIgnoringCase('javascript:', $html);
    }

    public function test_safe_urls_are_kept(): void
    {
        $items = collect((new Menu([
            ['title' => 'Harici', 'url' => 'https://destek.ornek.com/a?b=c'],
            ['title' => 'Http', 'url' => 'http://ornek.com'],
            ['title' => 'Protokolsüz', 'url' => '//localhost/x'],
            ['title' => 'Uygulama', 'url' => '/raporlar'],
            ['title' => 'Göreli href', 'href' => '/raporlar/satislar'],
            ['title' => 'Çapa', 'href' => '#bolum'],
            ['title' => 'E-posta', 'href' => 'mailto:destek@ornek.com'],
            ['title' => 'Telefon', 'url' => 'tel:+905551112233'],
        ]))->items())->pluck('href', 'title');

        self::assertSame('https://destek.ornek.com/a?b=c', $items['Harici']);
        self::assertSame('http://ornek.com', $items['Http']);
        // A protocol-relative address is kept only with the application's own host.
        self::assertSame('//localhost/x', $items['Protokolsüz']);
        self::assertSame(url('/raporlar'), $items['Uygulama']);
        self::assertSame('/raporlar/satislar', $items['Göreli href']);
        self::assertSame('#bolum', $items['Çapa']);
        self::assertSame('mailto:destek@ornek.com', $items['E-posta']);
        self::assertSame('tel:+905551112233', $items['Telefon']);
    }

    public function test_the_menu_does_no_authorization(): void
    {
        // Access rules belong to the application; a "can" key is ignored and every item is shown.
        $titles = array_column((new Menu([
            ['title' => 'Kullanıcılar', 'url' => '/kullanicilar', 'can' => 'yonetici'],
            ['title' => 'Raporlar', 'url' => '/raporlar'],
        ]))->items(), 'title');

        self::assertSame(['Kullanıcılar', 'Raporlar'], $titles);
    }

    public function test_renders_vertical_and_horizontal(): void
    {
        $this->simulateRequest('/siparisler');
        $vertical = Blade::render('<x-ui.menu :menu="$menu" />', ['menu' => $this->definition()]);

        self::assertStringContainsString('menu-heading', $vertical);
        self::assertStringContainsString('aria-current="page"', $vertical);
        self::assertStringContainsString('target="_blank"', $vertical);
        self::assertStringContainsString('Arşiv', $vertical);

        $horizontal = Blade::render('<x-ui.menu :menu="$menu" horizontal />', ['menu' => $this->definition()]);
        self::assertStringNotContainsString('menu-heading', $horizontal, 'The horizontal menu does not render section headings.');
        self::assertStringContainsString('group-focus-within:visible', $horizontal);
    }

    /**
     * A long horizontal menu groups by section: each section is one dropdown, its groups
     * open beside the panel, a single-entry section shows its entry; a short one stays flat.
     */
    public function test_horizontal_menu_groups_long_menus_by_section(): void
    {
        $this->simulateRequest('/siparisler');
        $menu = [
            ['title' => 'Pano', 'icon' => 'home', 'url' => '/'],
            ['section' => 'Bileşenler'],
            ['title' => 'Arayüz', 'icon' => 'cursor-arrow-rays', 'children' => [['title' => 'Butonlar', 'url' => '/ui/buttons'], ['title' => 'Rozetler', 'url' => '/ui/badges']]],
            ['title' => 'Kartlar', 'icon' => 'rectangle-stack', 'url' => '/cards'],
            ['section' => 'Tablolar'],
            ['title' => 'Standart tablo', 'icon' => 'table-cells', 'url' => '/tables/basic'],
            ['title' => 'DataTable', 'icon' => 'queue-list', 'children' => [['title' => 'Temel', 'url' => '/tables/datatable/basic']]],
            ['section' => 'Destek'],
            ['title' => 'Yardım merkezi', 'icon' => 'lifebuoy', 'url' => 'https://destek.ornek.com', 'target' => '_blank'],
            ['title' => 'Ayarlar', 'icon' => 'cog-6-tooth', 'url' => '/settings'],
            ['title' => 'Profil', 'icon' => 'user', 'url' => '/profile'],
        ];

        $html = Blade::render('<x-ui.menu :menu="$menu" horizontal />', ['menu' => $menu]);

        // Pano (before any section) stays a link; each of the three sections is one dropdown.
        self::assertMatchesRegularExpression('/>\s*Bileşenler\s*<svg/', $html);
        self::assertMatchesRegularExpression('/>\s*Tablolar\s*<svg/', $html);
        self::assertMatchesRegularExpression('/>\s*Destek\s*<svg/', $html);
        self::assertStringContainsString('group/sub relative', $html);
        self::assertStringContainsString('<span class="flex-1 truncate text-start">Arayüz</span>', $html);
        self::assertStringContainsString('/ui/buttons"', $html);

        // A short menu, or sections="false": items side by side, as before.
        self::assertStringNotContainsString('group/sub', Blade::render('<x-ui.menu :menu="$menu" horizontal />', ['menu' => $this->definition()]));
        self::assertStringNotContainsString('group/sub', Blade::render('<x-ui.menu :menu="$menu" horizontal :sections="false" />', ['menu' => $menu]));
    }

    public function test_menu_badges_are_shown_in_every_menu_position(): void
    {
        $menu = [
            ['title' => 'Gelen kutusu', 'href' => '/inbox', 'badge' => 4],
            ['title' => 'Raporlar', 'badge' => true, 'children' => [
                ['title' => 'Satış', 'href' => '/sales'],
                ['title' => 'Arşiv', 'badge' => 'Yeni', 'children' => [['title' => '2025', 'href' => '/2025']]],
            ]],
        ];

        $horizontal = Blade::render('<x-ui.menu :menu="$menu" horizontal />', ['menu' => $menu]);
        self::assertStringContainsString('>4</span>', $horizontal);
        self::assertStringContainsString('>2</span>', $horizontal);

        $vertical = Blade::render('<x-ui.menu :menu="$menu" />', ['menu' => $menu]);
        self::assertStringContainsString('>Yeni</span>', $vertical);
    }
}
