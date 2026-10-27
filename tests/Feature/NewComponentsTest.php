<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

/** segmented, chip-group, file-icon, inline-confirm. */
final class NewComponentsTest extends TestCase
{
    public function test_segmented_is_a_radio_group_bound_like_a_field(): void
    {
        $html = Blade::render('<acun:segmented wire:model.live="donem" label="Dönem" :options="[\'gun\' => \'Gün\', \'hafta\' => \'Hafta\']" class="mb-4" />');

        self::assertStringContainsString('role="radiogroup"', $html);
        self::assertStringContainsString('aria-label="Dönem"', $html);
        self::assertSame(2, substr_count($html, 'type="radio" name="donem"'));
        self::assertSame(2, substr_count($html, 'wire:model.live="donem"'), 'The binding is on each radio, not on the group.');
        self::assertMatchesRegularExpression('/role="radiogroup"\s+aria-label="Dönem"\s+class="[^"]*mb-4/', $html);

        $form = Blade::render('<acun:segmented name="gorunum" value="liste" :options="[\'kart\' => \'Kart\', \'liste\' => \'Liste\']" />');
        self::assertMatchesRegularExpression('/value="liste" class="peer sr-only" checked/', $form);
    }

    public function test_chip_group_shows_counts_and_takes_one_or_many(): void
    {
        $html = Blade::render('<acun:chip-group name="durum" value="kargoda" :options="[\'hepsi\' => [\'label\' => \'Tümü\', \'count\' => 1480], \'kargoda\' => [\'label\' => \'Kargoda\', \'count\' => 35]]" />');

        self::assertStringContainsString('role="radiogroup"', $html);
        self::assertStringContainsString('>1.480</span>', $html, 'Counts in the page\'s number format.');
        self::assertMatchesRegularExpression('/value="kargoda" class="peer sr-only" checked/', $html);

        $many = Blade::render('<acun:chip-group name="etiketler" multiple :value="[\'yeni\']" :options="[\'yeni\' => \'Yeni\', \'indirim\' => \'İndirimli\']" />');
        self::assertStringContainsString('role="group"', $many);
        self::assertSame(2, substr_count($many, 'type="checkbox" name="etiketler[]"'));
    }

    public function test_file_icon_colours_the_sheet_by_its_extension(): void
    {
        self::assertMatchesRegularExpression('/text-red-600[^"]*">.*PDF\s*<\/span>/s', Blade::render('<acun:file-icon name="fatura-2026-10.pdf" />'));
        self::assertStringContainsString('text-emerald-700', Blade::render('<acun:file-icon name="stok.XLSX" />'));
        self::assertStringContainsString('text-gray-600', Blade::render('<acun:file-icon name="yedek.bak" />'));
        self::assertStringContainsString('aria-hidden="true"', Blade::render('<acun:file-icon name="a.pdf" />'));
    }

    public function test_inline_confirm_puts_the_action_on_the_confirm_button_only(): void
    {
        $html = Blade::render('<acun:inline-confirm wire:click="delete(7)" question="Silinsin mi?">Sil</acun:inline-confirm>');

        self::assertSame(1, substr_count($html, 'wire:click="delete(7)"'));
        self::assertMatchesRegularExpression('/data-slot="inline-confirm-confirm"[^>]*wire:click="delete\(7\)"/', $html);
        self::assertStringContainsString('Silinsin mi?', $html);
        self::assertStringContainsString('data-acun-inline-confirm', $html);
        self::assertMatchesRegularExpression('/data-slot="inline-confirm-question" hidden role="group" aria-label="Silinsin mi\?"/', $html);
        self::assertStringContainsString('border-danger-line text-danger-ink', $html, 'The first button is the outline of the variant.');
    }
    public function test_logo_shows_the_dark_version_where_dark_applies(): void
    {
        $html = Blade::render('<acun:logo src="/logo.svg" dark-src="/logo-white.svg" alt="Acme Mağaza" class="h-8" />');

        self::assertStringContainsString('<span class="inline-flex h-8">', $html);
        self::assertStringContainsString('src="/logo.svg" alt="Acme Mağaza" class="h-full w-auto dark:hidden"', $html);
        self::assertStringContainsString('src="/logo-white.svg" alt="Acme Mağaza" class="hidden h-full w-auto dark:block"', $html);

        self::assertStringContainsString('<img src="/logo.svg"', Blade::render('<acun:logo src="/logo.svg" class="h-8" />'));
    }

    public function test_two_factor_qr_and_recovery_codes(): void
    {
        $qr = Blade::render('<acun:two-factor-qr secret="JBSWY3DPEHPK3PXP"><svg viewBox="0 0 10 10"></svg></acun:two-factor-qr>');
        self::assertStringContainsString('role="img" aria-label="Doğrulama uygulaması için QR kodu"', $qr);
        self::assertStringContainsString('data-theme="light" class="flex-none rounded-sm bg-surface p-3', $qr, 'White in the dark theme too (a light section), for the camera.');
        self::assertStringContainsString('JBSW Y3DP EHPK 3PXP', $qr);

        $codes = Blade::render('<acun:recovery-codes :codes="[\'a1b2-c3d4\', \'e5f6-g7h8\']" filename="acme-kodlar.txt" />');
        self::assertSame(2, substr_count($codes, '<li class="select-all">'));
        self::assertStringContainsString('acme-kodlar.txt', $codes);
        self::assertStringContainsString('role="status"', $codes);
    }
}
