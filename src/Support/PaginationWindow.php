<?php

namespace Acun\Ui\Support;

/**
 * Page numbers of acun:pagination with a fixed number of slots, so the bar keeps
 * its width and the arrows stay under the pointer while paging:
 *
 *     1 2 3 4 5 … 200        (near the start)
 *     1 … 99 100 101 … 200    (in the middle)
 *     1 … 196 197 198 199 200 (near the end)
 *
 * null marks a gap ("…"). With $onEachSide pages on each side of the current one,
 * there are always 2 * $onEachSide + 5 slots once the pages don't fit.
 */
final class PaginationWindow
{
    /** @return list<int|null> */
    public static function slots(int $current, int $last, int $onEachSide = 1): array
    {
        $last = max(1, $last);
        $current = min(max(1, $current), $last);
        $onEachSide = max(0, $onEachSide);
        $slots = 2 * $onEachSide + 5;

        if ($last <= $slots) {
            return range(1, $last);
        }

        // Pages shown next to the first / last one before a gap is needed.
        $edge = $slots - 2;

        // A gap always stands for two pages or more ("…" for a single page would hide it for nothing).
        if ($current <= $edge - $onEachSide) {
            return [...range(1, $edge), null, $last];
        }

        if ($current >= $last - $edge + $onEachSide + 1) {
            return [1, null, ...range($last - $edge + 1, $last)];
        }

        return [1, null, ...range($current - $onEachSide, $current + $onEachSide), null, $last];
    }
}
