<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\Tone;
use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;

/** Every coloured component takes the palette's colour names; the older names keep working. */
final class ToneTest extends TestCase
{
    public function test_names_and_aliases_resolve_to_the_palette(): void
    {
        foreach (Tone::NAMES as $name) {
            self::assertSame($name, Tone::name($name));
        }

        self::assertSame('primary', Tone::name('brand'));
        self::assertSame('secondary', Tone::name('neutral'));
        self::assertSame('secondary', Tone::name('gray'));
        self::assertSame('danger', Tone::name('error'));
        self::assertSame('success', Tone::name('emerald'));
        self::assertSame('warning', Tone::name('amber'));
        self::assertSame('info', Tone::name('blue'));
        self::assertSame('info', Tone::name('mor', 'info'));
        self::assertSame('primary', Tone::name(null));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function badges(): array
    {
        return [
            'primary' => ['primary', 'bg-brand-50 text-brand-700'],
            'secondary' => ['secondary', 'bg-secondary-100 text-secondary-700'],
            'success' => ['success', 'bg-emerald-50 text-emerald-700'],
            'danger' => ['danger', 'bg-red-50 text-red-700'],
            'warning' => ['warning', 'bg-amber-50 text-amber-700'],
            'info' => ['info', 'bg-sky-50 text-sky-700'],
            'dark' => ['dark', 'bg-dark/10 text-dark'],
            'brand (old name)' => ['brand', 'bg-brand-50 text-brand-700'],
            'neutral (old name)' => ['neutral', 'bg-secondary-100 text-secondary-700'],
            'unknown' => ['mor', 'bg-secondary-100 text-secondary-700'],
        ];
    }

    #[DataProvider('badges')]
    public function test_badge_takes_every_palette_colour(string $variant, string $classes): void
    {
        self::assertStringContainsString($classes, Blade::render('<x-ui.badge variant="'.$variant.'">A</x-ui.badge>'));
    }

    public function test_solid_badge_is_filled_with_the_colour(): void
    {
        // White text on every solid fill: success, warning and info use the darker -solid shade,
        // danger the 600 shade.
        self::assertStringContainsString('bg-emerald-solid text-white', Blade::render('<x-ui.badge variant="success" solid>Aktif</x-ui.badge>'));
        self::assertStringContainsString('bg-amber-solid text-white', Blade::render('<x-ui.badge variant="warning" solid>Bekliyor</x-ui.badge>'));
        self::assertStringContainsString('bg-red-600 text-white', Blade::render('<x-ui.badge variant="danger" solid>İptal</x-ui.badge>'));
        // The solid primary badge has the primary button's fill and text (a light brand preset uses dark text).
        self::assertStringContainsString('bg-(--btn-primary-bg) text-(--btn-primary-color,white)', Blade::render('<x-ui.badge variant="primary" solid>Yeni</x-ui.badge>'));
        self::assertStringContainsString('bg-secondary-100', Blade::render('<x-ui.badge>Taslak</x-ui.badge>'));
    }

    public function test_alert_takes_every_palette_colour(): void
    {
        self::assertStringContainsString('bg-brand-50 text-brand-800', Blade::render('<x-ui.alert variant="primary">A</x-ui.alert>'));
        self::assertStringContainsString('bg-secondary-100 text-secondary-800', Blade::render('<x-ui.alert variant="secondary">A</x-ui.alert>'));
        self::assertStringContainsString('bg-dark/10 text-dark', Blade::render('<x-ui.alert variant="dark">A</x-ui.alert>'));
        self::assertStringContainsString('bg-sky-50 text-sky-800', Blade::render('<x-ui.alert>A</x-ui.alert>'));
        self::assertStringContainsString('bg-red-50 text-red-800', Blade::render('<x-ui.alert variant="error">A</x-ui.alert>'));
    }

    public function test_spinner_takes_a_colour(): void
    {
        self::assertStringContainsString('inline-flex items-center gap-2 text-emerald-500', Blade::render('<x-ui.spinner color="success" />'));
        self::assertStringContainsString('text-brand-600 dark:text-brand-400', Blade::render('<x-ui.spinner />'));
    }

    public function test_progress_and_stat_card_take_a_colour(): void
    {
        $progress = Blade::render('<x-ui.progress :value="40" color="success" />');
        self::assertStringContainsString('bg-emerald-100 dark:bg-emerald-500/15', $progress);
        self::assertMatchesRegularExpression('/class="h-full rounded-full [^"]*bg-emerald-500"\s+style="width: 40%"/', $progress);
        self::assertStringContainsString('bg-(--btn-primary-bg)', Blade::render('<x-ui.progress :value="40" />'));

        self::assertStringContainsString('bg-secondary-100 text-secondary-600', Blade::render('<x-ui.stat-card label="Taslak" value="3" icon="document" color="secondary" />'));
        self::assertStringContainsString('bg-brand-100 text-brand-600', Blade::render('<x-ui.stat-card label="Toplam" value="3" icon="users" color="brand" />'));
    }
    public function test_the_button_takes_the_palette_aliases_alone_and_after_outline_or_soft(): void
    {
        $same = fn (string $a, string $b) => self::assertSame(Blade::render("<x-ui.button variant=\"{$b}\">Sil</x-ui.button>"), Blade::render("<x-ui.button variant=\"{$a}\">Sil</x-ui.button>"), "{$a} draws {$b}");

        $same('error', 'danger');
        $same('green', 'success');
        $same('brand', 'primary');
        $same('outline-error', 'outline-danger');
        $same('soft-yellow', 'soft-warning');
        $same('outline-brand', 'outline');
        $same('gray', 'secondary');
        self::assertStringContainsString('ui-btn-danger', Blade::render('<x-ui.button variant="error">Sil</x-ui.button>'));
        // Names that are not colours keep their own variant.
        self::assertStringContainsString('border-gray-300 bg-white', Blade::render('<x-ui.button variant="white">Vazgeç</x-ui.button>'));
    }
}
