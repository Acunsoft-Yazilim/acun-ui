<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Support\PaginationWindow;
use Acun\Ui\Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginationWindowTest extends TestCase
{
    public function test_the_window_has_the_same_number_of_slots_on_every_page(): void
    {
        self::assertSame([1, 2, 3, 4, 5, null, 200], PaginationWindow::slots(1, 200));
        self::assertSame([1, null, 99, 100, 101, null, 200], PaginationWindow::slots(100, 200));
        self::assertSame([1, null, 196, 197, 198, 199, 200], PaginationWindow::slots(200, 200));

        foreach (range(1, 200) as $page) {
            $slots = PaginationWindow::slots($page, 200);
            self::assertCount(7, $slots, "page {$page}");
            self::assertContains($page, $slots, "page {$page} is shown");
        }

        foreach (range(1, 60) as $page) {
            self::assertCount(9, PaginationWindow::slots($page, 60, 2), "page {$page}, two on each side");
        }
    }

    public function test_a_gap_never_stands_for_a_single_page_and_the_neighbours_are_shown(): void
    {
        self::assertSame([1, 2, 3, 4, 5, null, 9], PaginationWindow::slots(4, 9));
        self::assertSame([1, null, 5, 6, 7, 8, 9], PaginationWindow::slots(6, 9));

        foreach (range(1, 40) as $last) {
            foreach ([0, 1, 2, 3] as $onEachSide) {
                foreach (range(1, $last) as $page) {
                    $slots = PaginationWindow::slots($page, $last, $onEachSide);
                    $context = "page {$page} of {$last}, {$onEachSide} on each side";

                    self::assertCount(min($last, 2 * $onEachSide + 5), $slots, $context);

                    foreach (range(max(1, $page - $onEachSide), min($last, $page + $onEachSide)) as $neighbour) {
                        self::assertContains($neighbour, $slots, $context);
                    }

                    foreach ($slots as $index => $slot) {
                        if ($slot === null) {
                            self::assertGreaterThanOrEqual(2, $slots[$index + 1] - $slots[$index - 1] - 1, $context.': a gap hides two pages or more');
                        }
                    }
                }
            }
        }
    }

    public function test_few_pages_are_all_shown_and_out_of_range_pages_are_clamped(): void
    {
        self::assertSame([1, 2, 3], PaginationWindow::slots(2, 3));
        self::assertSame([1], PaginationWindow::slots(1, 0));
        self::assertSame([1, null, 196, 197, 198, 199, 200], PaginationWindow::slots(999, 200));
    }

    public function test_the_last_page_shows_a_disabled_next_arrow_with_a_tooltip(): void
    {
        app()->setLocale('tr');
        $paginator = (new LengthAwarePaginator(range(1, 25), 5000, 25, 200))->onEachSide(1);
        $html = view('acun-ui::components.ui.pagination', ['paginator' => $paginator, 'elements' => []])->render();

        self::assertStringContainsString('title="Son sayfadasınız"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('>200</span>', $html);
        self::assertStringNotContainsString('title="İlk sayfadasınız"', $html);
    }

    /** A paginator with its own page name (two lists on one page) pages through that name. */
    public function test_the_livewire_calls_use_the_paginators_page_name(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 10), 50, 10, 2, ['pageName' => 'ordersPage']);
        $html = html_entity_decode(view('acun-ui::components.ui.pagination', ['paginator' => $paginator, 'elements' => []])->render(), ENT_QUOTES);

        self::assertStringContainsString("previousPage('ordersPage')", $html);
        self::assertStringContainsString("nextPage('ordersPage')", $html);
        self::assertStringContainsString("gotoPage(3, 'ordersPage')", $html);

        $default = html_entity_decode(view('acun-ui::components.ui.pagination', ['paginator' => new LengthAwarePaginator(range(1, 10), 50, 10, 2), 'elements' => []])->render(), ENT_QUOTES);
        self::assertStringContainsString("nextPage('page')", $default);
    }
}
