<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Behaviour that the component docs promise: form values, accessibility
 * wiring, and props that must reach the rendered markup.
 */
final class ComponentBehaviourTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    private function withErrors(array $messages): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));
    }

    public function test_tabs_keep_attributes_and_select_numeric_keys(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.tabs :tabs="[1 => 'Bir', 2 => 'İki']" class="mt-6">
                <x-ui.tab-panel value="1" class="p-2">A</x-ui.tab-panel>
            </x-ui.tabs>
        BLADE);

        self::assertStringContainsString('data-slot="tabs" data-value="1"', $html, 'The numeric key opens as a string.');
        self::assertMatchesRegularExpression('/data-value="1" class="mt-6"/', $html, 'Attributes stay on the root.');
        self::assertMatchesRegularExpression('/data-value="1"\s+aria-selected="true"/', $html);
        self::assertStringContainsString('class="pt-4 p-2"', $html);
    }

    public function test_carousel_does_not_resume_auto_advance_under_reduced_motion(): void
    {
        $html = Blade::render('<x-ui.carousel :interval="3000"><div>1</div></x-ui.carousel>');

        // start() runs on load and when the pointer or focus leaves (tests/js/components.test.mjs).
        self::assertStringContainsString("if (this.paused || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;", $html);
        self::assertStringContainsString('x-on:mouseleave="if (! $el.contains(document.activeElement)) start()"', $html);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function fields(): array
    {
        return [
            'input' => ['<x-ui.input name="email" label="E-posta" %s />'],
            'input floating' => ['<x-ui.input name="email" variant="floating" label="E-posta" %s />'],
            'select' => ['<x-ui.select name="email" :options="[\'a\' => \'A\']" %s />'],
            'textarea' => ['<x-ui.textarea name="email" %s />'],
            'textarea floating' => ['<x-ui.textarea name="email" variant="floating" label="Not" %s />'],
            'input group' => ['<x-ui.input-group name="email" label="E-posta" suffix="@firma.com" %s />'],
        ];
    }

    #[DataProvider('fields')]
    public function test_field_errors_and_hints_describe_the_control(string $blade): void
    {
        self::assertStringNotContainsString('aria-describedby', Blade::render(sprintf($blade, '')));

        $hinted = Blade::render(sprintf($blade, 'hint="İş adresiniz" aria-describedby="extra"'));
        self::assertStringContainsString('aria-describedby="extra email-message"', $hinted);
        self::assertStringContainsString('<p id="email-message"', $hinted);
        self::assertStringNotContainsString('aria-invalid', $hinted);

        $this->withErrors(['email' => 'E-posta geçersiz.']);
        $invalid = Blade::render(sprintf($blade, ''));
        self::assertStringContainsString('aria-invalid="true"', $invalid);
        self::assertStringContainsString('aria-describedby="email-message"', $invalid);
        // Readable in the dark theme too (the danger ink token is the lighter red there).
        self::assertMatchesRegularExpression('/<p id="email-message" class="text-sm text-danger-ink">E-posta geçersiz\./', $invalid);
    }

    /**
     * The neutral frame (ring-line-strong, border-line-strong on x-ui.input-group) used to have a
     * dark: twin on every field, so in dark mode it replaced the red frame of an invalid one. The
     * frame is one token in both themes now, and an invalid field has only the danger line.
     */
    #[DataProvider('fields')]
    public function test_the_error_ring_stays_red_in_the_dark_theme(string $blade): void
    {
        $valid = Blade::render(sprintf($blade, ''));
        self::assertMatchesRegularExpression('/\b(ring|border)-line-strong\b/', $valid);
        self::assertDoesNotMatchRegularExpression('/dark:(ring|border)-/', $valid);

        $this->withErrors(['email' => 'E-posta geçersiz.']);
        $invalid = Blade::render(sprintf($blade, ''));

        self::assertMatchesRegularExpression('/\b(ring|border)-danger-line\b/', $invalid);
        self::assertDoesNotMatchRegularExpression('/\b(ring|border)-line-strong\b|dark:(ring|border)-/', $invalid);
    }

    /**
     * The frame is a border, not an inset ring the add-ons painted over; on focus it stays
     * 1px, uses the shared focus shadow token, and the line between the field and an add-on
     * takes the frame's colour too.
     */
    public function test_input_group_frame_surrounds_the_add_ons_and_follows_focus(): void
    {
        $html = Blade::render('<x-ui.input-group name="eposta" suffix="@example.com" />');

        self::assertMatchesRegularExpression('/class="group\/input-group [^"]*\bborder\b[^"]*focus-within:border-focus focus-within:shadow-\(--ac-focus-shadow\)/', $html);
        self::assertStringNotContainsString('ring-inset', $html);
        self::assertStringNotContainsString('ring-1', $html);
        self::assertStringNotContainsString('shadow-sm', $html);
        self::assertStringNotContainsString('after:w-0.5', $html);
        self::assertMatchesRegularExpression('/<span class="[^"]*group-focus-within\/input-group:after:bg-focus[^"]*after:start-0">@example\.com<\/span>/', $html);
        self::assertStringNotContainsString('bg-sunken px-3', $html);
    }

    /**
     * On focus the frame keeps its 1px width (no ring-2); its shadow is the one token
     * (--ac-focus-shadow, none by default), the red one on an invalid field.
     */
    #[DataProvider('fields')]
    public function test_focus_keeps_a_1px_frame_and_the_shared_focus_shadow_token(string $blade): void
    {
        $html = Blade::render(sprintf($blade, ''));
        self::assertMatchesRegularExpression('/focus(-within)?:shadow-\(--ac-focus-shadow\)/', $html);
        self::assertDoesNotMatchRegularExpression('/focus(-within)?:ring-2/', $html);

        $this->withErrors(['email' => 'E-posta geçersiz.']);
        self::assertMatchesRegularExpression('/focus(-within)?:shadow-\(--ac-focus-shadow-danger\)/', Blade::render(sprintf($blade, '')));
    }

    public function test_input_group_has_the_disabled_and_read_only_look_and_never_forces_its_width(): void
    {
        $html = Blade::render('<x-ui.input-group name="fiyat" suffix="₺" disabled />');

        self::assertStringContainsString('has-[input:disabled]:bg-field-disabled', $html);
        self::assertStringContainsString('has-[input[readonly]]:bg-field-disabled', $html);
        self::assertMatchesRegularExpression('/<input[^>]*class="w-full min-w-0 flex-1 [^"]*disabled:cursor-not-allowed/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*\sdisabled[\s=>]/s', $html);
    }

    /**
     * A null wire:model value matches no option; the browser then selects the first option
     * that is not disabled. A disabled placeholder made that the first real option.
     */
    public function test_select_placeholder_is_what_the_browser_falls_back_to(): void
    {
        $html = Blade::render('<x-ui.select wire:model="birim" placeholder="Seçiniz" :options="[\'kg\' => \'Kilogram\']" />');

        // The first option, not disabled (hidden keeps it out of the list, as disabled did).
        self::assertSame(strpos($html, '<option'), strpos($html, '<option value="" hidden selected>Seçiniz</option>'));
        self::assertMatchesRegularExpression('/<option value="" hidden selected>Seçiniz<\/option>\s*<option value="kg">/', $html);
        self::assertStringNotContainsString('disabled selected', $html);
    }

    public function test_password_toggle_is_reachable_from_the_keyboard(): void
    {
        $html = Blade::render('<x-ui.input name="password" type="password" viewable />');

        self::assertStringNotContainsString('tabindex="-1"', $html);
        self::assertStringContainsString(':aria-pressed="shown.toString()"', $html);
    }

    /** The tooltip plugin (js/plugins/tooltip.js) shows the bubble on hover and focus and wires aria-describedby. */
    public function test_tooltip_is_a_plugin_root_with_a_top_layer_bubble(): void
    {
        $html = Blade::render('<x-ui.tooltip text="Sil"><button>x</button></x-ui.tooltip>');

        self::assertMatchesRegularExpression('/<span data-acun-tooltip class="relative">\s*<button>x<\/button>/', $html);
        self::assertStringNotContainsString('x-data', $html);
        // The bubble: a tooltip in the browser's top layer, hidden until shown, above the element by default.
        self::assertMatchesRegularExpression('/role="tooltip"\s+data-slot="tooltip-content"\s+data-position="top"\s+popover="manual"\s+hidden/', $html);
        self::assertStringContainsString('-translate-y-full', $html);
        self::assertStringContainsString('>Sil</span>', $html);

        $below = Blade::render('<x-ui.tooltip text="Bilgi" position="bottom"><span tabindex="0">?</span></x-ui.tooltip>');
        self::assertStringContainsString('data-position="bottom"', $below);
        self::assertStringNotContainsString('-translate-y-full', $below);
    }

    public function test_toasts_are_announced_and_links_work_without_livewire(): void
    {
        $html = Blade::render('<x-ui.toast-container />');

        self::assertStringContainsString('aria-live="polite"', $html);
        // wire:navigate within the application only; a mailto: or another site opens as a normal link.
        self::assertStringContainsString('if (window.Livewire?.navigate && sameSite(toast.link)) { $event.preventDefault();', $html);
        self::assertStringNotContainsString('x-on:click.prevent', $html);
    }

    public function test_stat_card_actions_stay_on_the_right_without_an_icon(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.stat-card label="Sipariş" value="3">
                <x-slot:actions><button>…</button></x-slot:actions>
            </x-ui.stat-card>
        BLADE);

        self::assertStringContainsString('<div class="ms-auto"><button>…</button></div>', $html);
    }

    public function test_layout_preferences_passed_by_the_server_win_over_the_browser(): void
    {
        // The runtime starts from these and falls back to the browser only for null (tests/js/appearance.test.mjs).
        $saved = $this->runtimeConfig(Blade::render('<x-ui.layout.head navbar-mode="static" :semi-dark="false" />'));
        self::assertSame('static', $saved['navbarMode']);
        self::assertFalse($saved['semiDark']);

        $unsaved = Blade::render('<x-ui.layout.head />');
        self::assertNull($this->runtimeConfig($unsaved)['navbarMode']);
        self::assertNull($this->runtimeConfig($unsaved)['semiDark']);
    }

    /** @return array<string, array{0: string}> */
    public static function solidButtons(): array
    {
        return array_map(fn (string $variant) => [$variant], array_combine(
            $variants = ['primary', 'success', 'danger', 'warning', 'info', 'secondary', 'dark', 'light'],
            $variants,
        ));
    }

    /**
     * Solid buttons read their fill, border, hover and text colours from the theme's tokens
     * (bg-success text-on-success…; light is the sunken grey); shadow adds a shadow in the fill's
     * colour, gone on hover. Disabled ones only fade.
     */
    #[DataProvider('solidButtons')]
    public function test_solid_buttons_take_their_colours_from_the_theme(string $variant): void
    {
        $html = Blade::render('<x-ui.button variant="'.$variant.'" disabled>Kaydet</x-ui.button>');
        $fill = $variant === 'light' ? 'border-line bg-sunken text-dim' : "border-{$variant} bg-{$variant} text-on-{$variant} hover:not-disabled:border-{$variant}-hover hover:not-disabled:bg-{$variant}-hover";
        self::assertStringContainsString(' '.$fill.' ', $html);
        self::assertStringContainsString('disabled:opacity-60', $html);
        self::assertStringNotContainsString('shadow-[', $html);

        $shadow = Blade::render('<x-ui.button variant="'.$variant.'" shadow>Kaydet</x-ui.button>');
        self::assertMatchesRegularExpression('/ shadow-\[0_0\.5rem_1\.125rem_-0\.5rem\] shadow-(?:'.$variant.'|secondary)\/\d+ hover:shadow-none active:shadow-none disabled:shadow-none aria-disabled:shadow-none/', $shadow);
    }

    public function test_only_solid_buttons_get_the_coloured_shadow(): void
    {
        self::assertStringNotContainsString('shadow-[', Blade::render('<x-ui.button variant="white" shadow>Vazgeç</x-ui.button>'));
        self::assertStringNotContainsString('shadow-[', Blade::render('<x-ui.button variant="soft-danger" shadow>Sil</x-ui.button>'));
    }

    /** Every colour has a solid, an outline-* and a soft-* variant; -primary and the gray names are other names. */
    public function test_every_colour_has_outline_and_soft_variants(): void
    {
        foreach (['success', 'danger', 'warning', 'info', 'secondary', 'dark'] as $colour) {
            self::assertStringContainsString('border-'.$colour.'-line text-'.$colour.'-ink', Blade::render('<x-ui.button variant="outline-'.$colour.'">A</x-ui.button>'));
            self::assertStringContainsString('bg-'.$colour.'-soft text-'.$colour.'-tint-ink', Blade::render('<x-ui.button variant="soft-'.$colour.'">A</x-ui.button>'));
        }

        self::assertSame(Blade::render('<x-ui.button variant="outline">A</x-ui.button>'), Blade::render('<x-ui.button variant="outline-primary">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="soft">A</x-ui.button>'), Blade::render('<x-ui.button variant="soft-primary">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="soft-secondary">A</x-ui.button>'), Blade::render('<x-ui.button variant="soft-gray">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="secondary">A</x-ui.button>'), Blade::render('<x-ui.button variant="gray">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="outline-secondary">A</x-ui.button>'), Blade::render('<x-ui.button variant="outline-gray">A</x-ui.button>'));

        // white is the neutral button (a field with a frame), not a colour.
        self::assertStringContainsString('border-line-strong bg-field text-body', Blade::render('<x-ui.button variant="white">Vazgeç</x-ui.button>'));
    }

    /**
     * The brand outline and soft buttons fill with the primary fill on hover in both themes, with
     * its own text colour: white on the default and the presets, the measured text on a custom
     * colour (tests/brand-contrast.test.mjs measures it), never a lighter tint under white text.
     */
    public function test_brand_hover_fills_under_white_text_are_brand_600(): void
    {
        foreach (['outline', 'soft'] as $variant) {
            $html = Blade::render('<x-ui.button variant="'.$variant.'">Kaydet</x-ui.button>');

            self::assertStringContainsString(' hover:not-disabled:bg-primary hover:not-disabled:text-on-primary ', $html, $variant);
            self::assertDoesNotMatchRegularExpression('/hover:not-disabled:bg-primary-(?:tint|soft|line)\b|dark:/', $html, $variant);
        }
    }

    /** A disabled button keeps its colours on hover and does not move when pressed. */
    public function test_hover_and_press_skip_disabled_buttons(): void
    {
        $html = Blade::render('<x-ui.button variant="outline-danger" disabled>Sil</x-ui.button>');

        self::assertStringContainsString('hover:not-disabled:bg-danger ', $html);
        self::assertStringContainsString('active:not-disabled:translate-y-px', $html);
        self::assertDoesNotMatchRegularExpression('/(?<![\w:-])hover:bg-/', $html);
    }

    /** A link cannot be disabled: it gets aria-disabled, leaves the tab order and ignores the pointer. */
    public function test_a_disabled_link_button_is_out_of_reach(): void
    {
        $html = Blade::render('<x-ui.button href="/rapor" variant="link" disabled>Raporu aç</x-ui.button>');

        self::assertMatchesRegularExpression('/<a href="\/rapor"\s+aria-disabled="true" tabindex="-1"/', $html);
        self::assertStringContainsString('aria-disabled:pointer-events-none', $html);
        self::assertStringContainsString('text-primary-ink underline-offset-4 hover:not-disabled:underline', $html);

        self::assertStringNotContainsString('aria-disabled="true"', Blade::render('<x-ui.button href="/rapor">Raporu aç</x-ui.button>'));
    }

    /** Every variant has a 1px border, so buttons of one size are the same height; md matches x-ui.input (40px). */
    public function test_every_button_has_a_border_and_the_sizes_keep_their_height(): void
    {
        self::assertMatchesRegularExpression('/class="inline-flex items-center justify-center rounded-sm border /', Blade::render('<x-ui.button variant="ghost">Temizle</x-ui.button>'));
        self::assertStringContainsString('px-3.5 py-2 text-sm', Blade::render('<x-ui.button>Kaydet</x-ui.button>'));
        self::assertStringContainsString('px-5 py-[11px] text-base', Blade::render('<x-ui.button size="lg">Kaydet</x-ui.button>'));
        self::assertStringContainsString('px-2.5 py-[5px] text-xs', Blade::render('<x-ui.button size="sm">Kaydet</x-ui.button>'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function inactiveFields(): array
    {
        return [
            'input' => ['<x-ui.input name="a" disabled />', 'disabled:bg-field-disabled'],
            'input read-only' => ['<x-ui.input name="a" readonly />', '[&amp;[readonly]]:bg-field-disabled'],
            'input floating' => ['<x-ui.input name="a" variant="floating" label="A" disabled />', 'disabled:opacity-60'],
            'select' => ['<x-ui.select name="a" disabled />', 'disabled:bg-field-disabled'],
            'textarea' => ['<x-ui.textarea name="a" disabled />', 'disabled:bg-field-disabled'],
            'radio' => ['<x-ui.radio name="a" value="1" label="A" disabled />', 'disabled:opacity-40'],
        ];
    }

    #[DataProvider('inactiveFields')]
    public function test_disabled_and_read_only_fields_look_inactive(string $blade, string $class): void
    {
        self::assertStringContainsString($class, Blade::render($blade));
    }

    public function test_info_is_sky_in_every_component_and_brand_badges_remain(): void
    {
        self::assertStringContainsString('bg-info-tint text-info-tint-ink', Blade::render('<x-ui.alert>Bilgi</x-ui.alert>'));
        self::assertStringContainsString('bg-info-tint text-info-tint-ink', Blade::render('<x-ui.badge variant="info">Bilgi</x-ui.badge>'));
        self::assertStringContainsString('bg-primary-tint text-primary-tint-ink', Blade::render('<x-ui.badge variant="brand">Yeni</x-ui.badge>'));
    }

    public function test_card_body_grows_so_footers_line_up(): void
    {
        $html = Blade::render('<x-ui.card title="A">Gövde<x-slot:footer>Alt</x-slot:footer></x-ui.card>');

        self::assertStringContainsString('class="flex flex-col rounded-sm', $html);
        self::assertMatchesRegularExpression('/<div\s+class="flex-1 p-5">\s*Gövde/', $html);
    }

    public function test_a_display_class_on_a_button_replaces_inline_flex(): void
    {
        self::assertMatchesRegularExpression('/class="inline-flex items-center/', Blade::render('<x-ui.button>A</x-ui.button>'));

        $hidden = Blade::render('<x-ui.button class="hidden sm:inline-flex">A</x-ui.button>');
        self::assertDoesNotMatchRegularExpression('/class="inline-flex/', $hidden);
        self::assertStringContainsString('hidden sm:inline-flex', $hidden);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function externalLinks(): array
    {
        return [
            'button' => ['<x-ui.button href="https://docs.example.test" external>Doküman</x-ui.button>'],
            'dropdown item' => ['<x-ui.dropdown-item href="https://docs.example.test" external>Doküman</x-ui.dropdown-item>'],
        ];
    }

    #[DataProvider('externalLinks')]
    public function test_external_links_open_in_a_new_tab_and_say_so(string $blade): void
    {
        $html = Blade::render($blade);

        self::assertMatchesRegularExpression('/target="_blank" rel="noopener/', $html);
        self::assertStringContainsString('<span class="sr-only"> (yeni sekmede açılır)</span>', $html);
        self::assertStringNotContainsString('wire:navigate', $html);
    }

    public function test_page_header_shows_breadcrumb_title_description_and_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.page-header title="Kullanıcılar" description="Tüm hesaplar" :breadcrumbs="[['label' => 'Uygulamalar'], ['label' => 'Kullanıcılar']]" home-url="/">
                <x-slot:actions><button>Yeni</button></x-slot:actions>
            </x-ui.page-header>
        BLADE);

        self::assertStringContainsString('<h1 class="text-2xl font-semibold text-ink">Kullanıcılar</h1>', $html);
        self::assertStringContainsString('Tüm hesaplar', $html);
        self::assertStringContainsString('Uygulamalar', $html);
        self::assertStringContainsString('<button>Yeni</button>', $html);
        self::assertStringNotContainsString('<nav', Blade::render('<x-ui.page-header title="Özet" />'), 'No breadcrumb without items.');
    }

    public function test_description_list_escapes_values_and_takes_rich_items(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.description-list :items="['Sipariş no' => '<b>AC-1</b>']">
                <x-ui.description-item label="Durum"><span data-badge>Ödendi</span></x-ui.description-item>
            </x-ui.description-list>
        BLADE);

        self::assertStringContainsString('<dt class="min-w-0 font-medium break-words text-muted">Sipariş no</dt>', $html);
        self::assertStringContainsString('&lt;b&gt;AC-1&lt;/b&gt;', $html);
        self::assertStringContainsString('<span data-badge>Ödendi</span>', $html);
    }
}
