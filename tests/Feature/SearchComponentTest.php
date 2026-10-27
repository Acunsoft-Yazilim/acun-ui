<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

final class SearchComponentTest extends TestCase
{
    public function test_it_is_a_search_landmark_with_a_labelled_search_field(): void
    {
        $html = Blade::render('<x-ui.search name="q" placeholder="Sipariş ara" class="max-w-md" />');

        self::assertMatchesRegularExpression('/<div\s+role="search"\s+class="flex w-full flex-col gap-1.5 max-w-md"/', $html);
        self::assertMatchesRegularExpression('/<input[^>]*type="search"/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*name="q"/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*aria-label="Sipariş ara"/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*enterkeyhint="search"/s', $html);
        // class stays on the outer element.
        self::assertDoesNotMatchRegularExpression('/<input[^>]*max-w-md/s', $html);
        self::assertStringNotContainsString('<form', $html);
    }

    public function test_with_an_action_it_is_a_get_form_with_the_slot_inside(): void
    {
        $html = Blade::render('<x-ui.search name="q" action="/siparisler" button="Ara"><input type="hidden" name="durum" value="acik"></x-ui.search>');

        self::assertMatchesRegularExpression('/<form\s+role="search"\s+method="GET" action="\/siparisler"/', $html);
        self::assertMatchesRegularExpression('/<button\s+type="submit"[^>]*>Ara<\/button>/s', $html);
        self::assertMatchesRegularExpression('/<input type="hidden" name="durum" value="acik">\s*<\/form>/', $html);
        self::assertStringNotContainsString('_token', $html);
    }

    public function test_a_post_form_carries_the_csrf_token(): void
    {
        $this->startSession();

        $html = Blade::render('<x-ui.search name="q" action="/ara" method="post" />');

        self::assertStringContainsString('method="POST"', $html);
        self::assertStringContainsString('name="_token"', $html);

        self::assertStringContainsString('name="_token"', Blade::render('<x-ui.search name="q" :action="url(\'/ara\')" method="post" />'), 'An absolute address of the application.');
    }

    /**
     * An unsafe action (javascript:, another site's //host) is not printed. It used to become
     * action="#": with method="post" the form then POSTed the term and the CSRF token to the
     * page it sits on (POST /orders could create an order). Now the form searches with GET on
     * the current page, without a token, and the developer finds a warning in the log.
     */
    public function test_an_unsafe_action_is_left_out_the_form_falls_back_to_get_and_a_warning_is_logged(): void
    {
        $this->startSession();
        Log::spy();

        // The address may come from data (a tenant's setting): javascript: never becomes the form's action.
        $html = Blade::render('<x-ui.search name="q" :action="$action" method="post" />', ['action' => 'javascript:alert(1)']);
        self::assertMatchesRegularExpression('/<form\s+role="search"\s+method="GET"\s/', $html);
        self::assertStringNotContainsString('action=', $html);
        self::assertStringNotContainsString('method="POST"', $html);
        self::assertStringNotContainsString('name="_token"', $html);
        self::assertStringNotContainsStringIgnoringCase('javascript:', $html);

        // A protocol-relative address of another site is not safe either.
        $html = Blade::render('<x-ui.search name="q" action="//arama.ornek.com/ara" method="post" />');
        self::assertMatchesRegularExpression('/<form\s+role="search"\s+method="GET"\s/', $html);
        self::assertStringNotContainsString('action=', $html);
        self::assertStringNotContainsString('name="_token"', $html);

        Log::shouldHaveReceived('warning')->twice();

        // Another site may receive the search, never the CSRF token.
        $html = Blade::render('<x-ui.search name="q" action="https://arama.ornek.com/ara" method="post" />');
        self::assertStringContainsString('action="https://arama.ornek.com/ara"', $html);
        self::assertStringContainsString('method="POST"', $html);
        self::assertStringNotContainsString('name="_token"', $html);
    }

    /**
     * The rule of the other fields: layout attributes (class, style, x-show, wire:key) place
     * the whole search, the rest (wire:model, data-*, events…) reach the field, input-class styles it.
     */
    public function test_layout_attributes_place_the_whole_search_and_the_rest_reach_the_field(): void
    {
        $html = Blade::render('<x-ui.search wire:model.live="arama" class="max-w-md" style="order: 2" x-show="open" wire:key="s1" data-testid="ara" x-on:keyup="ara()" input-class="font-mono px-4" />');

        self::assertSame(1, preg_match('/^<div\s+role="search"(?:[^>"]|"[^"]*")*>/', $html, $outer));
        self::assertStringContainsString('class="flex w-full flex-col gap-1.5 max-w-md" style="order: 2;" x-show="open" wire:key="s1"', $outer[0]);
        foreach (['wire:model', 'data-testid', 'x-on:keyup'] as $control) {
            self::assertStringNotContainsString($control, $outer[0]);
        }

        self::assertSame(1, preg_match('/<input\s+data-acun-control\s+id=(?:[^>"]|"[^"]*")*>/s', $html, $input));
        self::assertStringContainsString('wire:model.live="arama"', $input[0]);
        self::assertStringContainsString('data-testid="ara"', $input[0]);
        self::assertStringContainsString('x-on:keyup="ara()"', $input[0]);
        self::assertMatchesRegularExpression('/\sclass="peer [^"]* font-mono px-4"/', $input[0]);
        self::assertStringNotContainsString(' px-2.5 ', $input[0], 'input-class replaces the kit padding.');
        foreach (['max-w-md', 'order: 2', 'x-show', 'wire:key'] as $layout) {
            self::assertStringNotContainsString($layout, $input[0]);
        }
        self::assertSame(1, substr_count($html, 'wire:key="s1"'));
    }

    /** :value="request('q')" with ?q[]=x in the URL: an empty field, not a failing page. */
    public function test_a_value_that_is_not_text_leaves_the_field_empty(): void
    {
        $html = Blade::render('<x-ui.search name="q" :value="$term" />', ['term' => ['x']]);

        self::assertMatchesRegularExpression('/<input[^>]*value=""/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*value="kulaklık"/s', Blade::render('<x-ui.search name="q" :value="$term" />', ['term' => 'kulaklık']));
    }

    public function test_the_clear_button_and_the_shortcut_hint_follow_the_field(): void
    {
        $html = Blade::render('<x-ui.search name="q" shortcut="/" />');

        self::assertMatchesRegularExpression('/<button\s+type="button"\s+data-slot="search-clear"[^>]*peer-\[:not\(:placeholder-shown\)\]:flex[^>]*aria-label="Aramayı temizle"/s', $html);
        self::assertMatchesRegularExpression('/peer-\[:placeholder-shown:not\(:focus\)\]:flex[^>]*>\s*<kbd[^>]*>\/<\/kbd>/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*aria-keyshortcuts="\/"/s', $html);
        // The search plugin (js/plugins/search.js) clears the field and focuses it on the shortcut key.
        self::assertMatchesRegularExpression('/data-acun-search\s+data-shortcut="\/"/', $html);

        $plain = Blade::render('<x-ui.search name="q" :clearable="false" />');
        self::assertStringNotContainsString('Aramayı temizle', $plain);
        self::assertStringNotContainsString('data-shortcut', $plain);
    }

    public function test_a_livewire_field_shows_a_spinner_while_its_property_updates(): void
    {
        $html = Blade::render('<x-ui.search wire:model.live.debounce.300ms="arama" />');

        self::assertMatchesRegularExpression('/<svg\s+wire:loading.remove wire:target="arama"/', $html);
        self::assertMatchesRegularExpression('/<span wire:loading wire:target="arama" class="[^"]*animate-spin/', $html);
        self::assertMatchesRegularExpression('/<input[^>]*id="search-arama"/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*wire:model.live.debounce.300ms="arama"/s', $html);
        self::assertDoesNotMatchRegularExpression('/<input[^>]*\sname=/s', $html);
    }

    public function test_a_visible_label_replaces_the_aria_label(): void
    {
        $html = Blade::render('<x-ui.search name="q" label="Ürün ara" />');

        self::assertStringContainsString('<label for="search-q"', $html);
        self::assertDoesNotMatchRegularExpression('/<input[^>]*aria-label=/s', $html);
    }

    public function test_disabled_disables_the_button_and_drops_the_clear_button(): void
    {
        $html = Blade::render('<x-ui.search name="q" button="Ara" disabled />');

        self::assertMatchesRegularExpression('/<button\s+type="submit"\s+disabled/s', $html);
        self::assertStringNotContainsString('Aramayı temizle', $html);
    }

    public function test_large_size_and_focus_frame(): void
    {
        $html = Blade::render('<x-ui.search name="q" size="lg" />');

        self::assertStringContainsString('h-12', $html);
        self::assertStringContainsString('focus-within:border-focus focus-within:shadow-(--ac-focus-shadow)', $html);
    }
}
