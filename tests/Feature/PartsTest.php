<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Parts;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\HtmlString;

final class PartsTest extends TestCase
{
    public function test_a_slot_that_starts_with_a_part_uses_parts(): void
    {
        self::assertTrue(Parts::used('<div data-slot="card-header">Başlık</div>', 'card'));
        self::assertTrue(Parts::used(new HtmlString("\n  <!--[if BLOCK]><![endif]-->\n<section class=\"x\" data-slot=\"card-body\">…</section>"), 'card'));
        self::assertSame('card-header', Parts::name('card', 'header'));
    }

    public function test_plain_content_and_nested_containers_do_not(): void
    {
        self::assertFalse(Parts::used('Düz metin', 'card'));
        self::assertFalse(Parts::used('<p>Metin</p><div data-slot="card-header">İç kart</div>', 'card'), 'Only the first element counts.');
        self::assertFalse(Parts::used('<div data-slot="modal-header">…</div>', 'card'), 'Another component\'s part.');
        self::assertFalse(Parts::used(null, 'card'));
    }
}
