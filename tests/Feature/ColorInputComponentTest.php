<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component;
use Livewire\Livewire;

/**
 * acun:color-input: the #rrggbb text box is the form control (name, wire:model, the error), the browser's
 * colour picker beside it is the plugin's (js/plugins/color-input.js; behaviour: plain-html.test.mjs).
 */
final class ColorInputComponentTest extends TestCase
{
    public function test_the_text_box_is_the_control_and_the_picker_starts_at_its_colour(): void
    {
        $html = Blade::render('<acun:color-input name="renk" label="Ürün rengi" value="#C2410C" hint="Örneğin #c2410c" required class="max-w-xs" />');

        self::assertMatchesRegularExpression('~<div class="flex flex-col gap-1.5 min-w-0 max-w-xs">~', $html, 'Layout attributes place the whole field.');
        self::assertMatchesRegularExpression('~<label for="([^"]+)"[^>]*>\s*Ürün rengi\s*<span[^>]*>\*</span>~', $html);
        preg_match('~<label for="([^"]+)"~', $html, $label);
        self::assertMatchesRegularExpression('~<div class="flex items-center gap-2" data-acun-color-input>~', $html);
        self::assertMatchesRegularExpression('~<input\s+data-acun-control\s+type="color"\s+data-slot="color-swatch"\s+value="#c2410c"\s+aria-label="[^"]*Ürün rengi[^"]*"~', $html, 'The picker takes #rrggbb in lower case and names the field.');
        self::assertMatchesRegularExpression('~<input\s+data-acun-control\s+data-slot="color-value"\s+type="text"\s+id="'.preg_quote($label[1], '~').'"\s+name="renk"\s+value="#C2410C"~', $html);
        self::assertStringContainsString('pattern="#[0-9a-fA-F]{6}"', $html);
        self::assertMatchesRegularExpression('~aria-describedby="'.preg_quote($label[1], '~').'-message"[^>]*required~', $html, 'required goes on the text box.');
        self::assertStringContainsString('<p id="'.$label[1].'-message" class="text-sm text-muted">Örneğin #c2410c</p>', $html);
        self::assertSame(1, substr_count($html, 'name="'), 'Only the text box is submitted.');
    }

    public function test_a_value_that_is_not_a_colour_is_kept_in_the_box_escaped_and_leaves_the_picker_black(): void
    {
        $html = Blade::render('<acun:color-input name="renk" :value="$deger" />', ['deger' => '"><script>']);

        self::assertStringContainsString('value="#000000"', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('value="&quot;&gt;&lt;script&gt;"', $html);
    }

    public function test_an_error_marks_the_box_and_replaces_the_hint(): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['renk' => ['Renk #rrggbb biçiminde olmalı.']])));

        $html = Blade::render('<acun:color-input name="renk" label="Renk" hint="İpucu" />');

        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('ring-danger-line', $html);
        self::assertStringContainsString('class="text-sm text-danger-ink">Renk #rrggbb biçiminde olmalı.</p>', $html);
        self::assertStringNotContainsString('İpucu', $html);

        View::share('errors', null);
    }

    public function test_with_wire_model_both_parts_start_at_the_property(): void
    {
        $html = Livewire::test(BrandColorForm::class)->html();

        self::assertMatchesRegularExpression('~data-slot="color-swatch"\s+value="#15803d"~', $html);
        self::assertMatchesRegularExpression('~name="markaRengi"\s+value="#15803d"~', $html);
        self::assertStringContainsString('wire:model="markaRengi"', $html);
    }

    public function test_disabled_turns_both_parts_off(): void
    {
        $html = Blade::render('<acun:color-input name="renk" value="#2563eb" disabled />');

        self::assertSame(2, preg_match_all('~<input\b[^>]*\bdisabled\b~', $html));
    }
}

final class BrandColorForm extends Component
{
    public string $markaRengi = '#15803d';

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <acun:color-input wire:model="markaRengi" label="Marka rengi" />
            </div>
        BLADE;
    }
}
