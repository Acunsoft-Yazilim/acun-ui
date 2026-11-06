<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

/** acun:layout.head prints the browser's validation messages in the current language (behaviour: plain-html.test.mjs). */
final class ValidationMessagesTest extends TestCase
{
    /** @return array<string, mixed> */
    private function settings(): array
    {
        preg_match('~<script type="application/json" data-acun-ui-config="[^"]+">(.*?)</script>~s', Blade::render('<acun:layout.head title="Siparişler" />'), $island);

        return json_decode($island[1], true);
    }

    public function test_the_head_prints_the_messages_in_the_current_language(): void
    {
        app()->setLocale('tr');
        $validation = $this->settings()['validation'];

        self::assertSame('Lütfen bu alanı doldurun.', $validation['valueMissing']);
        self::assertSame('Lütfen bir e-posta adresi girin.', $validation['typeMismatchEmail']);
        self::assertSame('Lütfen en az :minlength karakter kullanın.', $validation['tooShort'], 'The field fills in its own limits.');

        app()->setLocale('en');
        self::assertSame('Please fill in this field.', $this->settings()['validation']['valueMissing']);
    }

    public function test_validation_messages_false_leaves_the_bubbles_to_the_browser(): void
    {
        config(['acun-ui.validation_messages' => false]);

        self::assertNull($this->settings()['validation']);
    }
}
