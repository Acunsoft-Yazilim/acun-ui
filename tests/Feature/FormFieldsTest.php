<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Field;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Component;
use Livewire\Livewire;

/**
 * What a classic form and a Livewire form need from the fields: the value they were
 * given (or got back after a failed validation), their errors, stable ids, a visible
 * keyboard focus, and links that cannot run a script.
 */
final class FormFieldsTest extends TestCase
{
    private function withErrors(array $messages): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));
    }

    private function withOldInput(array $input): void
    {
        $this->app['request']->setLaravelSession($this->app['session.store']);
        $this->app['session.store']->flashInput($input);
    }

    /** The opening tag that starts with the given pattern. */
    private static function tag(string $html, string $start): string
    {
        self::assertSame(1, preg_match('/'.$start.'(?:[^>"]|"[^"]*")*>/s', $html, $match), "No tag starting with $start");

        return $match[0];
    }

    public function test_a_bound_select_without_placeholder_shows_no_value_the_model_does_not_have(): void
    {
        $html = Blade::render('<x-ui.select wire:model="birim" :options="[\'kg\' => \'Kilogram\', \'lt\' => \'Litre\']" />');

        self::assertSame(strpos($html, '<option'), strpos($html, '<option value="" hidden></option>'), 'The empty option comes first.');
        self::assertMatchesRegularExpression('/<option value="" hidden><\/option>\s*<option value="kg">/', $html);
        self::assertStringNotContainsString('<option value="" hidden></option>', Blade::render('<x-ui.select name="birim" :options="[\'kg\' => \'Kilogram\']" />'), 'A classic select keeps the browser default.');
    }

    public function test_a_classic_select_and_textarea_show_their_value_and_get_old_input_back(): void
    {
        $options = "['a' => 'Taslak', 'b' => 'Yayında', 'c' => 'Arşiv']";

        self::assertStringContainsString('<option value="b" selected>Yayında</option>', Blade::render("<x-ui.select name=\"durum\" :options=\"{$options}\" value=\"b\" />"));
        self::assertMatchesRegularExpression('/<textarea[^>]*>Merhaba<\/textarea>/', Blade::render('<x-ui.textarea name="not" value="Merhaba" />'));
        self::assertStringNotContainsString(' value="', Blade::render('<x-ui.textarea name="not" value="Merhaba" />'));

        $this->withOldInput(['durum' => 'c', 'not' => 'Geri geldi', 'etiket' => ['a', 'c']]);
        $select = Blade::render("<x-ui.select name=\"durum\" placeholder=\"Seçin\" :options=\"{$options}\" value=\"b\" />");
        self::assertStringContainsString('<option value="c" selected>Arşiv</option>', $select);
        self::assertStringNotContainsString('<option value="b" selected>', $select);
        self::assertStringNotContainsString('hidden selected', $select, 'The placeholder is not selected when a value is.');
        self::assertMatchesRegularExpression('/<textarea[^>]*>Geri geldi<\/textarea>/', Blade::render('<x-ui.textarea name="not" value="Merhaba" />'));

        $multiple = Blade::render("<x-ui.select name=\"etiket[]\" multiple :options=\"{$options}\" />");
        self::assertStringContainsString('<option value="a" selected>', $multiple);
        self::assertStringContainsString('<option value="c" selected>', $multiple);

        // Bound fields take their value from the model, never from old().
        self::assertStringNotContainsString(' selected>', Blade::render("<x-ui.select wire:model=\"durum\" :options=\"{$options}\" />"));
    }

    public function test_errors_of_bracket_names_are_found_and_no_error_bag_is_no_error(): void
    {
        View::share('errors', null);
        self::assertStringNotContainsString('aria-invalid', Blade::render('<x-ui.input name="adres[il]" label="İl" />'));

        $this->withErrors(['adres.il' => 'İl seçin.']);
        $html = Blade::render('<x-ui.input name="adres[il]" label="İl" />');
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('İl seçin.', $html);
    }

    public function test_checkboxes_show_their_error_and_a_keyboard_focus(): void
    {
        $this->withErrors(['kosullar' => 'Koşulları kabul edin.', 'etiketler' => 'En az bir etiket seçin.']);

        $single = Blade::render('<x-ui.checkbox name="kosullar" label="Koşulları kabul ediyorum" />');
        self::assertStringContainsString('aria-invalid="true"', $single);
        self::assertStringContainsString('aria-describedby="kosullar-message"', $single);
        self::assertStringContainsString('<span id="kosullar-message" class="mt-0.5 block text-red-600 dark:text-red-400" aria-hidden="true">Koşulları kabul edin.</span>', $single);
        self::assertStringContainsString('focus-visible:!outline-2', $single);

        $list = Blade::render('<x-ui.checkbox name="etiketler[]" value="acil" label="Acil" />');
        self::assertStringContainsString('aria-invalid="true"', $list);
        self::assertStringNotContainsString('En az bir etiket seçin.', $list, 'The message is the group\'s, not every box\'s.');

        self::assertSame(1, substr_count(Blade::render('<x-ui.checkbox id="kutu" label="Bir" />'), 'id="kutu"'), 'The id is printed once.');
    }

    public function test_ids_are_the_same_on_every_render_and_differ_between_radios(): void
    {
        $input = '<x-ui.input label="Not" x-model="not" />';
        self::assertSame(Blade::render($input), Blade::render($input));
        self::assertStringContainsString('id="not"', Blade::render($input));

        $unnamed = '<x-ui.textarea label="Açıklama" />';
        self::assertSame(Blade::render($unnamed), Blade::render($unnamed));

        $radios = Blade::render('<x-ui.radio x-model="plan" value="aylik" label="Aylık" /><x-ui.radio x-model="plan" value="yillik" label="Yıllık" />');
        self::assertStringContainsString('id="plan-aylik"', $radios);
        self::assertStringContainsString('id="plan-yillik"', $radios);

        $searches = Blade::render('<x-ui.search label="Sipariş ara" /><x-ui.search label="Kişi ara" />');
        preg_match_all('/<input[^>]*\sid="([^"]+)"/', $searches, $ids);
        self::assertCount(2, array_unique($ids[1]));
    }

    /** The switcher only draws: the application gives the languages and the URL that saves the choice. */
    public function test_locale_switcher_posts_to_the_application(): void
    {
        self::assertSame('', trim(Blade::render('<x-ui.locale-switcher :locales="[\'tr\' => \'Türkçe\', \'en\' => \'English\']" />')), 'Without an action nothing is drawn.');
        self::assertSame('', trim(Blade::render('<x-ui.locale-switcher action="/dil" :locales="[\'tr\' => \'Türkçe\']" />')), 'A single-language application gets no switcher.');

        $html = Blade::render('<x-ui.locale-switcher action="/dil" :locales="[\'tr\' => \'Türkçe\', \'en\' => \'English\']" :flags="[\'en\' => \'gb\']" current="en" />');

        self::assertStringContainsString('action="/dil"', $html);
        self::assertStringContainsString('value="tr"', $html);
        self::assertStringContainsString('English', $html);
        self::assertStringContainsString('<svg', $html, 'The flags are inline SVGs.');
        self::assertMatchesRegularExpression('/value="en"[^>]*aria-current="true"/', $html);
    }

    /** The select fix is opt-in: no x-init on a wire:model select unless sync or the config asks for it. */
    public function test_select_reapplies_its_value_only_when_asked(): void
    {
        $options = ':options="[\'a\' => \'A\', \'b\' => \'B\']"';

        self::assertStringNotContainsString('_x_forceModelUpdate', Blade::render('<x-ui.select wire:model="status" '.$options.' />'));
        self::assertStringContainsString('_x_forceModelUpdate', Blade::render('<x-ui.select wire:model="status" :sync="true" '.$options.' />'));

        config(['acun-ui.select_sync' => true]);
        self::assertStringContainsString('_x_forceModelUpdate', Blade::render('<x-ui.select wire:model="status" '.$options.' />'));
        self::assertStringNotContainsString('_x_forceModelUpdate', Blade::render('<x-ui.select wire:model="status" :sync="false" '.$options.' />'));
    }

    public function test_links_from_data_cannot_run_a_script(): void
    {
        self::assertStringContainsString('href="#"', Blade::render('<x-ui.button href="javascript:alert(1)">Aç</x-ui.button>'));
        self::assertStringContainsString('href="#"', Blade::render('<x-ui.breadcrumb :items="[[\'label\' => \'Kat\', \'url\' => \'javascript:alert(1)\'], [\'label\' => \'Son\']]" />'));
        self::assertStringContainsString('href="#"', Blade::render('<x-ui.breadcrumb.item href="javascript:alert(1)" :current="false">Kat</x-ui.breadcrumb.item>'));
        self::assertStringContainsString('href="#"', Blade::render('<x-ui.list-group.item href="javascript:alert(1)">Kat</x-ui.list-group.item>'));
        // Dropdown links (a menu may be stored in the database).
        self::assertStringContainsString('href="#"', Blade::render('<x-ui.dropdown.item href="javascript:alert(1)">Sil</x-ui.dropdown.item>'));
        self::assertStringNotContainsString('javascript:', Blade::render('<x-ui.dropdown.item href="javascript:alert(1)">Sil</x-ui.dropdown.item>'));
        self::assertStringContainsString('href="/siparisler"', Blade::render('<x-ui.button href="/siparisler">Siparişler</x-ui.button>'));
    }

    public function test_smaller_component_fixes(): void
    {
        // A loading button cannot be pressed again.
        self::assertMatchesRegularExpression('/<button type="submit" disabled\s+aria-busy="true"/', Blade::render('<x-ui.button type="submit" :loading="true">Kaydet</x-ui.button>'));

        // The change follows the application's locale.
        app()->setLocale('en');
        self::assertStringContainsString('+12.5%', Blade::render('<x-ui.stat-card label="Sipariş" value="3" change="12.5" />'));
        app()->setLocale('tr');
        self::assertStringContainsString('+12,5%', Blade::render('<x-ui.stat-card label="Sipariş" value="3" change="12.5" />'));

        // auth-header: description optional, attributes kept.
        $header = Blade::render('<x-ui.auth-header title="Giriş" class="mb-6" />');
        self::assertStringContainsString('class="flex w-full flex-col mb-6"', $header);
        self::assertStringNotContainsString('<p', $header);

        // color and variant name the same tone everywhere.
        self::assertSame(Blade::render('<x-ui.badge variant="success">Tamam</x-ui.badge>'), Blade::render('<x-ui.badge color="success">Tamam</x-ui.badge>'));
        self::assertSame(Blade::render('<x-ui.progress :value="40" color="danger" />'), Blade::render('<x-ui.progress :value="40" variant="danger" />'));

        // The customizer's choices say which one is on.
        $customizer = Blade::render('<x-ui.theme-customizer />');
        $choices = preg_match_all('/x-on:click="\$store\.(appearance\.set|customizer\.setSkin|customizer\.setContentWidth|sidebar\.set|navbar\.set)\(/', $customizer);
        self::assertGreaterThan(0, $choices);
        self::assertSame($choices, substr_count($customizer, ':aria-pressed="'));
    }
    public function test_the_same_field_twice_on_a_page_gets_its_own_id(): void
    {
        $html = Blade::render('<acun:input name="email" label="E-posta" /><acun:input name="email" label="E-posta (tekrar)" /><acun:input name="email" id="kendi-id" label="Kendi" />');

        self::assertStringContainsString('id="email"', $html);
        self::assertStringContainsString('for="email"', $html);
        self::assertStringContainsString('id="email-2"', $html);
        self::assertStringContainsString('for="email-2"', $html);
        self::assertStringContainsString('id="kendi-id"', $html, 'An id attribute is kept as given.');

        // Counted per render: the next render starts again, so a field keeps its id.
        self::assertStringContainsString('id="email"', Blade::render('<acun:input name="email" />'));
        self::assertStringNotContainsString('email-2', Blade::render('<acun:input name="email" />'));
    }

    public function test_a_livewire_component_counts_its_field_ids_apart_from_the_page(): void
    {
        $component = new class extends \Livewire\Component
        {
            public string $email = '';

            public function render(): string
            {
                return '<div><acun:input wire:model="email" label="E-posta" /></div>';
            }
        };

        \Livewire\Livewire::component('abone-formu', $component::class);
        $page = Blade::render('<acun:input name="email" /><livewire:abone-formu />');
        $update = \Livewire\Livewire::test($component::class)->set('email', 'a@b.c')->html();

        self::assertSame(2, substr_count($page, 'id="email"'), 'The page field and the component field are counted apart.');
        self::assertStringContainsString('id="email"', $update, 'The update renders the same id.');
    }
    public function test_a_checkbox_list_shows_its_message_once_under_the_group(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['kategoriler' => ['En az bir kategori seçin.']]));
        View::share('errors', $errors);

        $html = Blade::render(<<<'BLADE'
            <acun:checkbox-group label="Kategoriler" name="kategoriler" description="Ürünün göründüğü yerler." required>
                <acun:checkbox wire:model="kategoriler" value="ses" label="Ses" />
                <acun:checkbox wire:model="kategoriler" value="giyilebilir" label="Giyilebilir" />
            </acun:checkbox-group>
            BLADE);

        self::assertSame(1, substr_count($html, 'En az bir kategori seçin.'), 'Once, not under every box.');
        self::assertSame(2, substr_count($html, 'aria-invalid="true"'), 'Every box turns red.');
        self::assertMatchesRegularExpression('/<fieldset\s+class="min-w-0 space-y-2"\s+id="kategoriler-group"\s+aria-describedby="kategoriler-group-description kategoriler-group-message"/', $html);
        self::assertMatchesRegularExpression('/<legend[^>]*>\s*Kategoriler<span[^>]*aria-hidden="true">\*<\/span><span class="sr-only"> \(zorunlu\)<\/span>/', $html);

        // A single box keeps its own message.
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['kabul' => ['Koşulları kabul edin.']])));
        self::assertStringContainsString('Koşulları kabul edin.', Blade::render('<acun:checkbox wire:model="kabul" label="Kabul ediyorum" />'));
        View::share('errors', new ViewErrorBag);
    }

    public function test_a_radio_group_and_the_messages_of_list_items(): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['etiketler.1' => ['Geçersiz etiket.']])));

        $list = Blade::render('<acun:checkbox-group label="Etiketler" name="etiketler[]" inline><acun:checkbox name="etiketler[]" value="a" label="A" /></acun:checkbox-group>');
        self::assertStringContainsString('Geçersiz etiket.', $list, 'An error of one item (etiketler.*) is the group\'s message.');
        self::assertStringContainsString('flex flex-wrap gap-x-6 gap-y-2', $list);

        View::share('errors', new ViewErrorBag);
        $radios = Blade::render('<acun:radio-group label="Kargo" name="kargo"><acun:radio name="kargo" value="standart" label="Standart" /></acun:radio-group>');
        self::assertStringContainsString('id="kargo-group"', $radios);
        self::assertStringContainsString('<legend', $radios);
        self::assertStringNotContainsString('aria-describedby', $radios);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function fields(): array
    {
        // The field (with %s for the attributes), the start of its outer element, the start of its control.
        return [
            'input' => ['<x-ui.input wire:model="sku" label="Kod" %s />', '<div ', '<input\s+id="sku"'],
            'input floating' => ['<x-ui.input wire:model="sku" label="Kod" variant="floating" %s />', '<div ', '<input\s+id="sku"'],
            'select' => ['<x-ui.select wire:model="sku" label="Kod" :options="[\'a\' => \'A\']" %s />', '<div ', '<select\s+id="sku"'],
            'textarea' => ['<x-ui.textarea wire:model="sku" label="Kod" %s />', '<div ', '<textarea\s+id="sku"'],
            'checkbox' => ['<x-ui.checkbox wire:model="sku" label="Kod" %s />', '<label for="sku" ', '<input\s+id="sku"'],
            'radio' => ['<x-ui.radio wire:model="sku" value="a" label="Kod" %s />', '<label for="sku-a" ', '<input\s+id="sku-a"'],
            'input-group' => ['<x-ui.input-group wire:model="sku" label="Kod" prefix="₺" %s />', '<div ', '<input\s+id="sku"'],
        ];
    }

    /**
     * One rule for every field: layout attributes (class, style, x-show, wire:key) place the
     * whole field and go on its outer element, so the label and the message follow them; the
     * rest (wire:model, required, aria-*, data-*, events) belong to the control, and
     * input-class styles the control.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('fields')]
    public function test_layout_attributes_go_on_the_field_and_the_rest_on_the_control(string $field, string $outer, string $control): void
    {
        $html = Blade::render(sprintf($field, 'class="col-span-2" style="order: 2" x-show="open" wire:key="sku-1" required data-testid="sku" aria-describedby="sku-not" x-on:change="saved = false" input-class="font-mono"'));

        $outerTag = substr($html, 0, strpos($html, '>') + 1);
        self::assertStringStartsWith($outer, $outerTag);
        self::assertMatchesRegularExpression('/class="[^"]* col-span-2" style="order: 2;" x-show="open" wire:key="sku-1">$/', $outerTag);

        self::assertSame(1, preg_match('/'.$control.'(?:[^>"]|"[^"]*")*>/s', $html, $match));
        $tag = $match[0];
        self::assertStringContainsString('wire:model="sku"', $tag);
        self::assertStringContainsString('required="required" data-testid="sku"', $tag);
        self::assertStringContainsString('x-on:change="saved = false"', $tag);
        self::assertMatchesRegularExpression('/\sclass="[^"]* font-mono"/', $tag);
        self::assertStringContainsString('aria-describedby="sku-not"', $tag);
        self::assertSame(1, substr_count($tag, 'aria-describedby='), 'printed once');
        foreach (['col-span-2', 'order: 2', 'x-show', 'wire:key'] as $layout) {
            self::assertStringNotContainsString($layout, $tag);
        }
        self::assertSame(1, substr_count($html, 'wire:key="sku-1"'));
    }

    /**
     * A box with its own message keeps its root element: the label, with the layout attributes,
     * as without an error (a morph that brings the error no longer swaps the root, and the box
     * keeps its place in a flex row). The message sits inside the label, out of its name.
     */
    public function test_a_checkbox_with_a_message_keeps_its_label_as_the_root(): void
    {
        $calm = trim(Blade::render('<x-ui.checkbox name="kabul" label="Kabul ediyorum" class="mt-4" wire:key="kabul" />'));
        self::assertStringStartsWith('<label for="kabul" class="inline-flex items-center gap-2.5 text-sm text-gray-700 select-none dark:text-zinc-300 mt-4" wire:key="kabul">', $calm);

        $this->withErrors(['kabul' => ['Koşulları kabul edin.']]);
        $html = trim(Blade::render('<x-ui.checkbox name="kabul" label="Kabul ediyorum" class="mt-4" wire:key="kabul" aria-describedby="kosullar" />'));

        self::assertMatchesRegularExpression('/^<label for="kabul" class="inline-flex [^"]* mt-4" wire:key="kabul">/', $html);
        self::assertStringEndsWith('</label>', $html);
        self::assertSame(1, substr_count($html, '<label'), 'One root element, no wrapper around it.');
        self::assertStringNotContainsString('<div', $html);
        // The message: inside the label, hidden from the label's name, read through aria-describedby.
        self::assertMatchesRegularExpression('/<span id="kabul-message" class="[^"]*text-red-600[^"]*" aria-hidden="true">Koşulları kabul edin\.<\/span>/', $html);
        self::assertSame(1, substr_count($html, 'aria-describedby='), 'printed once');
        self::assertStringContainsString('aria-describedby="kosullar kabul-message"', $html);
        self::assertStringContainsString('Kabul ediyorum', $html);
    }

    /** The same in a Livewire component: a failed save brings the message, the root stays the label for the morph. */
    public function test_a_livewire_checkbox_keeps_its_root_when_the_save_fails(): void
    {
        $test = Livewire::test(new class extends Component
        {
            public bool $kabul = false;

            public function save(): void
            {
                $this->validate(['kabul' => 'accepted'], ['kabul.accepted' => 'Koşulları kabul edin.']);
            }

            public function render(): string
            {
                return '<div><acun:checkbox wire:model="kabul" label="Kabul ediyorum" class="mt-4" /></div>';
            }
        });

        self::assertMatchesRegularExpression('/<label for="kabul" class="inline-flex items-center [^"]* mt-4">/', $test->html());

        $test->call('save')->assertHasErrors('kabul');
        $html = $test->html();
        self::assertMatchesRegularExpression('/<label for="kabul" class="inline-flex items-start [^"]* mt-4">/', $html);
        self::assertStringNotContainsString('gap-1 mt-4', $html, 'No wrapper takes the layout from the label.');
        self::assertStringContainsString('<span id="kabul-message" class="mt-0.5 block text-red-600 dark:text-red-400" aria-hidden="true">Koşulları kabul edin.</span>', $html);
        self::assertStringContainsString('aria-describedby="kabul-message"', $html);
    }

    /** The routing rule lives in one place (Acun\Ui\Support\Field), the same for every field. */
    public function test_one_helper_routes_the_attributes_of_every_field(): void
    {
        $attributes = new ComponentAttributeBag([
            'class' => 'mt-2', 'style' => 'order: 1', ':class' => '{ a: b }', 'x-show' => 'open', 'x-cloak' => true,
            'x-transition.opacity' => true, 'wire:key' => 'k', 'wire:show' => 'open', 'wire:transition' => true,
            'name' => 'sku', 'wire:model.live' => 'sku', 'required' => true, 'autofocus' => true, 'tabindex' => '2',
            'autocomplete' => 'off', 'inputmode' => 'numeric', 'spellcheck' => 'false', 'aria-label' => 'Kod',
            'data-testid' => 'sku', 'data-dirty-ignore' => true, 'x-on:change' => 'saved = false',
        ]);

        self::assertSame(['class', 'style', ':class', 'x-show', 'x-cloak', 'x-transition.opacity', 'wire:key', 'wire:show', 'wire:transition'], array_keys(Field::layoutAttributes($attributes)->getAttributes()));
        self::assertSame(['name', 'wire:model.live', 'required', 'autofocus', 'tabindex', 'autocomplete', 'inputmode', 'spellcheck', 'aria-label', 'data-testid', 'data-dirty-ignore', 'x-on:change'], array_keys(Field::controlAttributes($attributes)->getAttributes()));

        // A field whose root holds the value (combobox, tag-input, rating): the native control gets
        // focus, typing hints, ARIA and data-*; the model, the listeners, the layout and
        // data-dirty-* (the whole field out of the dirty-check) stay on the root.
        self::assertSame(['required', 'autofocus', 'tabindex', 'autocomplete', 'inputmode', 'spellcheck', 'aria-label', 'data-testid'], array_keys(Field::nativeAttributes($attributes)->getAttributes()));
        self::assertSame(['class', 'style', ':class', 'x-show', 'x-cloak', 'x-transition.opacity', 'wire:key', 'wire:show', 'wire:transition', 'name', 'wire:model.live', 'data-dirty-ignore', 'x-on:change'], array_keys(Field::rootAttributes($attributes)->getAttributes()));
    }

    /**
     * input-class replaces the kit's class of the same kind (Tailwind sorts utilities by value,
     * so ring-blue-500 lost to the kit's ring-gray-300 and h-12 to h-10 depending on the build),
     * but never the error frame: a field with an error always shows it.
     */
    public function test_input_class_replaces_the_kit_classes_of_the_same_kind_but_not_the_error_frame(): void
    {
        $html = Blade::render('<x-ui.input name="sku" input-class="ring-blue-500 h-12 px-4 font-mono" />');
        self::assertSame(1, preg_match('/<input\s+id="sku"(?:[^>"]|"[^"]*")*>/s', $html, $input));
        self::assertSame(1, preg_match('/\sclass="([^"]*)"/', $input[0], $class));
        $classes = explode(' ', $class[1]);
        foreach (['ring-blue-500', 'h-12', 'px-4', 'font-mono', 'ring-1', 'ring-inset', 'focus:ring-brand-600', 'dark:ring-zinc-600'] as $kept) {
            self::assertContains($kept, $classes);
        }
        foreach (['ring-gray-300', 'h-10', 'px-3'] as $replaced) {
            self::assertNotContains($replaced, $classes);
        }

        // The other fields follow the same rule.
        self::assertStringNotContainsString(' h-10 ', Blade::render('<x-ui.select name="durum" :options="[1 => \'A\']" input-class="h-12" />'));
        self::assertDoesNotMatchRegularExpression('/\srounded-sm\s/', Blade::render('<x-ui.checkbox name="kabul" input-class="rounded-full" />'));
        self::assertStringContainsString('class="block w-full rounded-sm border-0 py-1.5 px-3 text-gray-900', Blade::render('<x-ui.textarea name="not" />'), 'No input-class: the kit classes as they are.');
        self::assertSame(1, preg_match('/<input\s+id="tutar"(?:[^>"]|"[^"]*")*>/s', Blade::render('<x-ui.input-group name="tutar" prefix="₺" input-class="px-1" />'), $group));
        self::assertStringNotContainsString(' px-3 ', $group[0]);
        self::assertStringContainsString(' px-1', $group[0]);

        // With an error the kit's red frame stays; the page's frame colour gives way to it.
        $this->withErrors(['sku' => ['Kod gerekli.']]);
        $invalid = Blade::render('<x-ui.input name="sku" input-class="ring-transparent focus:ring-blue-600 font-mono" />');
        self::assertSame(1, preg_match('/<input\s+id="sku"(?:[^>"]|"[^"]*")*>/s', $invalid, $input));
        self::assertStringContainsString('ring-red-400', $input[0]);
        self::assertStringContainsString('focus:ring-red-500', $input[0]);
        self::assertStringContainsString('font-mono', $input[0]);
        self::assertStringNotContainsString('ring-transparent', $input[0]);
        self::assertStringNotContainsString('focus:ring-blue-600', $input[0]);

        // Unit: no input-class leaves the kit's string untouched; variants and important are read.
        self::assertSame('block  h-10 px-3', Field::controlClass('block  h-10 px-3', null));
        self::assertSame('block h-12', Field::controlClass('block h-10', ['h-12' => true, 'w-8' => false]));
        self::assertSame('px-3 py-1 p-2 sm:text-sm hover:bg-white text-base', Field::controlClass('px-3 py-1 p-2 text-sm sm:text-sm hover:bg-white', 'text-base'));
        self::assertSame('ring-1 p-1', Field::controlClass('ring-1 px-3 pt-2', 'p-1'));
        self::assertSame('ring-red-400 focus:ring-red-500 ring-2', Field::controlClass('ring-1 ring-red-400 focus:ring-red-500', 'ring-2 ring-transparent focus:ring-blue-500!', 'ring-red-400 focus:ring-red-500'));
        self::assertSame('size-5', Field::controlClass('h-4 w-4', 'size-5'));
        self::assertSame('rounded-full', Field::controlClass('rounded-sm rounded-s-md', 'rounded-full'));
        self::assertSame('border-0 text-gray-900 font-mono text-lg font-bold!', Field::controlClass('border-0 text-sm text-gray-900 font-semibold', 'font-mono text-lg font-bold!'));
    }

    public function test_switch_puts_the_layout_on_its_label_and_its_error_beside_the_text(): void
    {
        $this->withErrors(['notify' => ['Bildirimleri açın.'], 'channels' => ['Bir kanal seçin.']]);
        $html = Blade::render('<x-ui.switch wire:model="notify" label="Bildirim" class="mt-1" wire:key="s1" input-class="x-check" data-testid="s" aria-describedby="not" />');

        self::assertStringContainsString('<label for="switch-notify" class="inline-flex cursor-pointer items-start gap-3 select-none has-disabled:cursor-not-allowed has-disabled:opacity-50 mt-1" wire:key="s1">', $html);
        $input = self::tag($html, '<input\s+id="switch-notify"');
        self::assertStringContainsString('class="peer sr-only x-check" wire:model="notify" data-testid="s"', $input);
        self::assertStringContainsString('aria-invalid="true"', $input);
        self::assertStringContainsString('aria-describedby="not switch-notify-message"', $input);
        // In the label (the root stays), out of its name (aria-hidden), read as the description.
        self::assertStringContainsString('<span id="switch-notify-message" class="mt-0.5 block text-red-600 dark:text-red-400" aria-hidden="true">Bildirimleri açın.</span>', $html);

        // A switch of a list only says it is invalid; the list shows the message.
        $listed = Blade::render('<x-ui.switch wire:model="channels" value="sms" label="SMS" />');
        self::assertStringContainsString('aria-invalid="true"', $listed);
        self::assertStringNotContainsString('Bir kanal seçin.', $listed);

        // Without a name the id is the same on every render (it was random).
        self::assertSame(Blade::render('<x-ui.switch label="Karanlık" />'), Blade::render('<x-ui.switch label="Karanlık" />'));
    }
}
