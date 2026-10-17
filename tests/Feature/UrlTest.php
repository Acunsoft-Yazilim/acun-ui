<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Url;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL as UrlGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Acun\Ui\Support\Url: which links from data may become a link, and which addresses stay on
 * the application's origin (the test request's is http://localhost).
 */
final class UrlTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function otherSites(): array
    {
        return [
            'protocol-relative' => ['//evil.example/giris'],
            'backslash' => ['/\\evil.example'],
            'two backslashes' => ['\\\\evil.example'],
            'three slashes' => ['///evil.example'],
            'leading space and tab' => [" \t//evil.example"],
            'own host as user name' => ['//localhost@evil.example'],
            'own host after a backslash' => ['//evil.example\\@localhost'],
            'own host as a sub-domain' => ['//localhost.evil.example'],
        ];
    }

    #[DataProvider('otherSites')]
    public function test_a_protocol_relative_address_of_another_site_is_not_safe(string $url): void
    {
        self::assertTrue(Url::isProtocolRelative($url));
        self::assertFalse(Url::isSafe($url));
        self::assertNull(Url::safe($url));
        self::assertFalse(Url::isSameOrigin($url));
    }

    public function test_a_protocol_relative_address_of_the_application_is_safe(): void
    {
        self::assertSame('//localhost/raporlar', Url::safe('//localhost/raporlar'));
        self::assertSame('//LOCALHOST/raporlar', Url::safe('//LOCALHOST/raporlar'));
        self::assertTrue(Url::isSameOrigin('//localhost/raporlar'));
        self::assertNull(Url::safe('//localhost:8080/raporlar'), 'Another port is another site.');
    }

    public function test_the_safe_schemes_and_relative_addresses_are_kept(): void
    {
        foreach (['https://destek.ornek.com/a?b=c', 'http://ornek.com', '/raporlar', 'raporlar/satislar', '?sayfa=2', '#bolum', 'mailto:destek@ornek.com', 'tel:+905551112233'] as $url) {
            self::assertSame($url, Url::safe($url), $url);
            self::assertFalse(Url::isProtocolRelative($url), $url);
        }

        foreach (['javascript:alert(1)', "java\tscript:alert(1)", 'data:text/html,x', 'vbscript:x'] as $url) {
            self::assertNull(Url::safe($url), $url);
        }
    }

    public function test_only_the_application_origin_is_the_same_origin(): void
    {
        foreach (['/ara', 'ara', '?q=1', '#sonuc', 'http://localhost/ara', 'HTTP://LocalHost:80/ara', '//localhost/ara'] as $url) {
            self::assertTrue(Url::isSameOrigin($url), $url);
        }

        foreach (['https://localhost/ara', 'http://localhost:8000/ara', 'https://evil.example/ara', 'http://localhost.evil.example', 'mailto:a@ornek.com', 'javascript:alert(1)', 'http:\\\\evil.example'] as $url) {
            self::assertFalse(Url::isSameOrigin($url), $url);
        }
    }

    /**
     * A link that is not printed (every component prints "#" instead) is no longer silent: the
     * developer finds a warning in the log, once per request and address. For another site's
     * //host it says to write the address with https://. The rule itself stays as it is.
     */
    public function test_a_link_that_is_not_printed_leaves_one_warning_per_address_in_the_log(): void
    {
        Log::spy();

        self::assertNull(Url::safe('//cdn.ornek.com/logo.png'));
        self::assertNull(Url::safe('//cdn.ornek.com/logo.png'));
        self::assertFalse(Url::isSafe('//cdn.ornek.com/logo.png'));
        self::assertNull(Url::safe('javascript:alert(1)'));
        self::assertSame('/siparisler', Url::safe('/siparisler'));
        self::assertSame('https://cdn.ornek.com/logo.png', Url::safe('https://cdn.ornek.com/logo.png'));
        self::assertSame('//localhost/raporlar', Url::safe('//localhost/raporlar'));

        Log::shouldHaveReceived('warning')->with(Mockery::on(fn ($message) => str_contains($message, '//host') && str_contains($message, 'https://')), ['url' => '//cdn.ornek.com/logo.png'])->once();
        Log::shouldHaveReceived('warning')->with(Mockery::on(fn ($message) => str_contains($message, 'http, https, mailto, tel')), ['url' => 'javascript:alert(1)'])->once();
        Log::shouldHaveReceived('warning')->twice();

        // The components print "#" and the log says why.
        self::assertStringContainsString('href="#"', Blade::render('<x-ui.button href="//cdn.ornek.com/katalog.pdf">Katalog</x-ui.button>'));
        Log::shouldHaveReceived('warning')->with(Mockery::type('string'), ['url' => '//cdn.ornek.com/katalog.pdf'])->once();

        // A long address is cut in the log.
        Url::safe('javascript:'.str_repeat('a', 500));
        Log::shouldHaveReceived('warning')->with(Mockery::type('string'), Mockery::on(fn (array $context) => str_starts_with($context['url'], 'javascript:aaa') && mb_strlen($context['url']) <= 201))->once();
    }

    public function test_a_forced_root_url_is_the_application_origin_too(): void
    {
        UrlGenerator::forceScheme('https');
        UrlGenerator::forceRootUrl('https://panel.ornek.com');

        self::assertTrue(Url::isSameOrigin('https://panel.ornek.com/ara'));
        self::assertTrue(Url::isSameOrigin('http://localhost/ara'), 'The request host still counts.');
        self::assertSame('//panel.ornek.com/ara', Url::safe('//panel.ornek.com/ara'));
        self::assertFalse(Url::isSameOrigin('https://ornek.com/ara'));
    }
}
