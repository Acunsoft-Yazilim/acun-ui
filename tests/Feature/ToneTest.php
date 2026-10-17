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
            'primary' => ['primary', 'bg-primary-tint text-primary-tint-ink'],
            'secondary' => ['secondary', 'bg-secondary-tint text-secondary-tint-ink'],
            'success' => ['success', 'bg-success-tint text-success-tint-ink'],
            'danger' => ['danger', 'bg-danger-tint text-danger-tint-ink'],
            'warning' => ['warning', 'bg-warning-tint text-warning-tint-ink'],
            'info' => ['info', 'bg-info-tint text-info-tint-ink'],
            'dark' => ['dark', 'bg-dark-soft text-dark-tint-ink'],
            'brand (old name)' => ['brand', 'bg-primary-tint text-primary-tint-ink'],
            'neutral (old name)' => ['neutral', 'bg-secondary-tint text-secondary-tint-ink'],
            'unknown' => ['mor', 'bg-secondary-tint text-secondary-tint-ink'],
        ];
    }

    #[DataProvider('badges')]
    public function test_badge_takes_every_palette_colour(string $variant, string $classes): void
    {
        self::assertStringContainsString($classes, Blade::render('<x-ui.badge variant="'.$variant.'">A</x-ui.badge>'));
    }

    public function test_solid_badge_is_filled_with_the_colour(): void
    {
        // The tone's solid fill with its own text colour (white on success, warning, info and danger,
        // whose fills are the darker shades that carry it).
        self::assertStringContainsString('bg-success text-on-success', Blade::render('<x-ui.badge variant="success" solid>Aktif</x-ui.badge>'));
        self::assertStringContainsString('bg-warning text-on-warning', Blade::render('<x-ui.badge variant="warning" solid>Bekliyor</x-ui.badge>'));
        self::assertStringContainsString('bg-danger text-on-danger', Blade::render('<x-ui.badge variant="danger" solid>İptal</x-ui.badge>'));
        // The solid primary badge has the primary button's fill and text (a light custom colour uses dark text).
        self::assertStringContainsString('bg-primary text-on-primary', Blade::render('<x-ui.badge variant="primary" solid>Yeni</x-ui.badge>'));
        self::assertStringContainsString('bg-secondary-tint', Blade::render('<x-ui.badge>Taslak</x-ui.badge>'));
    }

    public function test_alert_takes_every_palette_colour(): void
    {
        self::assertStringContainsString('bg-primary-tint text-primary-tint-ink', Blade::render('<x-ui.alert variant="primary">A</x-ui.alert>'));
        self::assertStringContainsString('bg-secondary-tint text-secondary-tint-ink', Blade::render('<x-ui.alert variant="secondary">A</x-ui.alert>'));
        self::assertStringContainsString('bg-dark-tint text-dark-tint-ink', Blade::render('<x-ui.alert variant="dark">A</x-ui.alert>'));
        self::assertStringContainsString('bg-info-tint text-info-tint-ink', Blade::render('<x-ui.alert>A</x-ui.alert>'));
        self::assertStringContainsString('bg-danger-tint text-danger-tint-ink', Blade::render('<x-ui.alert variant="error">A</x-ui.alert>'));
        self::assertStringContainsString('text-danger-line', Blade::render('<x-ui.alert variant="error">A</x-ui.alert>'));
    }

    public function test_spinner_takes_a_colour(): void
    {
        self::assertStringContainsString('inline-flex items-center gap-2 text-success-line', Blade::render('<x-ui.spinner color="success" />'));
        self::assertStringContainsString('text-primary-ink', Blade::render('<x-ui.spinner />'));
    }

    public function test_progress_and_stat_card_take_a_colour(): void
    {
        $progress = Blade::render('<x-ui.progress :value="40" color="success" />');
        self::assertStringContainsString('bg-success-soft', $progress);
        self::assertMatchesRegularExpression('/class="h-full rounded-full [^"]*bg-success-line"\s+style="width: 40%"/', $progress);
        self::assertMatchesRegularExpression('/class="h-full rounded-full [^"]*bg-primary"\s+style="width: 40%"/', Blade::render('<x-ui.progress :value="40" />'));

        self::assertStringContainsString('bg-secondary-soft text-secondary-ink', Blade::render('<x-ui.stat-card label="Taslak" value="3" icon="document" color="secondary" />'));
        self::assertStringContainsString('bg-primary-soft text-primary-ink', Blade::render('<x-ui.stat-card label="Toplam" value="3" icon="users" color="brand" />'));
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
        self::assertStringContainsString('border-danger bg-danger text-on-danger', Blade::render('<x-ui.button variant="error">Sil</x-ui.button>'));
        // Names that are not colours keep their own variant.
        self::assertStringContainsString('border-line-strong bg-field', Blade::render('<x-ui.button variant="white">Vazgeç</x-ui.button>'));
    }
}
