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

        self::assertStringContainsString('tab: \'1\',', $html, 'The numeric key opens as a string.');
        self::assertMatchesRegularExpression('/data-slot="tabs" class="mt-6"/', $html, 'Attributes stay on the root.');
        self::assertStringContainsString('x-show="selected(\'1\')"', $html);
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
        // Readable in the dark theme too.
        self::assertMatchesRegularExpression('/<p id="email-message" class="text-sm text-red-600 dark:text-red-400">E-posta geçersiz\./', $invalid);
    }

    /**
     * The neutral frame of the dark theme (dark:ring-zinc-600, dark:border-zinc-600 on
     * x-ui.input-group) used to be on every field, so in dark mode it replaced the red
     * frame of an invalid one.
     */
    #[DataProvider('fields')]
    public function test_the_error_ring_stays_red_in_the_dark_theme(string $blade): void
    {
        self::assertMatchesRegularExpression('/dark:(ring|border)-zinc-600/', Blade::render(sprintf($blade, '')));

        $this->withErrors(['email' => 'E-posta geçersiz.']);
        $invalid = Blade::render(sprintf($blade, ''));

        self::assertMatchesRegularExpression('/\b(ring|border)-red-400\b/', $invalid);
        self::assertDoesNotMatchRegularExpression('/dark:(ring|border)-zinc-600/', $invalid);
    }

    /**
     * The frame is a border, not an inset ring the add-ons painted over; on focus it stays
     * 1px, uses the shared focus shadow token, and the line between the field and an add-on
     * takes the frame's colour too.
     */
    public function test_input_group_frame_surrounds_the_add_ons_and_follows_focus(): void
    {
        $html = Blade::render('<x-ui.input-group name="eposta" suffix="@example.com" />');

        self::assertMatchesRegularExpression('/class="group\/input-group [^"]*\bborder\b[^"]*focus-within:border-brand-600 focus-within:shadow-\(--ui-focus-shadow\)/', $html);
        self::assertStringNotContainsString('ring-inset', $html);
        self::assertStringNotContainsString('ring-1', $html);
        self::assertStringNotContainsString('shadow-sm', $html);
        self::assertStringNotContainsString('after:w-0.5', $html);
        self::assertMatchesRegularExpression('/<span class="[^"]*group-focus-within\/input-group:after:bg-brand-600[^"]*after:start-0">@example\.com<\/span>/', $html);
        self::assertStringNotContainsString('bg-gray-50 px-3', $html);
    }

    /**
     * On focus the frame keeps its 1px width (no ring-2); its shadow is the one token
     * of panel.css (none by default), the red one on an invalid field.
     */
    #[DataProvider('fields')]
    public function test_focus_keeps_a_1px_frame_and_the_shared_focus_shadow_token(string $blade): void
    {
        $html = Blade::render(sprintf($blade, ''));
        self::assertMatchesRegularExpression('/focus(-within)?:shadow-\(--ui-focus-shadow\)/', $html);
        self::assertDoesNotMatchRegularExpression('/focus(-within)?:ring-2/', $html);

        $this->withErrors(['email' => 'E-posta geçersiz.']);
        self::assertMatchesRegularExpression('/focus(-within)?:shadow-\(--ui-focus-shadow-error\)/', Blade::render(sprintf($blade, '')));
    }

    public function test_input_group_has_the_disabled_and_read_only_look_and_never_forces_its_width(): void
    {
        $html = Blade::render('<x-ui.input-group name="fiyat" suffix="₺" disabled />');

        self::assertStringContainsString('has-[input:disabled]:bg-gray-50', $html);
        self::assertStringContainsString('has-[input[readonly]]:bg-gray-50', $html);
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

    public function test_tooltip_opens_on_focus_and_describes_its_trigger(): void
    {
        $html = Blade::render('<x-ui.tooltip text="Sil"><button>x</button></x-ui.tooltip>');

        self::assertStringContainsString('x-on:focusin="open()"', $html);
        // The pointer can rest on the tooltip, and Escape hides it from anywhere (WCAG 1.4.13).
        self::assertSame(2, substr_count($html, 'x-on:mouseleave="close(120)"'));
        self::assertStringContainsString('x-on:keydown.escape.window="show = false"', $html);
        self::assertStringNotContainsString('pointer-events-none fixed', $html);
        // A binding on the button: a Livewire re-render keeps it.
        self::assertStringContainsString("<button :aria-describedby=\"\$id('ui-tooltip')\">x</button>", $html);
        self::assertStringNotContainsString("setAttribute('aria-describedby'", $html);
        self::assertStringContainsString('role="tooltip"', $html);

        // Other content: set once on its first focusable element.
        self::assertStringContainsString("setAttribute('aria-describedby', \$id('ui-tooltip'))", Blade::render('<x-ui.tooltip text="Bilgi"><span tabindex="0">?</span></x-ui.tooltip>'));
        self::assertStringContainsString(":id=\"\$id('ui-tooltip')\"", $html);
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
     * Solid buttons read their fill, border and hover colours from the theme (.ui-btn-* in
     * panel.css); shadow adds the coloured shadow. Disabled ones only fade.
     */
    #[DataProvider('solidButtons')]
    public function test_solid_buttons_take_their_colours_from_the_theme(string $variant): void
    {
        $html = Blade::render('<x-ui.button variant="'.$variant.'" disabled>Kaydet</x-ui.button>');
        self::assertStringContainsString('ui-btn ui-btn-'.$variant.' ', $html);
        self::assertStringContainsString('disabled:opacity-60', $html);
        self::assertStringNotContainsString('ui-btn-shadow', $html);

        self::assertStringContainsString('ui-btn-shadow', Blade::render('<x-ui.button variant="'.$variant.'" shadow>Kaydet</x-ui.button>'));
    }

    public function test_only_solid_buttons_get_the_coloured_shadow(): void
    {
        self::assertStringNotContainsString('ui-btn', Blade::render('<x-ui.button variant="white" shadow>Vazgeç</x-ui.button>'));
        self::assertStringNotContainsString('ui-btn', Blade::render('<x-ui.button variant="soft-danger" shadow>Sil</x-ui.button>'));
    }

    /** Every colour has a solid, an outline-* and a soft-* variant; -primary and the gray names are other names. */
    public function test_every_colour_has_outline_and_soft_variants(): void
    {
        foreach (['success' => 'emerald', 'danger' => 'red', 'warning' => 'amber', 'info' => 'sky', 'secondary' => 'secondary'] as $colour => $scale) {
            self::assertStringContainsString('border-'.$scale.'-', Blade::render('<x-ui.button variant="outline-'.$colour.'">A</x-ui.button>'));
        }
        self::assertStringContainsString('border-dark text-dark', Blade::render('<x-ui.button variant="outline-dark">A</x-ui.button>'));
        self::assertStringContainsString('bg-dark/10 text-dark', Blade::render('<x-ui.button variant="soft-dark">A</x-ui.button>'));

        self::assertSame(Blade::render('<x-ui.button variant="outline">A</x-ui.button>'), Blade::render('<x-ui.button variant="outline-primary">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="soft">A</x-ui.button>'), Blade::render('<x-ui.button variant="soft-primary">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="soft-secondary">A</x-ui.button>'), Blade::render('<x-ui.button variant="soft-gray">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="secondary">A</x-ui.button>'), Blade::render('<x-ui.button variant="gray">A</x-ui.button>'));
        self::assertSame(Blade::render('<x-ui.button variant="outline-secondary">A</x-ui.button>'), Blade::render('<x-ui.button variant="outline-gray">A</x-ui.button>'));

        // white is the neutral button (white with a border), not a colour.
        self::assertStringContainsString('border-gray-300 bg-white text-gray-700', Blade::render('<x-ui.button variant="white">Vazgeç</x-ui.button>'));
    }

    /**
     * The brand outline and soft buttons fill with brand-600 on hover in both themes: white
     * text reaches 4.5:1 on it for any brand colour (tests/brand-contrast.test.mjs
     * measures the light and the dark theme value); on brand-500 it does not
     * (#2563eb: 3.89:1).
     */
    public function test_brand_hover_fills_under_white_text_are_brand_600(): void
    {
        foreach (['outline', 'soft'] as $variant) {
            $html = Blade::render('<x-ui.button variant="'.$variant.'">Kaydet</x-ui.button>');

            self::assertStringContainsString(' hover:not-disabled:bg-brand-600 hover:not-disabled:text-white ', $html, $variant);
            self::assertStringContainsString(' dark:hover:not-disabled:bg-brand-600 dark:hover:not-disabled:text-white', $html, $variant);
            self::assertDoesNotMatchRegularExpression('/hover:not-disabled:bg-brand-(?:[1-5]\d\d|50)\b/', $html, $variant);
        }
    }

    /** A disabled button keeps its colours on hover and does not move when pressed. */
    public function test_hover_and_press_skip_disabled_buttons(): void
    {
        $html = Blade::render('<x-ui.button variant="outline-danger" disabled>Sil</x-ui.button>');

        self::assertStringContainsString('hover:not-disabled:bg-red-600', $html);
        self::assertStringContainsString('active:not-disabled:translate-y-px', $html);
        self::assertDoesNotMatchRegularExpression('/(?<![\w:-])hover:bg-/', $html);
    }

    /** A link cannot be disabled: it gets aria-disabled, leaves the tab order and ignores the pointer. */
    public function test_a_disabled_link_button_is_out_of_reach(): void
    {
        $html = Blade::render('<x-ui.button href="/rapor" variant="link" disabled>Raporu aç</x-ui.button>');

        self::assertMatchesRegularExpression('/<a href="\/rapor"\s+aria-disabled="true" tabindex="-1"/', $html);
        self::assertStringContainsString('aria-disabled:pointer-events-none', $html);
        self::assertStringContainsString('text-brand-600 underline-offset-4 hover:not-disabled:underline', $html);

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
            'input' => ['<x-ui.input name="a" disabled />', 'disabled:bg-gray-50'],
            'input read-only' => ['<x-ui.input name="a" readonly />', '[&amp;[readonly]]:bg-gray-50'],
            'input floating' => ['<x-ui.input name="a" variant="floating" label="A" disabled />', 'disabled:opacity-60'],
            'select' => ['<x-ui.select name="a" disabled />', 'disabled:bg-gray-50'],
            'textarea' => ['<x-ui.textarea name="a" disabled />', 'disabled:bg-gray-50'],
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
        self::assertStringContainsString('bg-sky-50 text-sky-800', Blade::render('<x-ui.alert>Bilgi</x-ui.alert>'));
        self::assertStringContainsString('bg-sky-50 text-sky-700', Blade::render('<x-ui.badge variant="info">Bilgi</x-ui.badge>'));
        self::assertStringContainsString('bg-brand-50 text-brand-700', Blade::render('<x-ui.badge variant="brand">Yeni</x-ui.badge>'));
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

        self::assertStringContainsString('<h1 class="text-2xl font-semibold text-gray-900 dark:text-zinc-100">Kullanıcılar</h1>', $html);
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

        self::assertStringContainsString('<dt class="min-w-0 font-medium break-words text-gray-500 dark:text-zinc-400">Sipariş no</dt>', $html);
        self::assertStringContainsString('&lt;b&gt;AC-1&lt;/b&gt;', $html);
        self::assertStringContainsString('<span data-badge>Ödendi</span>', $html);
    }
}
