<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

/** The passkey button and list draw the UI only; the WebAuthn steps are the application's. */
final class PasskeyComponentsTest extends TestCase
{
    public function test_the_button_hands_the_applications_steps_to_the_browser_component(): void
    {
        $html = Blade::render('<acun:passkey-button mode="register" options-method="passkeyOptions" action-method="addPasskey" name-field="#passkey-name" class="mt-4" />');

        self::assertSame(
            ['mode' => 'register', 'optionsUrl' => null, 'actionUrl' => null, 'optionsMethod' => 'passkeyOptions', 'actionMethod' => 'addPasskey', 'optionsGet' => false, 'nameField' => '#passkey-name'],
            self::config($html),
        );
        self::assertStringContainsString('class="space-y-2 mt-4"', $html);
        self::assertStringContainsString('x-on:click="run()"', $html);
        self::assertStringContainsString('Geçiş anahtarı ekle', $html);
        self::assertStringContainsString('role="alert"', $html);
    }

    public function test_login_is_the_default_and_unknown_modes_fall_back_to_it(): void
    {
        $html = Blade::render('<acun:passkey-button mode="other" options-url="/passkeys/options" action-url="/passkeys/login" options-get />');

        $config = self::config($html);
        self::assertSame('login', $config['mode']);
        self::assertTrue($config['optionsGet']);
        self::assertSame('/passkeys/options', $config['optionsUrl']);
        self::assertSame('/passkeys/login', $config['actionUrl']);
    }

    public function test_the_list_shows_each_passkey_with_its_dates_and_remove_button(): void
    {
        $html = Blade::render('<acun:passkey-list :passkeys="$passkeys" remove="removePasskey" />', ['passkeys' => [
            ['id' => 7, 'name' => 'MacBook', 'created_at' => Carbon::create(2026, 3, 4), 'last_used_at' => null],
            ['id' => 9, 'name' => '', 'created_at' => '12.05.2026', 'last_used_at' => '01.10.2026'],
        ]]);

        self::assertStringContainsString('MacBook', $html);
        self::assertStringContainsString('4 Mar 2026 tarihinde eklendi', $html);
        self::assertStringContainsString('Henüz kullanılmadı', $html);
        self::assertStringContainsString('wire:click="removePasskey(7)"', $html);
        self::assertStringContainsString('wire:confirm="Bu geçiş anahtarı kaldırılsın mı? Artık onunla giriş yapamazsınız."', $html);
        // A nameless passkey gets the generic name; text dates are shown as given.
        self::assertStringContainsString('>Geçiş anahtarı</p>', $html);
        self::assertStringContainsString('Son kullanım: 01.10.2026', $html);
        self::assertStringContainsString('wire:key="passkey-9"', $html);
    }

    public function test_the_list_without_passkeys_shows_its_slot_or_the_default_text(): void
    {
        self::assertStringContainsString('Henüz geçiş anahtarı eklenmedi.', Blade::render('<acun:passkey-list />'));
        self::assertStringContainsString('Add one to sign in without a password.', Blade::render('<acun:passkey-list>Add one to sign in without a password.</acun:passkey-list>'));
        self::assertStringNotContainsString('wire:click', Blade::render('<acun:passkey-list :passkeys="[[\'id\' => 1, \'name\' => \'iPhone\']]" />'));
    }

    /** The acunPasskey(...) settings the button prints (Js::from: JSON.parse('…')). */
    private static function config(string $html): array
    {
        self::assertMatchesRegularExpression("/x-data=\"acunPasskey\\(JSON\\.parse\\('(.*?)'\\)\\)\"/", $html);
        preg_match("/acunPasskey\\(JSON\\.parse\\('(.*?)'\\)\\)/", $html, $match);

        return json_decode(json_decode('"'.$match[1].'"'), true);
    }
}
