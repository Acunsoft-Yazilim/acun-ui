<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Livewire\Concerns\WithToasts;
use Acun\Ui\Support\Toast;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

final class ToastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Blade::render runs outside the HTTP kernel: give the request a session like the web middleware does.
        $this->app['request']->setLaravelSession($this->app['session']->driver());
    }

    public function test_a_link_with_an_unsafe_scheme_is_dropped(): void
    {
        self::assertNull(Toast::payload('Hazır', link: 'javascript:alert(document.cookie)')['link']);
        self::assertNull(Toast::payload('Hazır', link: "java\tscript:alert(1)")['link']);
        self::assertSame('/kayitlar/5', Toast::payload('Hazır', link: '/kayitlar/5')['link']);
        self::assertSame('mailto:destek@ornek.com', Toast::payload('Hazır', link: 'mailto:destek@ornek.com')['link']);
        // Protocol-relative: kept only with the application's own host.
        self::assertNull(Toast::payload('Hazır', link: '//evil.example/giris')['link']);
        self::assertSame('//localhost/kayitlar', Toast::payload('Hazır', link: '//localhost/kayitlar')['link']);

        // A toast dispatched straight from a component skips payload(): the container checks it too.
        $html = Blade::render('<x-ui.toast-container />');
        self::assertStringContainsString('const link = this.safeLink(toast.link);', html_entity_decode($html, ENT_QUOTES));
    }

    public function test_the_trait_dispatches_a_toast_event(): void
    {
        Livewire::test(ToastingComponent::class)
            ->call('save')
            ->assertDispatched('toast', type: 'success', message: 'Kaydedildi.')
            ->call('fail')
            ->assertDispatched('toast', type: 'error', message: 'Silinemedi.', link: '/kayitlar', linkLabel: 'Göster', download: false)
            ->call('failAsDanger')
            ->assertDispatched('toast', type: 'error', message: 'Bağlantı kesildi.')
            ->call('export')
            ->assertDispatched('toast', type: 'info', link: '/disa-aktarim/5.xlsx', linkLabel: 'İndir', download: true);
    }

    public function test_the_shortcuts_cannot_be_called_from_the_browser(): void
    {
        $this->expectException(MethodNotFoundException::class);

        Livewire::test(ToastingComponent::class)->call('toast', 'Sahte bildirim');
    }

    public function test_a_flashed_toast_is_shown_once_on_the_next_page(): void
    {
        Livewire::test(ToastingComponent::class)
            ->call('saveAndRedirect')
            ->assertNotDispatched('toast')
            ->assertRedirect('/kayitlar');

        // The same queueing in this test's session (a Livewire test request gets its own session).
        (new ToastingComponent)->queueForNextPage();

        $first = Blade::render('<x-ui.toast-container />');
        self::assertStringContainsString('x-init=', $first);
        self::assertStringContainsString('Eklendi.', $first);

        $second = Blade::render('<x-ui.toast-container />');
        self::assertStringNotContainsString('Eklendi.', $second, 'A flashed toast is shown only once.');
    }

    public function test_several_toasts_can_be_queued(): void
    {
        Toast::flash('Birinci');
        Toast::flash('İkinci', 'warning');

        $html = Blade::render('<x-ui.toast-container />');

        self::assertStringContainsString('Birinci', $html);
        self::assertStringContainsString('warning', $html);
    }

    public function test_a_flashed_message_cannot_break_out_of_the_attribute(): void
    {
        Toast::flash('"><script>alert(1)</script>');

        $html = Blade::render('<x-ui.toast-container />');

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_danger_is_the_error_type(): void
    {
        // The palette's name (acun:button, alert, badge): it used to throw and the page answered 500.
        self::assertSame('error', Toast::payload('Silinemedi.', 'danger')['type']);
        self::assertSame('error', Toast::payload('Silinemedi.', 'error')['type']);
        self::assertSame('error', Toast::payload('Silinemedi.', 'red')['type']);
        self::assertSame('success', Toast::payload('Kaydedildi.', 'green')['type']);
        self::assertSame(['success', 'error', 'warning', 'info'], array_map(fn (string $type) => Toast::type($type), Toast::TYPES));

        Toast::flash('Silinemedi.', 'danger');
        self::assertSame('error', self::flashed(Blade::render('<x-ui.toast-container />'))[0]['type']);
    }

    /** Toast::type('Danger') answered 500 while the container showed the same value as info. */
    public function test_the_type_ignores_case_and_surrounding_spaces(): void
    {
        self::assertSame('error', Toast::type('Danger'));
        self::assertSame('error', Toast::type('ERROR'));
        self::assertSame('success', Toast::payload('Kaydedildi.', ' Success ')['type']);
        self::assertSame('warning', Toast::payload('Dikkat.', 'Amber')['type']);

        Toast::flash('Silinemedi.', 'Danger');
        self::assertSame('error', self::flashed(Blade::render('<x-ui.toast-container />'))[0]['type']);
        // The container reads a dispatched type the same way (tests/js/components.test.mjs).
    }

    public function test_a_download_link_is_flagged_in_the_payload_and_the_flash(): void
    {
        self::assertFalse(Toast::payload('Hazır', link: '/rapor.pdf')['download']);
        self::assertTrue(Toast::payload('Hazır', 'info', '/rapor.pdf', 'İndir', download: true)['download']);

        Toast::flash('Rapor hazır.', 'info', '/rapor.pdf', 'İndir', download: true);
        $html = Blade::render('<x-ui.toast-container />');

        self::assertSame(['type' => 'info', 'message' => 'Rapor hazır.', 'link' => '/rapor.pdf', 'linkLabel' => 'İndir', 'download' => true], self::flashed($html)[0]);
        // The container gives the link the download attribute and skips wire:navigate for it (tests/js/components.test.mjs).
        self::assertStringContainsString(':download="toast.download"', $html);
        self::assertStringContainsString('if (toast.download !== null) { dismiss(toast.id); return }', html_entity_decode($html, ENT_QUOTES));
    }

    /**
     * The toasts the container's x-init pushes (Js::from writes JSON.parse('…')).
     *
     * @return list<array<string, mixed>>
     */
    private static function flashed(string $html): array
    {
        self::assertSame(1, preg_match("/x-init=\"JSON\\.parse\\('(.*?)'\\)\\.forEach/", html_entity_decode($html, ENT_QUOTES), $match));

        return json_decode(json_decode('"'.$match[1].'"', flags: JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, array{string}> */
    public static function unknownTypes(): array
    {
        return ['a palette colour that is not a toast type' => ['primary'], 'a made-up name' => ['notice'], 'empty' => ['']];
    }

    #[DataProvider('unknownTypes')]
    public function test_unknown_types_are_rejected(string $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        Toast::flash('Kaydedildi.', $type);
    }
}

final class ToastingComponent extends Component
{
    use WithToasts;

    public function save(): void
    {
        $this->toast('Kaydedildi.');
    }

    public function fail(): void
    {
        $this->toast('Silinemedi.', 'error', link: '/kayitlar', linkLabel: 'Göster');
    }

    public function failAsDanger(): void
    {
        $this->toast('Bağlantı kesildi.', 'danger');
    }

    public function export(): void
    {
        $this->toast('Dışa aktarma hazır.', 'info', link: '/disa-aktarim/5.xlsx', linkLabel: 'İndir', download: true);
    }

    public function saveAndRedirect(): void
    {
        $this->toastAfterRedirect('Eklendi.');
        $this->redirect('/kayitlar');
    }

    public function queueForNextPage(): void
    {
        $this->toastAfterRedirect('Eklendi.');
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
