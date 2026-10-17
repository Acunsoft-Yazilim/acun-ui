<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Livewire\Livewire;

/** The <acun:*> tags in a Livewire 4 single-file component (⚡name.blade.php): its view is Blade too. */
final class SingleFileComponentTest extends TestCase
{
    public function test_acun_tags_render_and_update_in_a_single_file_component(): void
    {
        Livewire::addLocation(viewPath: __DIR__.'/../Fixtures/livewire');

        Livewire::test('cart-line')
            ->assertSeeHtml('id="quantity"')
            ->assertSeeHtml('border-success bg-success text-on-success')
            ->assertSee('1 adet')
            ->call('increase')
            ->assertSet('quantity', 2)
            ->assertSee('2 adet');
    }
}
